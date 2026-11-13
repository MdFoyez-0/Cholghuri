<?php
/**
 * CholoGhuri - Admin Authentication & Authorization
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function require_admin() {
    global $conn;
    if (!is_admin_logged_in() || !$conn) {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Administrator access required. Please login.'
        ];
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    $admin_id = (int)$_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT id, name, email, role, status FROM admins WHERE id = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(503);
        exit('Service temporarily unavailable.');
    }
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if (!$admin || $admin['status'] !== 'active') {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
        session_regenerate_id(true);
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    $_SESSION['admin_role'] = $admin['role'];
    $route = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $operationsRoutes = ['index.php', 'dashboard.php', 'bookings.php', 'payments.php', 'cancellations.php'];
    if ($admin['role'] !== 'Super Admin'
        && !($admin['role'] === 'Operations Manager' && in_array($route, $operationsRoutes, true))) {
        http_response_code(403);
        exit('You are not authorized to access this page.');
    }
}

function current_admin($conn) {
    if (!is_admin_logged_in()) return null;
    $admin_id = (int)$_SESSION['admin_id'];
    if ($conn) {
        $stmt = $conn->prepare("SELECT id, name, email, role, status FROM admins WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
    }
    return null;
}
?>
