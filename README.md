# CholoGhuri 2.0 — Travel Booking & Management System

CholoGhuri is a comprehensive, production-grade web-based **Tour & Travel Booking Management System** engineered for exploring the natural wonders of Bangladesh (Cox's Bazar, Sajek Valley, Sundarbans, Saint Martin's Island, Sreemangal, Bandarban, Rangamati, Kuakata, and Tanguar Haor).

---

## 🛠️ Technology Stack

* **Frontend**: HTML5, CSS3 (Custom Design System with CSS variables), Vanilla JavaScript, Font Awesome 6.5, SweetAlert2
* **Backend**: PHP 8.x (Prepared Statements with MySQLi & PDO, password_hash, CSRF security, Session fixation defense)
* **Database**: MySQL 8.0 (Relational schema with foreign keys, indexes, and comprehensive Bangladesh seed data)
* **Environment**: XAMPP (Apache & MySQL) / Linux LAMP

---

## 🚀 How to Run in XAMPP

1. **Copy Project Folder**:
   Copy this directory into your XAMPP web root (the application derives its base URL from the folder name):
   ```text
   C:\xampp\htdocs\chologhuri 2
   ```

2. **Start Apache & MySQL**:
   Open XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Create a dedicated application database user** (do not use MySQL `root`):
   Create the database and a local-only user in phpMyAdmin/MySQL. Grant only the application privileges:
   `SELECT, INSERT, UPDATE, DELETE` on `chologhuri.*`. Import/upgrade scripts require elevated privileges; run them separately as a database administrator, not with the website account.

4. **Configure environment variables**:
   Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the Apache/PHP process environment. The application does not load `.env` files and fails closed if values are missing. For a remote database, also set `DB_SSL_CA` to the readable CA certificate path; remote MySQL TLS certificate verification is mandatory.

5. **Import Database Schema**:
   * Open phpMyAdmin: `http://localhost/phpmyadmin/`
   * Create a new database named `chologhuri` (UTF8mb4 collation).
   * Go to **Import** and select `database/chologhuri.sql`.
   * (For an existing installation, back up the database and run `database/upgrade.sql`.)

6. **Access the Application** at `http://localhost/chologhuri%202/` (or the URL matching the installed folder name). Register a traveler account. Sample database accounts are disabled and have no shared/default passwords. Provision the first administrator using a unique password hash and an out-of-band SQL insert; never enable or reuse the sample accounts.

7. **Production server requirements**: Use HTTPS, ensure Apache permits the project `.htaccess` files (`AllowOverride All` for this directory), set `display_errors=Off`, restrict MySQL to the application host, use the dedicated least-privilege DB user, and back up/rotate all previously used credentials. Do not expose the `database/` directory or uploads as executable PHP.

See [SECURITY.md](SECURITY.md) for the audit checklist, implemented controls, and deployment steps that cannot be verified from source alone.

---

## 🌟 Key Features

### User Side
* Browse authentic Bangladesh destinations with genuine photography.
* Live keyword search, destination filters, duration sorting, and price range filters.
* Interactive dynamic booking workflow with departure date selection, seats validation, multiple traveler information cards (name, phone, age, gender), and live BDT (৳) pricing.
* Payment inquiry options (merchant destinations must be configured and verified by the deployment owner).
* User Dashboard with metrics (Total Bookings, Upcoming Trips, Completed Trips, Cancelled Trips, Wishlist Count).
* Official E-Ticket / Travel Voucher generation with printable PDF view.
* Cancellation request submission with reasons for admin refund review.
* Wishlist management (1-click save/remove).
* Verified review system for travelers with completed trips (1–5 star rating).
* Notification center with unread badges.
* User profile editing and password changing.

### Admin Side
* Dedicated admin authentication & session authorization checks on every management page.
* Real-time KPI Dashboard (Users, Packages, Destinations, Bookings, Revenue, Pending Payments).
* Interactive Chart.js analytics for monthly trends and booking distribution.
* Dynamic Tour Package CRUD with duration, pricing, seat availability, itinerary, and real photo selector.
* Tourist Destination Management with division categories, descriptions, and cover images.
* User Management (View, Search, Activate/Deactivate access).
* Booking Management (Filter, Confirm, Reject, Complete, Cancel).
* Payment Verification (Verify bKash/Nagad/Card transaction IDs).
* Review moderation (Approve, Hide, Delete).
* Cancellation request management with admin notes and refund status.
* Filtered reports with 1-Click CSV Export for financial audits.
* Immutable admin activity logs with IP tracking and timestamps.
* System settings (Contacts, helpline numbers, merchant numbers, booking policies).
