<?php
/**
 * Campus2Career - Admin Dashboard
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');
$pdo = getDBConnection();

// Stats
$stats = [
    'students'     => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'companies'    => $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'internships'  => $pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn(),
    'applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'pending'      => $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
    'approved'     => $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn(),
];

// Recent users
$recentUsers = $pdo->query("
    SELECT * FROM users ORDER BY created_at DESC LIMIT 8
")->fetchAll();

// Recent applications
$recentApps = $pdo->query("
    SELECT a.*, i.title, u.name AS student_name, c.company_name
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN students s ON s.student_id = a.student_id
    JOIN users u ON u.user_id = s.user_id
    JOIN companies c ON c.company_id = i.company_id
    ORDER BY a.applied_at DESC LIMIT 8
")->fetchAll();

$pageTitle = 'Admin Dashboard – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="dashboard-page">
<div class="dashboard-container">

    <div class="dashboard-header">
        <div class="d-flex justify-between align-center" style="flex-wrap:wrap; gap:12px;">
            <div>
                <h1>Admin Dashboard 🛡️</h1>
                <p>Manage the entire Campus2Career platform</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
                <a href="internships.php" class="btn btn-outline"><i class="fas fa-briefcase"></i> Internships</a>
                <a href="reports.php" class="btn btn-blue"><i class="fas fa-chart-bar"></i> View Reports</a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['students'] ?>"><?= $stats['students'] ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-building"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['companies'] ?>"><?= $stats['companies'] ?></div>
                <div class="stat-label">Companies</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fas fa-briefcase"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['internships'] ?>"><?= $stats['internships'] ?></div>
                <div class="stat-label">Internships</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['applications'] ?>"><?= $stats['applications'] ?></div>
                <div class="stat-label">Applications</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['pending'] ?>"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['approved'] ?>"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
    </div>

    <div class="page-grid">
        <!-- Recent Users -->
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-users" style="color:var(--c-blue);margin-right:8px;"></i>Recent Users</h3>
                <a href="users.php" style="font-size:0.85rem;color:var(--c-blue);">View All →</a>
            </div>
            <div class="content-card-body" style="padding:0;">
                <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Name</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                            <span class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></span>
                        </td>
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td class="text-muted text-small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-file-alt" style="color:var(--c-blue);margin-right:8px;"></i>Recent Applications</h3>
            </div>
            <div class="content-card-body" style="padding:0;">
                <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Student</th><th>Position</th><th>Company</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentApps as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['student_name']) ?></td>
                        <td class="text-small"><?= htmlspecialchars($app['title']) ?></td>
                        <td class="text-small text-muted"><?= htmlspecialchars($app['company_name']) ?></td>
                        <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php require_once '../../includes/footer.php'; ?>
