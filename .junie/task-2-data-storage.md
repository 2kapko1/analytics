### Task 2: Data Storage & Privacy Logic

#### User Story
As a data administrator, I want to store visit and download counts efficiently while respecting user privacy, so that I can see aggregate data without storing sensitive personal information like IP addresses long-term.

#### Requirements
- **Stack**: Laravel (PHP 8.2+).
- **API Endpoint**: Create a POST endpoint for the tracking script to send data.
- **Unique Visit Logic**:
    - Use Cache (Redis or similar) to store user IP addresses temporarily (for the current day).
    - Only record a visit in the database if the IP/URL combination is unique for that day.
- **Data Persistence**:
    - Store visit counts per URL and per date in the database.
    - Do NOT store IP addresses in the long-term database (MySQL/PostgreSQL).
- **Security**: Validate that incoming requests are from allowed domains (`*.vify.pl`).

#### Acceptance Criteria
1. The API endpoint correctly receives and validates tracking data.
2. The system correctly identifies unique visitors using cached IP addresses.
3. IP addresses are NOT saved to the database.
4. The database records the correct counts for visits and downloads per URL per day.
5. Expired cache (IPs from previous days) does not affect current day uniqueness.

#### How to Test
1. **Automated Test**: Run `php artisan test` with a new test case that simulates multiple requests from the same IP to the same URL and verifies that only one entry is created/updated in the database for that day.
2. **Database Verification**: Check the database table after sending requests to ensure no IP address columns exist and that counts are incremented correctly.
