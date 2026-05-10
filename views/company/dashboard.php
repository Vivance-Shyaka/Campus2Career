<?php
/**
 * Campus2Career - Company Dashboard
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('company');
$pdo        = getDBConnection();
$company_id = $_SESSION['role_id'];
$user_id    = $_SESSION['user_id'];

// Company profile
$profile = $pdo->prepare("
    SELECT u.name, u.email, c.company_name, c.location
    FROM users u JOIN companies c ON c.user_id = u.user_id
    WHERE u.user_id = ?
");
$profile->execute([$user_id]);
$company = $profile->fetch();

// Stats
$totalInternships  = $pdo->prepare("SELECT COUNT(*) FROM internships WHERE company_id = ?");
$totalInternships->execute([$company_id]);
$totalInternships  = $totalInternships->fetchColumn();

$totalApplicants   = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    WHERE i.company_id = ?
");
$totalApplicants->execute([$company_id]);
$totalApplicants = $totalApplicants->fetchColumn();

$pendingApplicants = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    WHERE i.company_id = ? AND a.status = 'pending'
");
$pendingApplicants->execute([$company_id]);
$pendingApplicants = $pendingApplicants->fetchColumn();

$approvedApplicants = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    WHERE i.company_id = ? AND a.status = 'approved'
");
$approvedApplicants->execute([$company_id]);
$approvedApplicants = $approvedApplicants->fetchColumn();

// Internships list
$internStmt = $pdo->prepare("
    SELECT i.*,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.internship_id) AS total_apps,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.internship_id AND status='pending') AS pending_apps
    FROM internships i
    WHERE i.company_id = ?
    ORDER BY i.created_at DESC
");
$internStmt->execute([$company_id]);
$internships = $internStmt->fetchAll();

// Recent applicants
$recentApps = $pdo->prepare("
    SELECT a.*, i.title, u.name AS student_name, u.email AS student_email,
           s.university, s.course, s.skills
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN students s ON s.student_id = a.student_id
    JOIN users u ON u.user_id = s.user_id
    WHERE i.company_id = ?
    ORDER BY a.applied_at DESC LIMIT 5
");
$recentApps->execute([$company_id]);
$recentApplicants = $recentApps->fetchAll();

$pageTitle = 'Company Dashboard – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="dashboard-page">
<div class="dashboard-container">

    <div class="dashboard-header">
        <div class="d-flex justify-between align-center" style="flex-wrap:wrap; gap:12px;">
            <div>
                <h1>Welcome, <?= htmlspecialchars($company['company_name'] ?: $company['name']) ?>! 🏢</h1>
                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($company['location'] ?: 'Location not set') ?></p>
            </div>
            <a href="post_internship.php" class="btn btn-blue"><i class="fas fa-plus"></i> Post Internship</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $totalInternships ?>"><?= $totalInternships ?></div>
                <div class="stat-label">Active Postings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $totalApplicants ?>"><?= $totalApplicants ?></div>
                <div class="stat-label">Total Applicants</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $pendingApplicants ?>"><?= $pendingApplicants ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value" data-counter="<?= $approvedApplicants ?>"><?= $approvedApplicants ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
    </div>

    <div class="page-grid wide">
        <!-- Main Content -->
        <div>
            <!-- Internships -->
            <div class="content-card mb-24">
                <div class="content-card-header">
                    <h3><i class="fas fa-briefcase" style="color:var(--c-blue);margin-right:8px;"></i>Your Internship Postings</h3>
                    <a href="post_internship.php" class="btn btn-blue btn-sm"><i class="fas fa-plus"></i> New Post</a>
                </div>
                <div class="content-card-body" style="padding:0;">
                    <?php if (empty($internships)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No internships posted yet</h3>
                        <p>Post your first internship to start receiving applications!</p>
                        <a href="post_internship.php" class="btn btn-blue mt-16">Post Internship</a>
                    </div>
                    <?php else: ?>
                    <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Applicants</th>
                                <th>Pending</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($internships as $intern): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($intern['title']) ?></strong><br>
                                <span class="text-small text-muted"><?= htmlspecialchars(mb_substr($intern['requirements'] ?? '', 0, 80)) ?><?= mb_strlen($intern['requirements'] ?? '') > 80 ? '...' : '' ?></span>
                            </td>
                            <td><span class="badge badge-approved"><?= $intern['total_apps'] ?> total</span></td>
                            <td><span class="badge badge-pending"><?= $intern['pending_apps'] ?> pending</span></td>
                            <td class="text-muted text-small"><?= date('M j, Y', strtotime($intern['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button type="button"
                                            class="btn btn-outline btn-sm internship-edit-btn"
                                            data-id="<?= (int)$intern['internship_id'] ?>"
                                            data-title="<?= htmlspecialchars($intern['title'], ENT_QUOTES) ?>"
                                            data-description="<?= htmlspecialchars($intern['description'] ?? '', ENT_QUOTES) ?>"
                                            data-responsibilities="<?= htmlspecialchars($intern['responsibilities'] ?? '', ENT_QUOTES) ?>"
                                            data-expectations="<?= htmlspecialchars($intern['expectations'] ?? '', ENT_QUOTES) ?>"
                                            data-requirements="<?= htmlspecialchars($intern['requirements'] ?? '', ENT_QUOTES) ?>"
                                            data-location="<?= htmlspecialchars($intern['internship_location'] ?? '', ENT_QUOTES) ?>">
                                        Edit
                                    </button>
                                    <a href="../../controllers/internship_controller.php?action=delete&id=<?= $intern['internship_id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       data-confirm="Delete this internship and all its applications?">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Applicants -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3><i class="fas fa-users" style="color:var(--c-blue);margin-right:8px;"></i>Recent Applicants</h3>
                    <a href="applicants.php" style="font-size:0.85rem; color:var(--c-blue);">View All →</a>
                </div>
                <div class="content-card-body" style="padding:0;">
                    <?php if (empty($recentApplicants)): ?>
                    <div class="empty-state"><i class="fas fa-users"></i><h3>No applicants yet</h3></div>
                    <?php else: ?>
                    <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr><th>Student</th><th>Position</th><th>Skills</th><th>Status</th><th>Applied</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentApplicants as $app): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app['student_name']) ?></strong><br>
                                <span class="text-small text-muted"><?= htmlspecialchars($app['university']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($app['title']) ?></td>
                            <td><span class="text-small text-muted"><?= htmlspecialchars(mb_substr($app['skills'], 0, 40)) ?></span></td>
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
        </div>

        <!-- Sidebar -->
        <div>
            <div class="content-card">
                <div style="background:linear-gradient(135deg,var(--c-navy),var(--c-blue));padding:28px;text-align:center;color:white;">
                    <div class="sidebar-avatar" style="margin:0 auto 14px;font-size:1.4rem;"><i class="fas fa-building"></i></div>
                    <div class="sidebar-name"><?= htmlspecialchars($company['company_name']) ?></div>
                    <div class="sidebar-role"><?= htmlspecialchars($company['location']) ?></div>
                    <div style="margin-top:8px;"><span class="role-badge role-company">Company</span></div>
                </div>
                <div class="content-card-body">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div>
                            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--c-text-sub);font-weight:600;margin-bottom:4px;">Contact Email</div>
                            <div style="font-weight:500;font-size:0.88rem;"><?= htmlspecialchars($company['email']) ?></div>
                        </div>
                        <div class="divider" style="margin:4px 0;"></div>
                        <a href="post_internship.php" class="btn btn-blue btn-block"><i class="fas fa-plus"></i> Post New Internship</a>
                        <a href="applicants.php" class="btn btn-outline btn-block"><i class="fas fa-users"></i> Manage Applicants</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Internship</h3>
            <button class="modal-close" onclick="closeModal('editModal')">×</button>
        </div>
        <form action="../../controllers/internship_controller.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="internship_id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control textarea-large" rows="7" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Responsibilities</label>
                    <textarea name="responsibilities" id="edit_responsibilities" class="form-control textarea-large" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Expectations</label>
                    <textarea name="expectations" id="edit_expectations" class="form-control textarea-large" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Requirements</label>
                    <textarea name="requirements" id="edit_requirements" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Work Location</label>
                    <input type="text" name="internship_location" id="edit_location" class="form-control" placeholder="Kigali, Remote, Hybrid">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-blue">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModalFromButton(btn) {
    document.getElementById('edit_id').value = btn.dataset.id || '';
    document.getElementById('edit_title').value = btn.dataset.title || '';
    document.getElementById('edit_description').value = btn.dataset.description || '';
    document.getElementById('edit_responsibilities').value = btn.dataset.responsibilities || '';
    document.getElementById('edit_expectations').value = btn.dataset.expectations || '';
    document.getElementById('edit_requirements').value = btn.dataset.requirements || '';
    document.getElementById('edit_location').value = btn.dataset.location || '';
    openModal('editModal');
}
document.querySelectorAll('.internship-edit-btn').forEach(btn => {
    btn.addEventListener('click', () => openEditModalFromButton(btn));
});
</script>

<?php require_once '../../includes/footer.php'; ?>
