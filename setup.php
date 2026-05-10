<?php
/**
 * Campus2Career – Setup & Repair Script
 * Safe to run multiple times. Fixes passwords, creates tables, seeds data.
 * Access: http://localhost/campus2career/setup.php
 */
require_once 'config/database.php';
$pdo = getDBConnection();
$msgs = [];

// ── 1. Core tables ──────────────────────────────────────────────────────────
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','company','admin') NOT NULL,
    account_status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    university VARCHAR(100),
    course VARCHAR(100),
    skills TEXT,
    cv_file VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    company_name VARCHAR(100),
    location VARCHAR(100),
    description LONGTEXT NULL,
    website VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS internships (
    internship_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(150) NOT NULL,
    description LONGTEXT,
    responsibilities LONGTEXT,
    expectations LONGTEXT,
    internship_location VARCHAR(160),
    requirements LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    internship_id INT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    interview_date DATETIME NULL,
    interview_location VARCHAR(255) NULL,
    interview_notes TEXT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(internship_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('approval','rejection','interview','info') DEFAULT 'info',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS student_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
");
$msgs[] = "✅ Core tables verified";

// ── 2. Add columns if missing ────────────────────────────────────────────────
$alterCols = [
    ["ALTER TABLE users    ADD COLUMN account_status ENUM('active','disabled') DEFAULT 'active'"],
    ["ALTER TABLE students ADD COLUMN cv_file VARCHAR(255) NULL"],
    ["ALTER TABLE companies ADD COLUMN description LONGTEXT NULL"],
    ["ALTER TABLE companies ADD COLUMN website VARCHAR(255) NULL"],
    ["ALTER TABLE internships MODIFY description LONGTEXT"],
    ["ALTER TABLE internships MODIFY requirements LONGTEXT"],
    ["ALTER TABLE internships ADD COLUMN responsibilities LONGTEXT NULL AFTER description"],
    ["ALTER TABLE internships ADD COLUMN expectations LONGTEXT NULL AFTER responsibilities"],
    ["ALTER TABLE internships ADD COLUMN internship_location VARCHAR(160) NULL AFTER expectations"],
    ["ALTER TABLE applications ADD COLUMN interview_location VARCHAR(255) NULL AFTER interview_date"],
    ["ALTER TABLE applications ADD COLUMN interview_notes TEXT NULL AFTER interview_location"],
];
foreach ($alterCols as [$sql]) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* already exists */ }
}
$msgs[] = "✅ Column checks complete";

// ── 3. Seed users with hashed passwords ──────────────────────────────────────
$seedUsers = [
    'admin@c2c.com'      => ['Admin User',    'admin123', 'admin'],
    'john@student.com'   => ['John Student',  '123456',   'student'],
    'alice@student.com'  => ['Alice Student', '123456',   'student'],
    'mtn@company.com'    => ['MTN Rwanda',    '123456',   'company'],
    'irembo@company.com' => ['Irembo Ltd',    '123456',   'company'],
];

foreach ($seedUsers as $email => [$name, $plain, $role]) {
    $row = $pdo->prepare("SELECT user_id, password FROM users WHERE email=?");
    $row->execute([$email]);
    $existing = $row->fetch();
    if (!$existing) {
        $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
            ->execute([$name, $email, password_hash($plain, PASSWORD_DEFAULT), $role]);
        $msgs[] = "✅ Created: <strong>$email</strong>";
    } elseif (!password_verify($plain, $existing['password'])) {
        $pdo->prepare("UPDATE users SET password=? WHERE user_id=?")
            ->execute([password_hash($plain, PASSWORD_DEFAULT), $existing['user_id']]);
        $msgs[] = "🔑 Fixed password: <strong>$email</strong>";
    } else {
        $msgs[] = "✔ OK: <strong>$email</strong>";
    }
}

// ── 4. Role records ───────────────────────────────────────────────────────────
// Admin
$admin = $pdo->query("SELECT user_id FROM users WHERE role='admin' LIMIT 1")->fetch();
if ($admin) {
    $ex = $pdo->prepare("SELECT admin_id FROM admins WHERE user_id=?"); $ex->execute([$admin['user_id']]);
    if (!$ex->fetch()) { $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")->execute([$admin['user_id']]); $msgs[] = "✅ Admin record created"; }
}

// Students
$studentRows = $pdo->query("SELECT user_id, email FROM users WHERE role='student'")->fetchAll();
foreach ($studentRows as $s) {
    $ex = $pdo->prepare("SELECT student_id FROM students WHERE user_id=?"); $ex->execute([$s['user_id']]);
    if (!$ex->fetch()) {
        $isJohn = str_contains($s['email'], 'john');
        $pdo->prepare("INSERT INTO students (user_id,university,course,skills) VALUES (?,?,?,?)")
            ->execute([$s['user_id'], $isJohn?'AUCA':'University of Kigali', $isJohn?'Software Engineering':'Information Technology', $isJohn?'HTML, CSS, JavaScript':'PHP, MySQL']);
        $msgs[] = "✅ Student record for {$s['email']}";
    }
}

// Companies
$companyRows = $pdo->query("SELECT user_id, name FROM users WHERE role='company'")->fetchAll();
foreach ($companyRows as $co) {
    $ex = $pdo->prepare("SELECT company_id FROM companies WHERE user_id=?"); $ex->execute([$co['user_id']]);
    if (!$ex->fetch()) {
        $pdo->prepare("INSERT INTO companies (user_id,company_name,location,description) VALUES (?,?,?,?)")
            ->execute([$co['user_id'], $co['name'], 'Kigali', 'A verified employer using Campus2Career to discover skilled student talent.']);
        $msgs[] = "✅ Company record for {$co['name']}";
    }
}

// ── 5. Sample internships ────────────────────────────────────────────────────
if ($pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn() == 0) {
    $c1 = $pdo->query("SELECT company_id FROM companies LIMIT 1")->fetchColumn();
    $c2 = $pdo->query("SELECT company_id FROM companies LIMIT 1 OFFSET 1")->fetchColumn();
    if ($c1) {
        $pdo->prepare("INSERT INTO internships (company_id,title,description,requirements) VALUES (?,?,?,?)")
            ->execute([$c1,'Web Development Intern',
                "We are looking for a motivated Web Development Intern to join our engineering team.\n\nResponsibilities:\n- Develop and maintain frontend interfaces using HTML, CSS, and JavaScript\n- Collaborate with backend developers on PHP-based systems\n- Participate in code reviews and team meetings\n- Write clean, documented, and maintainable code\n\nWhat you will gain:\n- Real-world experience with production systems\n- Mentorship from senior engineers\n- A strong reference letter upon completion",
                'HTML, CSS, JavaScript, PHP, MySQL']);
        $msgs[] = "✅ Sample internship 1 added";
    }
    if ($c2) {
        $pdo->prepare("INSERT INTO internships (company_id,title,description,requirements) VALUES (?,?,?,?)")
            ->execute([$c2,'Backend Developer Intern',
                "Irembo Ltd is seeking a Backend Developer Intern to contribute to Rwanda's leading e-government platform.\n\nAbout the role:\n- Build and maintain RESTful APIs using PHP\n- Design and optimize MySQL database schemas\n- Implement security best practices\n- Work in an agile development environment\n\nIdeal candidate:\n- Studying Computer Science, Software Engineering, or related field\n- Passionate about clean code and backend systems\n- Eager to learn and contribute to real government digital services",
                'PHP, MySQL, REST APIs, Git']);
        $msgs[] = "✅ Sample internship 2 added";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Campus2Career – Setup</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0A0A0A;color:#E5E7EB;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
  .card{background:#111827;border:1px solid #1F2937;border-radius:16px;padding:36px;width:100%;max-width:640px;box-shadow:0 16px 48px rgba(0,0,0,.5);}
  .logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
  .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,#3B82F6,#6366F1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:white;}
  .logo-text{font-size:1.4rem;font-weight:800;color:#F9FAFB;}
  .logo-text span{color:#3B82F6;}
  h1{font-size:1.1rem;color:#9CA3AF;margin-bottom:20px;}
  .log{background:#0A0A0A;border:1px solid #1F2937;border-radius:10px;padding:16px;margin-bottom:24px;max-height:240px;overflow-y:auto;}
  .log-item{padding:6px 0;border-bottom:1px solid #1F2937;font-size:.85rem;color:#D1D5DB;}
  .log-item:last-child{border-bottom:none;}
  table{width:100%;border-collapse:collapse;font-size:.85rem;margin-bottom:24px;}
  th{text-align:left;padding:8px 12px;background:#1F2937;color:#6B7280;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;}
  td{padding:10px 12px;border-bottom:1px solid #1F2937;color:#D1D5DB;}
  tr:last-child td{border-bottom:none;}
  .tag{display:inline-block;padding:2px 9px;border-radius:50px;font-size:.72rem;font-weight:600;}
  .tag-admin{background:rgba(245,158,11,.15);color:#FCD34D;}
  .tag-student{background:rgba(59,130,246,.15);color:#93C5FD;}
  .tag-company{background:rgba(16,185,129,.15);color:#6EE7B7;}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#3B82F6;color:white;border-radius:8px;font-weight:700;text-decoration:none;font-size:.9rem;transition:.2s;}
  .btn:hover{background:#2563EB;}
  .note{font-size:.75rem;color:#6B7280;margin-top:16px;text-align:center;}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">🎓</div>
    <div class="logo-text">Campus<span>2</span>Career</div>
  </div>
  <h1>Setup &amp; Database Repair — Complete</h1>
  <div class="log">
    <?php foreach ($msgs as $m): ?>
    <div class="log-item"><?= $m ?></div>
    <?php endforeach; ?>
  </div>
  <table>
    <thead><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>
    <tbody>
      <tr><td><span class="tag tag-admin">Admin</span></td><td>admin@c2c.com</td><td>admin123</td></tr>
      <tr><td><span class="tag tag-student">Student</span></td><td>john@student.com</td><td>123456</td></tr>
      <tr><td><span class="tag tag-student">Student</span></td><td>alice@student.com</td><td>123456</td></tr>
      <tr><td><span class="tag tag-company">Company</span></td><td>mtn@company.com</td><td>123456</td></tr>
      <tr><td><span class="tag tag-company">Company</span></td><td>irembo@company.com</td><td>123456</td></tr>
    </tbody>
  </table>
  <a class="btn" href="index.php">🚀 Go to Campus2Career</a>
  <div class="note">Run this page anytime to repair passwords or reset sample data.</div>
</div>
</body>
</html>
