CHOLOGHURI - TRAVEL BOOKING MANAGEMENT SYSTEM

Run on PHP 8.x, MySQL 8, and Apache (XAMPP is supported).

SETUP
1. Place this project folder below C:\xampp\htdocs.
2. Create the chologhuri database and a dedicated local MySQL application user.
   Grant SELECT, INSERT, UPDATE, and DELETE on chologhuri.* only.
3. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS in the Apache/PHP
   process environment. This application does not load a .env file and will
   not connect with missing credentials. Remote DB_HOST also requires DB_SSL_CA.
4. Import database/chologhuri.sql as a database administrator. For upgrades,
   back up the DB and run database/upgrade.sql separately with migration rights.
5. Open the application at http://localhost/<folder-name>/.
6. Register a new traveler. Sample seeded users and admins are disabled.
   Create the first administrator using a unique password_hash() value.

SECURITY
- Use HTTPS in production and permit the included Apache .htaccess rules.
- Never run the website as MySQL root or use sample credentials.
- Keep environment credentials outside the document root and rotate credentials
  that were previously published or used.
- Read SECURITY.md for the full audit checklist and remaining deployment work.
