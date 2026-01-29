# Business Requirements - Analytics Application

## Project Overview
This application is a tracking and analytics platform designed to monitor user interactions across websites under the `*.vify.pl` subdomain. It provides insights into page visits and download link interactions.

## Core Features

### 1. Tracking Script
- **Domain Scope**: The script will be included on pages within the `*.vify.pl` subdomain.
- **Data Collection**:
    - Sends information about users visiting the website.
    - Tracks clicks on links with the `download` attribute.
    - Monitors the frequency of these download interactions.

### 2. Data Storage & Privacy
- **Visit Tracking**:
    - Stores visit counts for specific URLs.
    - Tracks both standard page visits and file downloads via links.
    - Only unique visits/downloads per day per URL are persisted in the main database.
- **Privacy & Optimization**:
    - User IPs are stored in a cache for the duration of the day to identify unique visitors.
    - IP addresses are NOT persisted in the long-term database to maintain privacy and prevent database bloat.

### 3. Analytics Dashboard
- **Functionality**: A simple dashboard to visualize collected data using charts and tables.
- **Access Control**: The dashboard is restricted to authenticated users only.
- **Visualization**: Clear presentation of access records (visits and downloads).
