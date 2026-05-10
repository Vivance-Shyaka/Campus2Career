<?php
/**
 * Campus2Career - Landing Page
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'student') { header('Location: ' . BASE_URL . 'views/student/dashboard.php'); exit; }
    if ($role === 'company') { header('Location: ' . BASE_URL . 'views/company/dashboard.php'); exit; }
    if ($role === 'admin') { header('Location: ' . BASE_URL . 'views/admin/dashboard.php'); exit; }
}

$pdo = getDBConnection();
$stats = [
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'companies' => (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'internships' => (int)$pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn(),
    'applications' => (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
];
$recent = $pdo->query("
    SELECT i.title, i.requirements, i.created_at, c.company_name, COALESCE(NULLIF(i.internship_location,''), c.location) AS location
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    ORDER BY i.created_at DESC
    LIMIT 3
")->fetchAll();

$pageTitle = 'Campus2Career - Bridging Education and Employment';
require_once 'includes/header.php';
?>

<section class="startup-hero">
    <div class="startup-hero-overlay"></div>
    <div class="container startup-hero-inner">
        <div class="startup-hero-copy">
            <span class="startup-eyebrow">Skills-based internship and job matching</span>
            <h1>Bridging the Gap Between Education and Employment</h1>
            <p>
                Campus2Career helps students gain practical experience through virtual and on-site internships,
                while companies discover talent by skills, potential, and real application history instead of CVs alone.
            </p>
            <div class="startup-actions">
                <a href="register.php" class="btn btn-blue btn-lg"><i class="fas fa-rocket"></i> Get Started</a>
                <a href="views/public/internships.php" class="btn btn-light btn-lg"><i class="fas fa-search"></i> Explore Internships</a>
            </div>
        </div>
        <div class="startup-hero-panel" aria-label="Recent internship matches">
            <div class="startup-panel-head">
                <span>Live matching pipeline</span>
                <strong><?= number_format($stats['internships']) ?> open roles</strong>
            </div>
            <?php foreach ($recent as $job): ?>
            <div class="startup-job-row">
                <div class="startup-job-icon"><i class="fas fa-briefcase"></i></div>
                <div>
                    <strong><?= htmlspecialchars($job['title']) ?></strong>
                    <span><?= htmlspecialchars($job['company_name']) ?> - <?= htmlspecialchars($job['location']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm startup-problem">
    <div class="container">
        <div class="section-header">
            <h2>The graduate transition is still too difficult</h2>
            <p>Students need practical exposure, companies need proof of ability, and universities need clearer outcomes.</p>
        </div>
        <div class="problem-grid">
            <?php
            $problems = [
                ['fa-user-clock', 'Graduate unemployment', 'Many graduates leave campus without a direct path into meaningful work.'],
                ['fa-door-closed', 'Limited internships', 'Traditional placements are scarce, manual, and hard to track at scale.'],
                ['fa-file-lines', 'Outdated CV screening', 'Recruitment often rewards formatting more than actual skill evidence.'],
                ['fa-puzzle-piece', 'Skills gap', 'Employers need PHP, MySQL, communication, teamwork, and delivery readiness.'],
            ];
            foreach ($problems as [$icon, $title, $text]): ?>
            <div class="problem-card">
                <div class="problem-icon"><i class="fas <?= $icon ?>"></i></div>
                <h3><?= htmlspecialchars($title) ?></h3>
                <p><?= htmlspecialchars($text) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-header">
            <h2>How Campus2Career Works</h2>
            <p>One platform for students, employers, and administrators to manage the full internship lifecycle.</p>
        </div>
        <div class="timeline-grid">
            <div class="timeline-card">
                <span>Students</span>
                <h3>Create a skills profile</h3>
                <p>Upload CVs and certificates, browse roles, view full details, apply, and track decisions.</p>
            </div>
            <div class="timeline-card">
                <span>Companies</span>
                <h3>Post and review talent</h3>
                <p>Publish detailed roles, review applicant profiles, compare match scores, and schedule interviews.</p>
            </div>
            <div class="timeline-card">
                <span>Admins</span>
                <h3>Monitor platform outcomes</h3>
                <p>Manage users, internships, reports, analytics, and inappropriate content from one dashboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-sm startup-features">
    <div class="container">
        <div class="section-header">
            <h2>Built like a real recruitment system</h2>
            <p>Professional workflows for matching, communication, analytics, and document review.</p>
        </div>
        <div class="feature-grid">
            <?php
            $features = [
                ['fa-laptop-house', 'Virtual Internships'],
                ['fa-brain', 'Skill Matching'],
                ['fa-calendar-check', 'Interview Scheduling'],
                ['fa-envelope-open-text', 'Email Notifications'],
                ['fa-chart-line', 'Dashboard Analytics'],
                ['fa-bolt', 'Real-Time Applications'],
                ['fa-file-arrow-up', 'CV Uploads'],
                ['fa-award', 'Certificate Review'],
            ];
            foreach ($features as [$icon, $title]): ?>
            <div class="feature-mini-card"><i class="fas <?= $icon ?>"></i><span><?= htmlspecialchars($title) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm startup-stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div><div class="stat-info"><div class="stat-value" data-counter="<?= $stats['students'] ?>"><?= $stats['students'] ?></div><div class="stat-label">Students Registered</div></div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-building"></i></div><div class="stat-info"><div class="stat-value" data-counter="<?= $stats['companies'] ?>"><?= $stats['companies'] ?></div><div class="stat-label">Companies Registered</div></div></div>
            <div class="stat-card"><div class="stat-icon navy"><i class="fas fa-briefcase"></i></div><div class="stat-info"><div class="stat-value" data-counter="<?= $stats['internships'] ?>"><?= $stats['internships'] ?></div><div class="stat-label">Internships Posted</div></div></div>
            <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-file-alt"></i></div><div class="stat-info"><div class="stat-value" data-counter="<?= $stats['applications'] ?>"><?= $stats['applications'] ?></div><div class="stat-label">Applications Submitted</div></div></div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-header">
            <h2>Trusted by the people who need outcomes</h2>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <p>"I could finally show my PHP and MySQL skills instead of only sending a CV. The interview notification made the process feel real."</p>
                <strong>John M.</strong><span>Software Engineering Student</span>
            </div>
            <div class="testimonial-card">
                <p>"The applicant profile review page helps us compare skills, CVs, certificates, and match scores before deciding."</p>
                <strong>Hiring Manager</strong><span>Kigali Technology Company</span>
            </div>
            <div class="testimonial-card">
                <p>"The reports make it easy to explain placement progress and internship activity to academic leadership."</p>
                <strong>Admin Office</strong><span>University Career Services</span>
            </div>
        </div>
    </div>
</section>

<section class="startup-cta">
    <div class="container">
        <h2>Turn student potential into employable experience.</h2>
        <p>Join Campus2Career and help build a fairer, skills-first path from campus to work.</p>
        <div class="startup-actions">
            <a href="register.php" class="btn btn-blue btn-lg"><i class="fas fa-user-plus"></i> Join Platform</a>
            <a href="views/public/internships.php" class="btn btn-light btn-lg"><i class="fas fa-briefcase"></i> View Opportunities</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
