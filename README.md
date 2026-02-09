# 🛡️ Trustpilot Intel: Extraction Suite Pro

![Premium Dashboard](https://img.shields.io/badge/Status-Operational-00b67a?style=for-the-badge)
![Tech Stack](https://img.shields.io/badge/Stack-PHP_|_MySQL_|_Tailwind-1e293b?style=for-the-badge)

**Trustpilot Intel** is a high-performance lead intelligence platform designed to transform raw business data into actionable sales opportunities. It provides a premium interface to explore, filter, and export targeted leads from Trustpilot categories.

---

## 🚀 Core Features

### 🔍 Advanced Leads Explorer
*   **Multi-Tier Filtering**: Segment businesses by Industry Category, Trust Score (Rating), and Company Size (Review Count).
*   **Live Search**: Instantaneous search across thousands of records.
*   **Verification Tracking**: Distinguish at a glance between "Claimed" and "Unclaimed" profiles.
*   **Priority Flagging**: Tag high-value prospects for immediate follow-up.

### 📊 Market Architecture
*   **Density Mapping**: Visualize your data across different tiers like "Mega Hubs", "Strategic Segments", and "Emerging Markets".
*   **Category Discovery**: Intelligent grouping of businesses into distinct market segments.

### 📥 Dynamic Export Intelligence (Memory Engine)
*   **Field Selection**: Choose exactly which data points (Email, Phone, Website, etc.) you want in your CSV.
*   **Export Memory**: The system remembers what you've already downloaded and hides it by default to prevent duplicate outreach.
*   **Lead Cleaner**: Automatically strips UTM tracking codes from URLs for professional output.
*   **Instant Reset**: Clear your export history with one click when starting new campaigns.

### 🕵️ Data Acquisition: The Scrapper
*   **Chrome Extension (Manifest V3)**: A custom-built automated browser extension for high-speed data harvesting.
*   **Automated Pagination**: Navigates through hundreds of Trustpilot category pages without human intervention.
*   **Intelligent Extraction**: Uses content scripts to pull deep-linked data including hidden emails, social profiles, and categories.
*   **Direct Sync**: Seamlessly pushes captured leads to the dashboard database via a secure POST API.

---

## 🛠️ Technical Implementation

-   **Frontend**: Tailwind CSS (Glassmorphic Design), Lucide Icons, Plus Jakarta Sans Typography.
-   **Backend**: PHP 8.x + MySQL.
-   **Database**: Automated schema initialization with `is_flagged` and `is_exported` tracking.
-   **Architecture**: AJAX-powered counts and non-blocking interactions.

---

## ⚙️ Installation & Setup

1.  **Requirement**: Ensure you have XAMPP, WAMP, or any PHP/MySQL server running.
2.  **Database**: The system is designed to self-initialize. 
    *   Open `db.php` and configure your credentials.
    *   Default port set to `3307` (Update to `3306` if using standard XAMPP settings).
3.  **Deployment**:
    *   Copy the project files into your `htdocs` directory.
    *   Access via `http://localhost/Trustpilot`.

---

## 📂 Project Structure

| File | Description |
| :--- | :--- |
| `index.php` | The main intelligence dashboard and leads table. |
| `header.php` | The dynamic navigation and Export Configuration Tray. |
| `categories.php` | Market density browser and industry segmentation. |
| `export.php` | The CSV engine that builds targeted lead sheets. |
| `db.php` | Core database engine and automated table manager. |
| `reset_exports.php` | API endpoint to clear extraction history. |

---

## 🎨 UI Aesthetics
The suite uses a **Dark Mode First** philosophy with custom `backdrop-blur` effects and a curated emerald-to-primary gradient system for a premium, high-tech experience.

---

*Developed for Advanced Lead Extraction & Market Intelligence.*
