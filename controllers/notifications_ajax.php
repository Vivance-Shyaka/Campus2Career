<?php
/**
 * Campus2Career – Notifications AJAX Endpoint
 */
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../models/Notification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$pdo    = getDBConnection();
$notif  = new Notification($pdo);
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $items  = $notif->getForUser($_SESSION['user_id'], 8);
    $unread = $notif->countUnread($_SESSION['user_id']);
    echo json_encode(['notifications' => $items, 'unread' => $unread]);
    exit;
}

if ($action === 'mark_read') {
    $notif->markAllRead($_SESSION['user_id']);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
