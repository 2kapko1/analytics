### Task 3: Analytics Dashboard

#### User Story
As an authenticated user, I want to access a dashboard that visualizes the collected tracking data using charts and tables, so that I can easily analyze traffic trends and download performance.

#### Requirements
- **Frontend Stack**: TypeScript, React, Tailwind CSS, Inertia.js.
- **UI Components**: Use **Shadcn UI** components (Table, Cards, Charts).
- **Access Control**: Ensure the dashboard route is protected by Laravel's `auth` middleware.
- **Features**:
    - **Summary Cards**: Display total visits and total downloads.
    - **Charts**: A line or bar chart showing visits/downloads over time.
    - **Data Table**: A sortable table listing URLs and their respective visit/download counts.
- **Data Fetching**: Use Inertia to pass data from the Laravel controller to the React component.

#### Acceptance Criteria
1. The dashboard is only accessible to logged-in users; guests are redirected to login.
2. The dashboard displays data using React components.
3. Charts correctly represent the data fetched from the backend.
4. The layout is responsive and follows the project's styling guidelines.
5. UI components from Shadcn UI are used consistently.

#### How to Test
1. **Manual Test**: Log in to the application and navigate to the dashboard. Verify that data is displayed and that charts render correctly. Attempt to access the dashboard while logged out to verify redirection.
2. **Visual Check**: Verify that Shadcn UI components are used and that the Tailwind CSS styling matches the design expectations.
