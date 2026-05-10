<?php
/**
 * Campus2Career - Admin Controller
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'delete_user':        deleteUser();        break;
    case 'delete_internship':  deleteInternship();  break;
    case 'toggle_status':      toggleUserStatus();  break;
    default:
        header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
        exit;
}

function deleteUser() {
    $id  = (int)($_GET['id'] ?? 0);
    $pdo = getDBConnection();
    // Prevent admin from deleting themselves
    if ($id === (int)$_SESSION['user_id']) {
        setFlash('error', 'You cannot delete your own account.');
        header('Location: ' . BASE_URL . 'views/admin/users.php');
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'User deleted successfully.');
    header('Location: ' . BASE_URL . 'views/admin/users.php');
    exit;
}

function deleteInternship() {
    $id  = (int)($_GET['id'] ?? 0);
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM internships WHERE internship_id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Internship deleted successfully.');
    header('Location: ' . BASE_URL . 'views/admin/internships.php');
    exit;
}

// ── Toggle account status (enable / disable) ──
function toggleUserStatus() {
    $id     = (int)($_GET['id'] ?? 0);
    $action = $_GET['toggle'] ?? '';
    if (!$id || !in_array($action, ['disable','enable'])) {
        setFlash('error', 'Invalid request.');
        header('Location: ' . BASE_URL . 'views/admin/users.php'); exit;
    }
    $pdo    = getDBConnection();
    $status = $action === 'disable' ? 'disabled' : 'active';
    // silently ignore if column missing
    try {
        $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ?")->execute([$status, $id]);
        setFlash('success', 'Account ' . ($status === 'disabled' ? 'disabled' : 're-enabled') . '.');
    } catch (PDOException $e) {
        setFlash('error', 'Could not update account status.');
    }
    header('Location: ' . BASE_URL . 'views/admin/users.php'); exit;
}
