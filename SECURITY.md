# CholoGhuri security audit

Audit baseline: the PHP source and SQL files in this directory. The application code is reviewed and hardened, but production infrastructure, external account history, and live payment-provider configuration were not accessible.

## Ordered checklist

| # | Status | Evidence / action |
|---|---|---|
| 1 | Fixed in application | `config/database.php` reads database credentials from process environment and refuses missing values; no default/root password. Seeded shared admin/traveler accounts are disabled in `database/chologhuri.sql` and invalidated by `database/upgrade.sql`. Merchant destinations are blank until configured. |
| 2 | Fixed in application | `config/database.php` reads OS/Apache environment variables only; it does not load a `.env` file. `.gitignore` excludes local env/secrets and generated backups. Keep actual variables outside the document root. |
| 3 | Fixed in application | Every admin data page calls `require_admin()`; `admin/index.php` is no longer a public dashboard and redirects only after authorization. |
| 4 | Fixed in application | `includes/auth.php`, `includes/admin-auth.php`, and both login pages use server-side sessions, database-backed active-account checks, session ID regeneration, and password verification; fixed-password bypasses were removed. |
| 5 | Fixed in application | `includes/admin-auth.php` enforces the explicit role map: Super Admin can use all admin modules; Operations Manager can use dashboard, bookings, payments, and cancellations only. Unknown roles are denied. |
| 6 | Fixed in application | Registration and password change require 12–72 byte passwords and use bcrypt cost 12; login upgrades older bcrypt cost settings. Seed demo users are inactive and their hashes are invalidated. |
| 7 | Fixed in application | Auth, booking/traveler, profile, review, cancellation, report-filter, package, destination, and settings inputs now have server-side type, length, enum, date, or format validation. SQL uses bound parameters for user-controlled values. |
| 8 | Fixed with compatibility caveat | Dynamic HTML is escaped, report links are URL-built and attribute-escaped, flash messages are JSON-encoded, and `config/config.php` sets CSP. The current legacy pages rely on inline JavaScript and inline styles, so the CSP still allows `'unsafe-inline'`; migrate those scripts/styles to nonce/hash-based or external assets before treating CSP as a strict XSS boundary. |
| 9 | Fixed in application | Login, administrator login, registration, and booking submission use a locked, IP-keyed file rate limiter in the system temporary directory; failed storage denies the operation and logs the failure. Behind a reverse proxy, configure trusted client-IP handling rather than trusting arbitrary forwarded headers. Multi-host deployments need shared rate-limit storage. |
| 10 | N/A | No API or CORS configuration/endpoint was found. The application does not emit permissive CORS headers. |
| 11 | Fixed in available routes | No API endpoints were found. CSV export filters use prepared statements, allowlisted report types and strict dates, and neutralize spreadsheet-formula cells. PHP display errors remain disabled and DB connection errors are logged without credentials. |
| 12 | Fixed in application | `config/config.php` sets HSTS on HTTPS requests, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and CSP. Ensure TLS termination makes PHP/Apache see the HTTPS request correctly. |
| 13 | Already enforced | `config/config.php` turns off display and startup errors and logs server-side errors. Confirm the production `php.ini` also has `display_errors=Off`. |
| 14 | N/A / not verifiable | No Composer/npm manifests or lockfiles are present, so a package-based CVE scan/update was not available. Third-party CDN assets remain and must be checked/pinned by the deployment owner; no external dependency scanner was available. |
| 15 | N/A | No Composer/npm package manifest or installed project package inventory is present to prune. |
| 16 | Fixed in repository configuration | Root `.htaccess` disables indexes and blocks dotfiles, SQL dumps, backups, maps, and docs; `database/.htaccess` denies the installer directory; `uploads/.htaccess` denies executable uploads. Apache must have `AllowOverride` enabled. Verify the live server returns 403/404 for blocked artifacts. |
| 17 | Partially fixed in application; deployment action remains | DB connection now requires explicit credentials; non-loopback DB hosts require a readable CA and TLS certificate verification. The application cannot grant DB privileges, restrict firewall/network access, or verify the live DB account. Configure a dedicated local-only DB identity with only `SELECT, INSERT, UPDATE, DELETE` on the application schema; run migrations separately with a privileged operator; restrict network ingress. |
| 18 | N/A / action required | This project directory has no `.git` repository, so its history and remote cannot be scanned. Rotate any previously used/published DB, admin, and payment-provider credentials; inspect source history in the actual repository before deployment. |
| 19 | Documented; environment integration pending | This file records the source audit and controls. PHP syntax validation was run for all PHP files. End-to-end authentication/booking checks against MySQL and live Apache `.htaccess` checks require configured DB variables and running deployment services, which were not available to this audit. |

## Important deployment steps

1. Back up existing data, then run `database/upgrade.sql` with a separate migration account. This disables the known sample accounts and clears the sample payment recipients. Provision real administrators only with unique, strong password hashes; do not re-enable the seeded rows.
2. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the PHP/Apache process environment. Do not put production secrets in this source directory or a public `.env` file. Set `DB_SSL_CA` for remote MySQL and use firewall rules to allow only the application host.
3. Use a dedicated MySQL application account with only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` grants on `chologhuri.*`. Keep schema/migration privileges out of the PHP runtime account.
4. Use HTTPS, enable the included Apache access-control files (`AllowOverride All` or equivalent vhost rules), keep PHP display errors off, and test that `.git`, env files, database dumps, backups, source maps, and executable uploads cannot be fetched.
5. Verify real bKash/Nagad recipient identifiers and support contact details out-of-band before entering them in Admin Settings. Card processing is not integrated; the UI explicitly does not accept card details. No provider API keys or payment credentials were present in the reviewed source.
6. Review actual Git history and rotate secrets if they were ever committed. Source-tree cleanup cannot remove historical exposure.

## Role policy

- **Super Admin:** full administrative access.
- **Operations Manager:** dashboard, booking workflow, payment review, and cancellation request handling.
- **Any other role:** denied until an explicit permission policy is implemented.

## CSP migration note

Inline attributes and inline script/style blocks are legacy application-wide dependencies. The applied policy constrains external origins and blocks object embedding and framing, but `'unsafe-inline'` weakens protection against injected script. Removing that token requires moving inline event handlers and script/style blocks to external assets or adding per-response nonces/hashes across all rendered pages.
