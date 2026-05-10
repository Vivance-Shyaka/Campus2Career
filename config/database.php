<?php
/**
 * Campus2Career - Database Configuration
 * Uses PDO for secure database connections
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'campus2career');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            ensureApplicationSchema($pdo);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function ensureApplicationSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $statements = [
        "ALTER TABLE users ADD COLUMN account_status ENUM('active','disabled') DEFAULT 'active'",
        "ALTER TABLE students ADD COLUMN cv_file VARCHAR(255) NULL",
        "ALTER TABLE companies ADD COLUMN description LONGTEXT NULL",
        "ALTER TABLE companies ADD COLUMN website VARCHAR(255) NULL",
        "ALTER TABLE internships MODIFY description LONGTEXT",
        "ALTER TABLE internships MODIFY requirements LONGTEXT",
        "ALTER TABLE internships ADD COLUMN responsibilities LONGTEXT NULL AFTER description",
        "ALTER TABLE internships ADD COLUMN expectations LONGTEXT NULL AFTER responsibilities",
        "ALTER TABLE internships ADD COLUMN internship_location VARCHAR(160) NULL AFTER expectations",
        "ALTER TABLE applications ADD COLUMN interview_location VARCHAR(255) NULL AFTER interview_date",
        "ALTER TABLE applications ADD COLUMN interview_notes TEXT NULL AFTER interview_location",
    ];

    foreach ($statements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Safe to ignore duplicate-column and already-compatible schema errors.
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_certificates (
            certificate_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        )
    ");
}
