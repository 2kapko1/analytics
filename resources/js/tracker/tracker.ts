/**
 * Lightweight tracking script for *.vify.pl domains
 * Tracks page visits and download link clicks
 */

interface TrackingPayload {
    url: string;
}

const currentScript = document.currentScript as HTMLScriptElement;
const API_ENDPOINT = currentScript?.getAttribute('data-domain') + '/api/track';

function sendTrackingData(payload: TrackingPayload): void {
    fetch(API_ENDPOINT, {
        method: 'POST',
        credentials: 'omit',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
        keepalive: true,
    }).catch(() => {
        // Silently fail - don't interfere with website functionality
    });
}

function trackPageVisit(): void {
    sendTrackingData({
        url: window.location.href,
    });
}

function handlePdfClick(event: Event): void {
    const target = event.currentTarget as HTMLAnchorElement;
    sendTrackingData({
        url: target.href,
    });
}

/**
 * Attaches event listeners to all download links
 */
function attachPdfListeners(): void {
    const downloadLinks = document.querySelectorAll<HTMLAnchorElement>('a[href$=".pdf"]');
    downloadLinks.forEach((link) => {
        link.addEventListener('click', handlePdfClick);
    });
}

function init(): void {
    trackPageVisit();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            attachPdfListeners();
        });
    } else {
        attachPdfListeners();
    }
}

init();
