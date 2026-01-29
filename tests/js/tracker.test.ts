import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Store the last payload sent to sendBeacon for verification
let lastSentPayload: string | null = null;

// Mock navigator.sendBeacon
const mockSendBeacon = vi.fn((url: string, data: Blob) => {
    // Extract the data from the Blob by reading it synchronously
    const reader = new FileReader();
    reader.readAsText(data);
    reader.onload = () => {
        lastSentPayload = reader.result as string;
    };
    // Trigger synchronously for testing
    if (data instanceof Blob) {
        // Use arrayBuffer approach for sync reading
        const arrayBuffer = new ArrayBuffer(0);
        lastSentPayload = new TextDecoder().decode(arrayBuffer);
    }
    return true;
});

const mockFetch = vi.fn(() => Promise.resolve(new Response()));

// We need to capture the actual JSON string passed to sendBeacon
const originalSendBeacon = vi.fn((url: string, data: Blob | string) => {
    return true;
});

describe('Tracker Script', () => {
    let capturedPayloads: Array<{ url: string; data: string }> = [];

    beforeEach(() => {
        // Reset captured payloads
        capturedPayloads = [];

        // Mock sendBeacon to capture the JSON string before it becomes a Blob
        Object.defineProperty(navigator, 'sendBeacon', {
            value: vi.fn((url: string, data: Blob) => {
                // We'll verify the call was made, payload verification done differently
                return true;
            }),
            writable: true,
            configurable: true,
        });

        // Mock fetch
        global.fetch = vi.fn(() => Promise.resolve(new Response())) as typeof fetch;

        // Reset DOM
        document.body.innerHTML = '';

        // Mock window.location
        Object.defineProperty(window, 'location', {
            value: {
                href: 'https://test.vify.pl/page',
            },
            writable: true,
            configurable: true,
        });
    });

    afterEach(() => {
        vi.resetModules();
        vi.restoreAllMocks();
    });

    describe('attachDownloadListeners', () => {
        it('should attach click event listeners to all links with download attribute', async () => {
            // Setup DOM with download links
            document.body.innerHTML = `
                <a href="/file1.pdf" download>Download File 1</a>
                <a href="/file2.zip" download="custom-name.zip">Download File 2</a>
                <a href="/regular-link">Regular Link</a>
            `;

            // Import the module (this will trigger init())
            const { attachDownloadListeners } = await import('../../resources/js/tracker/tracker');

            // Clear the mocks from init() call
            vi.mocked(navigator.sendBeacon).mockClear();

            // Re-attach listeners to ensure they're set
            attachDownloadListeners();

            // Get download links
            const downloadLinks = document.querySelectorAll('a[download]');
            expect(downloadLinks.length).toBe(2);

            // Simulate click on first download link
            const clickEvent = new MouseEvent('click', { bubbles: true });
            downloadLinks[0].dispatchEvent(clickEvent);

            // Verify sendBeacon was called
            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
            expect(navigator.sendBeacon).toHaveBeenCalledWith('/api/track', expect.any(Blob));
        });

        it('should not attach listeners to regular links without download attribute', async () => {
            document.body.innerHTML = `
                <a href="/regular-link" id="regular">Regular Link</a>
            `;

            const { attachDownloadListeners } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();

            attachDownloadListeners();

            const regularLink = document.getElementById('regular');
            const clickEvent = new MouseEvent('click', { bubbles: true });
            regularLink?.dispatchEvent(clickEvent);

            // sendBeacon should not be called for regular links
            expect(navigator.sendBeacon).not.toHaveBeenCalled();
        });
    });

    describe('trackPageVisit', () => {
        it('should send page visit event with current URL', async () => {
            const { trackPageVisit } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();

            trackPageVisit();

            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
            expect(navigator.sendBeacon).toHaveBeenCalledWith('/api/track', expect.any(Blob));
        });
    });

    describe('sendTrackingData', () => {
        it('should use sendBeacon when available', async () => {
            vi.mocked(navigator.sendBeacon).mockReturnValue(true);

            const { sendTrackingData } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();
            vi.mocked(global.fetch).mockClear();

            sendTrackingData({ type: 'visit', url: 'https://test.vify.pl' });

            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
            expect(global.fetch).not.toHaveBeenCalled();
        });

        it('should fallback to fetch when sendBeacon fails', async () => {
            // First set sendBeacon to return true for the init() call
            vi.mocked(navigator.sendBeacon).mockReturnValue(true);

            const { sendTrackingData } = await import('../../resources/js/tracker/tracker');

            // Now set it to return false for our test
            vi.mocked(navigator.sendBeacon).mockClear();
            vi.mocked(global.fetch).mockClear();
            vi.mocked(navigator.sendBeacon).mockReturnValue(false);

            sendTrackingData({ type: 'visit', url: 'https://test.vify.pl' });

            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
            expect(global.fetch).toHaveBeenCalledTimes(1);
            expect(global.fetch).toHaveBeenCalledWith('/api/track', expect.objectContaining({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
            }));
        });
    });

    describe('handleDownloadClick', () => {
        it('should send download event with page URL and download URL', async () => {
            document.body.innerHTML = `
                <a href="https://test.vify.pl/files/document.pdf" download id="download-link">Download</a>
            `;

            const { handleDownloadClick } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();

            const link = document.getElementById('download-link') as HTMLAnchorElement;
            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'currentTarget', { value: link });

            handleDownloadClick(event);

            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
            expect(navigator.sendBeacon).toHaveBeenCalledWith('/api/track', expect.any(Blob));
        });
    });

    describe('payload structure', () => {
        it('should create correct visit payload structure', async () => {
            // Test the payload structure by intercepting JSON.stringify
            const originalStringify = JSON.stringify;
            let capturedPayload: unknown = null;

            JSON.stringify = vi.fn((value) => {
                capturedPayload = value;
                return originalStringify(value);
            });

            const { trackPageVisit } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();
            (JSON.stringify as ReturnType<typeof vi.fn>).mockClear();

            trackPageVisit();

            expect(capturedPayload).toEqual({
                type: 'visit',
                url: 'https://test.vify.pl/page',
            });

            JSON.stringify = originalStringify;
        });

        it('should create correct download payload structure', async () => {
            document.body.innerHTML = `
                <a href="https://test.vify.pl/files/document.pdf" download id="download-link">Download</a>
            `;

            const originalStringify = JSON.stringify;
            let capturedPayload: unknown = null;

            JSON.stringify = vi.fn((value) => {
                capturedPayload = value;
                return originalStringify(value);
            });

            const { handleDownloadClick } = await import('../../resources/js/tracker/tracker');
            vi.mocked(navigator.sendBeacon).mockClear();
            (JSON.stringify as ReturnType<typeof vi.fn>).mockClear();

            const link = document.getElementById('download-link') as HTMLAnchorElement;
            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'currentTarget', { value: link });

            handleDownloadClick(event);

            expect(capturedPayload).toEqual({
                type: 'download',
                url: 'https://test.vify.pl/page',
                downloadUrl: 'https://test.vify.pl/files/document.pdf',
            });

            JSON.stringify = originalStringify;
        });
    });
});
