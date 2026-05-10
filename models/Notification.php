<?php
/**
 * Campus2Career – Notification Model
 */
class Notification {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                notification_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id         INT NOT NULL,
                type            ENUM('approval','rejection','interview','info') DEFAULT 'info',
                message         TEXT NOT NULL,
                is_read         TINYINT(1) DEFAULT 0,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )
        ");
    }

    public function create(int $userId, string $type, string $message): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $type, $message]);
    }

    public function getForUser(int $userId, int $limit = 10): array {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAllRead(int $userId): void {
        $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
        )->execute([$userId]);
    }
}
