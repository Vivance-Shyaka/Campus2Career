<?php
/**
 * Campus2Career - Student Dashboard
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('student');
$pdo        = getDBConnection();
$student_id = $_SESSION['role_id'];
$user_id    = $_SESSION['user_id'];

// Student profile
$profile = $pdo->prepare("
    SELECT u.name, u.email, s.university, s.course, s.skills, s.student_id
    FROM users u JOIN students s ON s.user_id = u.user_id
    WHERE u.user_id = ?
");
$profile->execute([$user_id]);
$student = $profile->fetch();

// Stats
$stats = [
    'total'    => $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ?"),
    'pending'  => $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status='pending'"),
    'approved' => $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status='approved'"),
    'rejected' => $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status='rejected'"),
];
foreach ($stats as $key => $s) { $s->execute([$student_id]); $stats[$key] = $s->fetchColumn(); }

// Recent applications
$apps = $pdo->prepare("
    SELECT a.*, i.title, i.requirements, c.company_name, c.location
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN companies c ON c.company_id = i.company_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC LIMIT 5
");
$apps->execute([$student_id]);
$recentApps = $apps->fetchAll();

// Available internships not yet applied
$avail = $pdo->prepare("
    SELECT i.*, c.company_name, c.location
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    WHERE i.internship_id NOT IN (
        SELECT internship_id FROM applications WHERE student_id = ?
    )
    ORDER BY i.created_at DESC LIMIT 4
");
$avail->execute([$student_id]);
$available = $avail->fetchAll();

$pageTitle = 'Student Dashboard – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="dashboard-page">
<div class="dashboard-container">

    <div class="dashboard-header">
        <div class="d-flex justify-between align-center" style="flex-wrap:wrap; gap:12px;">
            <div>
                <h1>Welcome back, <?= htmlspecialchars(explode(' ', $student['name'])[0]) ?>! 👋</h1>
                <p><?= htmlspecialchars($student['university']) ?> · <?= htmlspecialchars($student['course']) ?></p>
            </div>
            <a href="internships.php" class="btn btn-blue"><i class="fas fa-search"></i> Browse Internships</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['total'] ?>"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Applications</div>
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
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $stats['rejected'] ?>"><?= $stats['rejected'] ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>

    <div class="page-grid wide">
        <!-- Recent Applications -->
        <div>
            <div class="content-card">
                <div class="content-card-header">
                    <h3><i class="fas fa-file-alt" style="color:var(--c-blue);margin-right:8px;"></i>Recent Applications</h3>
                    <a href="applications.php" style="font-size:0.85rem; color:var(--c-blue);">View All →</a>
                </div>
                <div class="content-card-body" style="padding:0;">
                    <?php if (empty($recentApps)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No applications yet</h3>
                        <p>Start browsing and apply for internships!</p>
                        <a href="internships.php" class="btn btn-blue mt-16">Browse Internships</a>
                    </div>
                    <?php else: ?>
                    <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentApps as $app): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app['title']) ?></strong>
                                <?php if ($app['interview_date']): ?>
                                <div class="interview-badge mt-8">
                                    <i class="fas fa-calendar-alt"></i>
                                    Interview: <?= date('M j, Y g:i A', strtotime($app['interview_date'])) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($app['company_name']) ?><br>
                                <span class="text-muted text-small"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['location']) ?></span>
                            </td>
                            <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                            <td class="text-muted text-small"><?= date('M j', strtotime($app['applied_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Available Internships -->
            <?php if (!empty($available)): ?>
            <div class="content-card mt-24">
                <div class="content-card-header">
                    <h3><i class="fas fa-briefcase" style="color:var(--c-blue);margin-right:8px;"></i>Recommended for You</h3>
                    <a href="internships.php" style="font-size:0.85rem; color:var(--c-blue);">View All →</a>
                </div>
                <div class="content-card-body">
                    <div class="internship-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                        <?php foreach ($available as $job): ?>
                        <div class="internship-card" style="padding:18px;">
                            <div class="ic-header">
                                <div class="ic-logo" style="width:38px;height:38px;font-size:1rem;"><i class="fas fa-building"></i></div>
                                <div>
                                    <div class="ic-title" style="font-size:0.9rem;"><?= htmlspecialchars($job['title']) ?></div>
                                    <div class="ic-company"><?= htmlspecialchars($job['company_name']) ?></div>
                                </div>
                            </div>
                            <div class="ic-tags">
                                <?php foreach (array_slice(explode(',', $job['requirements']), 0, 3) as $r): ?>
                                    <span class="ic-tag"><?= htmlspecialchars(trim($r)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <form action="../../controllers/internship_controller.php" method="POST" style="margin-top:10px;">
                                <input type="hidden" name="action" value="apply">
                                <input type="hidden" name="internship_id" value="<?= $job['internship_id'] ?>">
                                <button type="submit" class="btn btn-blue btn-sm btn-block">Apply Now</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Profile Sidebar -->
        <div>
            <div class="content-card">
                <div style="background:linear-gradient(135deg,var(--c-navy),var(--c-blue));padding:28px;text-align:center;color:white;">
                    <div class="sidebar-avatar" style="margin:0 auto 14px;"><?= strtoupper(substr($student['name'],0,1)) ?></div>
                    <div class="sidebar-name"><?= htmlspecialchars($student['name']) ?></div>
                    <div class="sidebar-role">Student</div>
                    <div style="margin-top:8px;"><span class="role-badge role-student"><?= htmlspecialchars($student['course'] ?: 'Student') ?></span></div>
                </div>
                <div class="content-card-body">
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div>
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--c-text-sub);font-weight:600;margin-bottom:4px;">University</div>
                            <div style="font-weight:500;"><?= htmlspecialchars($student['university'] ?: '—') ?></div>
                        </div>
                        <div class="divider" style="margin:4px 0;"></div>
                        <div>
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--c-text-sub);font-weight:600;margin-bottom:4px;">Course</div>
                            <div style="font-weight:500;"><?= htmlspecialchars($student['course'] ?: '—') ?></div>
                        </div>
                        <div class="divider" style="margin:4px 0;"></div>
                        <div>
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--c-text-sub);font-weight:600;margin-bottom:8px;">Skills</div>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                <?php foreach (explode(',', $student['skills'] ?: 'No skills listed') as $skill): ?>
                                    <span class="ic-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="divider" style="margin:4px 0;"></div>
                        <div>
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--c-text-sub);font-weight:600;margin-bottom:4px;">Email</div>
                            <div style="font-weight:500;font-size:0.88rem;"><?= htmlspecialchars($student['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="content-card mt-24">
                <div class="content-card-header"><h3><i class="fas fa-lightbulb" style="color:var(--c-warning);margin-right:8px;"></i>Quick Tips</h3></div>
                <div class="content-card-body">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="notif-item" style="border:none;padding:0;gap:10px;">
                            <div class="notif-icon notif-blue"><i class="fas fa-user-edit"></i></div>
                            <div class="notif-text">Keep your skills updated to get better matches</div>
                        </div>
                        <div class="notif-item" style="border:none;padding:0;gap:10px;">
                            <div class="notif-icon notif-green"><i class="fas fa-paper-plane"></i></div>
                            <div class="notif-text">Apply to multiple internships to increase your chances</div>
                        </div>
                        <div class="notif-item" style="border:none;padding:0;gap:10px;">
                            <div class="notif-icon notif-yellow"><i class="fas fa-bell"></i></div>
                            <div class="notif-text">Check your email for interview notifications</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php require_once '../../includes/footer.php'; ?>
