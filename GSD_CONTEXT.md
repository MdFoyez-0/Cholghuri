# GSD Codebase Context: CholoGhuri 2.0

## 1. Project Overview
CholoGhuri is a modern, responsive web-based Tour & Travel Booking Management System. It allows users to browse Bangladeshi travel destinations, book packages, and manage their trips. It includes a comprehensive Admin portal for managing users, packages, bookings, payments, and system settings.

## 2. Tech Stack & Environment
- **Frontend:** HTML5, Tailwind CSS (via CDN), Vanilla Javascript, Font Awesome 6.5, SweetAlert2
- **Backend:** PHP 8.x
- **Database:** MySQL 8.0
- **Environment:** XAMPP (Apache) / Linux LAMP

## 3. Architecture & Conventions
- **Routing:** Direct file-based routing (e.g., `booking.php`, `admin/packages.php`).
- **Database Access:** Uses prepared statements with `mysqli` (found in `includes/db.php`).
- **Authentication:** Session-based authentication defined in `includes/auth.php` and `includes/admin-auth.php`.
- **Security:** CSRF tokens are injected and verified on all POST requests (`csrf_field()` and `verify_csrf()`). Input sanitization is used natively (`htmlspecialchars()`, etc.).
- **Styling:** Originally custom CSS -> migrated to Tailwind CSS utility classes on all new and refactored pages. Avoid custom CSS unless absolutely necessary (add overrides into `css/style.css` only when strictly required).
- **UI Components:** Reusable headers and footers are located in the `includes/` directory. Admin pages feature a unified sidebar and responsive data tables.

## 4. Key Directories
- `/admin`: All administrative interfaces (Dashboard, Users, Packages, Bookings, Settings, Reports, etc.).
- `/config`: Database connection and global configuration settings (e.g., `config.php`, `database.php`).
- `/css` and `/js`: Static assets. `style.css` contains legacy and root variables, `script.js` contains core UI animations.
- `/database`: Contains SQL dumps for initializing and upgrading the schema (`chologhuri.sql`).
- `/images`: Static images, specifically `/images/destinations/` and `/images/packages/`.
- `/includes`: Core functions (`functions.php`), authentication checkers, and UI partials (`header.php`, `footer.php`).
- `/user`: User-facing dashboard and profile pages.

## 5. Development Guidelines
- Always use the `BASE_URL` constant defined in `config/config.php` for absolute links and asset paths.
- All database queries must use prepared statements.
- Never disable or bypass the CSRF checks or authentication guards.
- Implement responsive design layouts with Tailwind CSS, favoring a cleanly spaced, visually appealing, "premium" aesthetic.
