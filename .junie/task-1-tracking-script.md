### Task 1: Tracking Script Implementation

#### User Story
As a site owner, I want a lightweight tracking script to be included on my `*.vify.pl` websites so that I can automatically monitor user visits and download link interactions without manual event logging.

#### Requirements
- **Language**: TypeScript (compiled to JavaScript for production).
- **Scope**: Must work on any subdomain of `vify.pl`.
- **Functionality**:
    - Automatically detect and send a "page visit" event on script load.
    - Attach event listeners to all links with the `download` attribute.
    - Track clicks on these download links.
    - Include the current URL in the payload.
- **Optimization**: The script should be lightweight and non-blocking.

#### Acceptance Criteria
1. The script correctly identifies page visits and sends them to the backend API.
2. The script correctly identifies clicks on links with the `download` attribute and sends them to the backend API.
3. The script does not interfere with the website's normal functionality.
4. The script uses the correct API endpoint for data collection.

#### How to Test
1. **Manual Test**: Embed the script in a test HTML page on a `vify.pl` subdomain (or local mock). Verify in the Network tab that POST requests are sent to `/api/track` (or equivalent) on page load and on download link clicks.
2. **Automated Test**: Create a Vitest or Jest test case to verify that the event listeners are correctly attached to elements with the `download` attribute.
