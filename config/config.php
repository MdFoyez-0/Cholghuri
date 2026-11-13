<?php
/**
 * CholoGhuri - System Configuration & Constants
 */

if (!defined('CHOLOGHURI_APP')) {
    define('CHOLOGHURI_APP', true);
}

// Harden session cookies before starting the session.
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    session_start();
}

// Baseline headers. HSTS is emitted only when the request is HTTPS.
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.tailwindcss.com; connect-src 'self'; upgrade-insecure-requests");
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// System Constants
define('APP_NAME', 'CholoGhuri');
define('APP_TAGLINE', 'Explore More. Live Better.');
define('APP_CURRENCY', '৳');
define('BASE_URL', '/' . rawurlencode(basename(dirname(__DIR__))) . '/');

// File Upload Paths
define('UPLOAD_DIR_PACKAGES', __DIR__ . '/../uploads/packages/');
define('UPLOAD_DIR_PROFILES', __DIR__ . '/../uploads/profiles/');

// Error reporting settings (Log errors, do not print in production)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
?>