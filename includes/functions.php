<?php
/**
 * CholoGhuri - Helper Functions & Utilities
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Sanitize user inputs against XSS
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function input_string($source, $key, $default = '') {
    $value = $source[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

// Generate CSRF Token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Render hidden CSRF form field
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Verify CSRF Token
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token)
        || empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid request.');
    }
}

// Format Currency
function format_bdt($amount) {
    return '৳' . number_format((float)$amount, 0);
}

// Generate unique booking code: CHG-10025
function generate_booking_code($conn) {
    $code = 'CHG-' . mt_rand(10000, 99999);
    if ($conn) {
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE booking_code = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $code);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return generate_booking_code($conn);
            }
        }
    }
    return $code;
}

// Set Flash Message
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

// Get and clear Flash Message
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Log Admin Action
function log_admin_action($conn, $admin_id, $action, $description) {
    if (!$conn || !$admin_id) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('isss', $admin_id, $action, $description, $ip);
        return $stmt->execute();
    }
    return false;
}

// Add user notification
function add_notification($conn, $user_id, $title, $message, $type = 'info') {
    if (!$conn || !$user_id) return false;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
    if ($stmt) {
        $stmt->bind_param('isss', $user_id, $title, $message, $type);
        return $stmt->execute();
    }
    return false;
}

// Get Setting Value
function get_setting($conn, $key, $default = '') {
    if (!$conn) return $default;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row['setting_value'];
        }
    }
    return $default;
}

// Redirect Helper
function safe_redirect_target($url, $default = 'user/dashboard.php') {
    if (!is_string($url) || $url === '' || strpos($url, '\\') !== false
        || preg_match('/[\r\n]/', $url)) {
        return $default;
    }
    $parts = parse_url($url);
    if ($parts === false) {
        return $default;
    }
    $path = $parts['path'] ?? '';
    if ($path !== '' && !isset($parts['scheme']) && !isset($parts['host'])) {
        $decodedPath = rawurldecode($path);
        $basePath = rawurldecode(BASE_URL);
        if (strpos($decodedPath, $basePath) === 0) {
            $path = substr($decodedPath, strlen($basePath));
            $url = $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
            $parts = parse_url($url);
        }
    }
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])
        || isset($parts['user']) || isset($parts['pass'])
        || empty($parts['path']) || strpos($parts['path'], '//') === 0
        || preg_match('#(^|/)\.\.?(/|$)#', $parts['path'])
        || !preg_match('#^(?:user/)?[a-zA-Z0-9_-]+\.php$#', $parts['path'])) {
        return $default;
    }
    return $url;
}

function rate_limit_allow($bucket, $limit = 10, $window = 900) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $bucket . '|' . $ip);
    $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'chologhuri-rate-limits';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('Unable to initialize authentication rate limiting.');
        return null;
    }
    if (random_int(1, 100) === 1) {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - 86400) {
                @unlink($file);
            }
        }
    }
    $handle = @fopen($directory . DIRECTORY_SEPARATOR . $key . '.json', 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        error_log('Unable to access authentication rate limit storage.');
        return null;
    }
    $raw = stream_get_contents($handle);
    if (!is_string($raw)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        error_log('Unable to read authentication rate limit storage.');
        return null;
    }
    $state = $raw === '' ? null : json_decode($raw, true);
    if ($raw !== '' && !is_array($state)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        error_log('Authentication rate limit storage is invalid.');
        return null;
    }
    $now = time();
    if (!is_array($state) || !isset($state['start'], $state['count']) || $now - (int)$state['start'] >= $window) {
        $state = ['start' => $now, 'count' => 0];
    }
    $allowed = (int)$state['count'] < $limit;
    if ($allowed) {
        $state['count'] = (int)$state['count'] + 1;
    }
    $encoded = json_encode($state);
    $written = is_string($encoded) && rewind($handle) && ftruncate($handle, 0)
        && fwrite($handle, $encoded) === strlen($encoded)
        && fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    if (!$written) {
        error_log('Unable to persist authentication rate limit state.');
        return null;
    }
    return $allowed;
}

function redirect($url) {
    if (!is_string($url) || preg_match('/[\r\n]/', $url)) {
        http_response_code(400);
        exit('Invalid redirect.');
    }
    header('Location: ' . $url);
    exit;
}
?>
