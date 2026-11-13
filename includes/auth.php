<?php
/**
 * CholoGhuri - User Authentication & Session Security
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login($redirect_url = null) {
    global $conn;
    if (!is_logged_in() || !$conn) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
        $target = $redirect_url ?: $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = safe_redirect_target($target, 'user/dashboard.php');
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please login to access this page.'
        ];
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $user_id = (int)$_SESSION['user_id'];
    if (!$stmt) {
        http_response_code(503);
        exit('Service temporarily unavailable.');
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || $user['status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
        session_regenerate_id(true);
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your account is unavailable. Please contact support.'];
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function current_user($conn) {
    if (!is_logged_in()) return null;
    $user_id = (int)$_SESSION['user_id'];
    if ($conn) {
        $stmt = $conn->prepare("SELECT id, name, email, phone, profile_image, status, created_at FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
    }
    return null;
}
?>
