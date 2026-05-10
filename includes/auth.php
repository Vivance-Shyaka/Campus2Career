<?php
/**
 * Campus2Career - Session & Auth Helper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . $redirect);
        exit;
    }
}

function requireRole($role, $redirect = 'login.php') {
    requireLogin($redirect);
    if ($_SESSION['role'] !== $role) {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}

function getCurrentUser() {
    return isset($_SESSION['user_id']) ? [
        'user_id'  => $_SESSION['user_id'],
        'name'     => $_SESSION['name'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
        'role_id'  => $_SESSION['role_id'] ?? null,
    ] : null;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Define base URL - adjust if project is in subfolder
if (!defined('BASE_URL')) {
    define('BASE_URL', '/campus2career/');
}
