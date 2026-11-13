<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (is_admin_logged_in() && $conn) {
        log_admin_action($conn, (int)$_SESSION['admin_id'], 'Admin Logout', 'Administrator logged out');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    set_flash('info', 'You have been logged out of the admin portal.');
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Log out | CholoGhuri Admin</title></head>
<body>
    <main>
        <h1>Log out of the administrator portal?</h1>
        <form method="POST" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit">Log out</button>
            <a href="dashboard.php">Cancel</a>
        </form>
    </main>
</body>
</html>
