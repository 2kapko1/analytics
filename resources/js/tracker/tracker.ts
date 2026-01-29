/**
 * Lightweight tracking script for *.vify.pl domains
 * Tracks page visits and download link clicks
 */

interface TrackingPayload {
    type: 'visit' | 'download';
    url: string;
    downloadUrl?: string;
}

const API_ENDPOINT = '/api/track';

/**
 * Sends tracking data to the backend API
 * Uses sendBeacon for non-blocking requests, falls back to fetch
 */
function sendTrackingData(payload: TrackingPayload): void {
    const data = JSON.stringify(payload);

    // Try sendBeacon first (non-blocking, works even on page unload)
    if (navigator.sendBeacon) {
        const blob = new Blob([data], { type: 'application/json' });
        const sent = navigator.sendBeacon(API_ENDPOINT, blob);
        if (sent) return;
    }

    // Fallback to fetch with keepalive
    fetch(API_ENDPOINT, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: data,
        keepalive: true,
    }).catch(() => {
        // Silently fail - don't interfere with website functionality
    });
}

/**
 * Tracks a page visit event
 */
function trackPageVisit(): void {
    sendTrackingData({
        type: 'visit',
        url: window.location.href,
    });
}

/**
 * Handles click events on download links
 */
function handleDownloadClick(event: Event): void {
    const target = event.currentTarget as HTMLAnchorElement;
    sendTrackingData({
        type: 'download',
        url: window.location.href,
        downloadUrl: target.href,
    });
}

/**
 * Attaches event listeners to all download links
 */
function attachDownloadListeners(): void {
    const downloadLinks = document.querySelectorAll<HTMLAnchorElement>('a[download]');
    downloadLinks.forEach((link) => {
        link.addEventListener('click', handleDownloadClick);
    });
}

/**
 * Observes DOM for dynamically added download links
 */
function observeDownloadLinks(): void {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof HTMLAnchorElement && node.hasAttribute('download')) {
                    node.addEventListener('click', handleDownloadClick);
                }
                if (node instanceof HTMLElement) {
                    const downloadLinks = node.querySelectorAll<HTMLAnchorElement>('a[download]');
                    downloadLinks.forEach((link) => {
                        link.addEventListener('click', handleDownloadClick);
                    });
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

/**
 * Initializes the tracking script
 */
function init(): void {
    // Track page visit immediately
    trackPageVisit();

    // Attach listeners to existing download links
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            attachDownloadListeners();
            observeDownloadLinks();
        });
    } else {
        attachDownloadListeners();
        observeDownloadLinks();
    }
}

// Auto-initialize
init();

// Export for testing purposes
export {
    sendTrackingData,
    trackPageVisit,
    handleDownloadClick,
    attachDownloadListeners,
    TrackingPayload,
};
