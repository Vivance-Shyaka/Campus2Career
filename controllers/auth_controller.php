<?php
/**
 * Campus2Career - Auth Controller
 * Handles: login, register, logout
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':    handleLogin();    break;
    case 'register': handleRegister(); break;
    case 'logout':   handleLogout();   break;
    default:
        header('Location: ' . BASE_URL . 'login.php');
        exit;
}

// ──────────────────────────────────────────
function handleLogin() {
    /**
     * Handles user login by validating credentials and setting session.
     * Redirects to appropriate dashboard based on user role.
     */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('error', 'Please fill in all fields.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email format.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        setFlash('error', 'Invalid email or password.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Block disabled accounts
    if (($user['account_status'] ?? 'active') === 'disabled') {
        setFlash('error', 'Your account has been disabled. Please contact the administrator.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    session_regenerate_id(true);

    // Set base session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    // Get role-specific ID
    switch ($user['role']) {
        case 'student':
            $s = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
            $s->execute([$user['user_id']]);
            $row = $s->fetch();
            $_SESSION['role_id'] = $row ? $row['student_id'] : null;
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            header('Location: ' . BASE_URL . 'views/student/dashboard.php');
            break;

        case 'company':
            $c = $pdo->prepare("SELECT company_id FROM companies WHERE user_id = ?");
            $c->execute([$user['user_id']]);
            $row = $c->fetch();
            $_SESSION['role_id'] = $row ? $row['company_id'] : null;
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            header('Location: ' . BASE_URL . 'views/company/dashboard.php');
            break;

        case 'admin':
            $a = $pdo->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
            $a->execute([$user['user_id']]);
            $row = $a->fetch();
            $_SESSION['role_id'] = $row ? $row['admin_id'] : null;
            setFlash('success', 'Welcome, Admin!');
            header('Location: ' . BASE_URL . 'views/admin/dashboard.php');
            break;
    }
    exit;
}

// ──────────────────────────────────────────
function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // ── Validation ──
    $errors = [];
    if (empty($name))     $errors[] = 'Full name is required.';
    if (empty($email))    $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)  $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['student', 'company'])) $errors[] = 'Invalid role selected.';

    // Role-specific fields
    if ($role === 'student') {
        if (empty($_POST['university'])) $errors[] = 'University is required.';
        if (empty($_POST['course']))     $errors[] = 'Course is required.';
    }
    if ($role === 'company') {
        if (empty($_POST['company_name'])) $errors[] = 'Company name is required.';
        if (empty($_POST['location']))     $errors[] = 'Location is required.';
    }

    if (!empty($errors)) {
        setFlash('error', implode(' | ', $errors));
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }

    $pdo = getDBConnection();

    // Check duplicate email
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        setFlash('error', 'An account with this email already exists.');
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }

    // Insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt   = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed, $role]);
    $userId = $pdo->lastInsertId();

    // Insert role record
    if ($role === 'student') {
        $university = trim($_POST['university'] ?? '');
        $course     = trim($_POST['course'] ?? '');
        $skills     = trim($_POST['skills'] ?? '');
        $s = $pdo->prepare("INSERT INTO students (user_id, university, course, skills) VALUES (?, ?, ?, ?)");
        $s->execute([$userId, $university, $course, $skills]);
    } elseif ($role === 'company') {
        $cname    = trim($_POST['company_name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $c = $pdo->prepare("INSERT INTO companies (user_id, company_name, location) VALUES (?, ?, ?)");
        $c->execute([$userId, $cname, $location]);
    }

    setFlash('success', 'Account created successfully! Please log in.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// ──────────────────────────────────────────
function handleLogout() {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
