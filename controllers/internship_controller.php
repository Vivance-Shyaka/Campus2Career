<?php
/**
 * Campus2Career - Internship Controller
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/EmailService.php';
require_once __DIR__ . '/../models/Notification.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'post':
        postInternship();
        break;
    case 'edit':
        editInternship();
        break;
    case 'delete':
        deleteInternship();
        break;
    case 'apply':
        applyInternship();
        break;
    case 'update_status':
        updateStatus();
        break;
    case 'schedule':
        scheduleInterview();
        break;
    default:
        header('Location: ' . BASE_URL . 'index.php');
        exit;
}

function redirectBackToApplicants(?int $applicationId = null): void {
    $fallback = BASE_URL . 'views/company/applicants.php';
    if (!empty($_POST['return_to']) && $_POST['return_to'] === 'profile' && $applicationId) {
        header('Location: ' . BASE_URL . 'views/company/applicant_profile.php?id=' . $applicationId);
        exit;
    }
    header('Location: ' . $fallback);
    exit;
}

function cleanText(string $value): string {
    return trim(str_replace(["\r\n", "\r"], "\n", $value));
}

function postInternship(): void {
    requireRole('company');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'views/company/post_internship.php');
        exit;
    }

    $title = cleanText($_POST['title'] ?? '');
    $description = cleanText($_POST['description'] ?? '');
    $responsibilities = cleanText($_POST['responsibilities'] ?? '');
    $expectations = cleanText($_POST['expectations'] ?? '');
    $requirements = cleanText($_POST['requirements'] ?? '');
    $location = cleanText($_POST['internship_location'] ?? '');
    $companyId = (int)$_SESSION['role_id'];

    if ($title === '' || $description === '' || $requirements === '') {
        setFlash('error', 'Title, description, and required skills are required.');
        header('Location: ' . BASE_URL . 'views/company/post_internship.php');
        exit;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO internships
            (company_id, title, description, responsibilities, expectations, requirements, internship_location)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$companyId, $title, $description, $responsibilities, $expectations, $requirements, $location]);

    setFlash('success', 'Internship posted successfully.');
    header('Location: ' . BASE_URL . 'views/company/dashboard.php');
    exit;
}

function editInternship(): void {
    requireRole('company');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'views/company/dashboard.php');
        exit;
    }

    $id = (int)($_POST['internship_id'] ?? 0);
    $title = cleanText($_POST['title'] ?? '');
    $description = cleanText($_POST['description'] ?? '');
    $responsibilities = cleanText($_POST['responsibilities'] ?? '');
    $expectations = cleanText($_POST['expectations'] ?? '');
    $requirements = cleanText($_POST['requirements'] ?? '');
    $location = cleanText($_POST['internship_location'] ?? '');
    $companyId = (int)$_SESSION['role_id'];

    if (!$id || $title === '' || $description === '' || $requirements === '') {
        setFlash('error', 'Please complete the required internship fields.');
        header('Location: ' . BASE_URL . 'views/company/dashboard.php');
        exit;
    }

    $pdo = getDBConnection();
    $check = $pdo->prepare("SELECT internship_id FROM internships WHERE internship_id = ? AND company_id = ?");
    $check->execute([$id, $companyId]);
    if (!$check->fetch()) {
        setFlash('error', 'You are not allowed to edit that internship.');
        header('Location: ' . BASE_URL . 'views/company/dashboard.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE internships
        SET title = ?, description = ?, responsibilities = ?, expectations = ?, requirements = ?, internship_location = ?
        WHERE internship_id = ? AND company_id = ?
    ");
    $stmt->execute([$title, $description, $responsibilities, $expectations, $requirements, $location, $id, $companyId]);

    setFlash('success', 'Internship updated successfully.');
    header('Location: ' . BASE_URL . 'views/company/dashboard.php');
    exit;
}

function deleteInternship(): void {
    if (!isLoggedIn() || !in_array($_SESSION['role'] ?? '', ['company', 'admin'], true)) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    $pdo = getDBConnection();

    if ($_SESSION['role'] === 'company') {
        $stmt = $pdo->prepare("DELETE FROM internships WHERE internship_id = ? AND company_id = ?");
        $stmt->execute([$id, (int)$_SESSION['role_id']]);
        setFlash('success', 'Internship deleted.');
        header('Location: ' . BASE_URL . 'views/company/dashboard.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM internships WHERE internship_id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Internship deleted.');
    header('Location: ' . BASE_URL . 'views/admin/internships.php');
    exit;
}

function applyInternship(): void {
    requireRole('student');

    $internshipId = (int)($_POST['internship_id'] ?? 0);
    $studentId = (int)$_SESSION['role_id'];

    if (!$internshipId || !$studentId) {
        setFlash('error', 'Invalid application request.');
        header('Location: ' . BASE_URL . 'views/student/internships.php');
        exit;
    }

    $pdo = getDBConnection();

    $job = $pdo->prepare("
        SELECT i.internship_id, i.title, c.company_name, c.user_id AS company_user_id,
               co.email AS company_email, co.name AS company_contact,
               st.name AS student_name
        FROM internships i
        JOIN companies c ON c.company_id = i.company_id
        JOIN users co ON co.user_id = c.user_id
        JOIN students s ON s.student_id = ?
        JOIN users st ON st.user_id = s.user_id
        WHERE i.internship_id = ?
    ");
    $job->execute([$studentId, $internshipId]);
    $info = $job->fetch();
    if (!$info) {
        setFlash('error', 'Internship not found.');
        header('Location: ' . BASE_URL . 'views/student/internships.php');
        exit;
    }

    $check = $pdo->prepare("SELECT application_id FROM applications WHERE student_id = ? AND internship_id = ?");
    $check->execute([$studentId, $internshipId]);
    if ($check->fetch()) {
        setFlash('error', 'You have already applied for this internship.');
        header('Location: ' . BASE_URL . 'views/student/internship_details.php?id=' . $internshipId);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO applications (student_id, internship_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$studentId, $internshipId]);

    $notif = new Notification($pdo);
    $notif->create(
        (int)$info['company_user_id'],
        'info',
        'New application from ' . $info['student_name'] . ' for "' . $info['title'] . '".'
    );

    $email = new EmailService();
    $email->sendNewApplication(
        $info['company_email'],
        $info['company_name'] ?: $info['company_contact'],
        $info['student_name'],
        $info['title']
    );

    setFlash('success', 'Application submitted successfully.');
    header('Location: ' . BASE_URL . 'views/student/applications.php');
    exit;
}

function loadOwnedApplication(PDO $pdo, int $applicationId, int $companyId): ?array {
    $stmt = $pdo->prepare("
        SELECT a.*, i.title AS internship_title, c.company_name,
               s.student_id, s.user_id AS student_user_id,
               u.email AS student_email, u.name AS student_name
        FROM applications a
        JOIN internships i ON i.internship_id = a.internship_id
        JOIN companies c ON c.company_id = i.company_id
        JOIN students s ON s.student_id = a.student_id
        JOIN users u ON u.user_id = s.user_id
        WHERE a.application_id = ? AND i.company_id = ?
    ");
    $stmt->execute([$applicationId, $companyId]);
    $app = $stmt->fetch();
    return $app ?: null;
}

function updateStatus(): void {
    requireRole('company');

    $applicationId = (int)($_POST['application_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!$applicationId || !in_array($status, ['approved', 'rejected'], true)) {
        setFlash('error', 'Invalid application status.');
        redirectBackToApplicants($applicationId);
    }

    $pdo = getDBConnection();
    $app = loadOwnedApplication($pdo, $applicationId, (int)$_SESSION['role_id']);
    if (!$app) {
        setFlash('error', 'Unauthorized application action.');
        redirectBackToApplicants($applicationId);
    }

    $pdo->prepare("UPDATE applications SET status = ? WHERE application_id = ?")->execute([$status, $applicationId]);

    $email = new EmailService();
    $notif = new Notification($pdo);
    $companyName = $app['company_name'] ?: ($_SESSION['name'] ?? 'The company');

    if ($status === 'approved') {
        $email->sendApproval($app['student_email'], $app['student_name'], $app['internship_title'], $companyName);
        $notif->create(
            (int)$app['student_user_id'],
            'approval',
            'Your application for "' . $app['internship_title'] . '" has been approved.'
        );
    } else {
        $email->sendRejection($app['student_email'], $app['student_name'], $app['internship_title'], $companyName);
        $notif->create(
            (int)$app['student_user_id'],
            'rejection',
            'Your application for "' . $app['internship_title'] . '" was not selected this time.'
        );
    }

    setFlash('success', 'Application ' . $status . '.');
    redirectBackToApplicants($applicationId);
}

function scheduleInterview(): void {
    requireRole('company');

    $applicationId = (int)($_POST['application_id'] ?? 0);
    $interviewDate = trim($_POST['interview_date'] ?? '');
    $location = cleanText($_POST['interview_location'] ?? '');
    $notes = cleanText($_POST['interview_notes'] ?? '');

    if (!$applicationId || $interviewDate === '') {
        setFlash('error', 'Please provide an interview date and time.');
        redirectBackToApplicants($applicationId);
    }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $interviewDate);
    if (!$dt) {
        setFlash('error', 'Invalid date/time format.');
        redirectBackToApplicants($applicationId);
    }
    $formatted = $dt->format('Y-m-d H:i:s');

    $pdo = getDBConnection();
    $app = loadOwnedApplication($pdo, $applicationId, (int)$_SESSION['role_id']);
    if (!$app) {
        setFlash('error', 'Unauthorized interview scheduling request.');
        redirectBackToApplicants($applicationId);
    }

    $stmt = $pdo->prepare("
        UPDATE applications
        SET interview_date = ?, interview_location = ?, interview_notes = ?, status = 'approved'
        WHERE application_id = ?
    ");
    $stmt->execute([$formatted, $location, $notes, $applicationId]);

    $companyName = $app['company_name'] ?: ($_SESSION['name'] ?? 'The company');
    $email = new EmailService();
    $email->sendInterviewScheduled(
        $app['student_email'],
        $app['student_name'],
        $app['internship_title'],
        $companyName,
        $formatted,
        $location,
        $notes
    );

    $notif = new Notification($pdo);
    $message = 'Interview scheduled for "' . $app['internship_title'] . '" on ' . $dt->format('M j, Y \a\t g:i A');
    if ($location !== '') {
        $message .= ' at ' . $location;
    }
    $notif->create((int)$app['student_user_id'], 'interview', $message . '.');

    setFlash('success', 'Interview scheduled for ' . $dt->format('M j, Y \a\t g:i A') . '.');
    redirectBackToApplicants($applicationId);
}
