<?php
/**
 * Campus2Career - Company: Manage Applicants
 * Fixed: Schedule Interview button always visible (not hidden behind selection)
 * Fixed: Interview scheduled box uses dark-mode-safe CSS variables
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/SkillMatcher.php';

requireRole('company');
$pdo        = getDBConnection();
$company_id = $_SESSION['role_id'];

$filterStatus = $_GET['status'] ?? 'all';
$filterIntern = (int)($_GET['internship_id'] ?? 0);

$params = [$company_id];
$where  = "WHERE i.company_id = ?";
if ($filterStatus !== 'all') {
    $where  .= " AND a.status = ?";
    $params[] = $filterStatus;
}
if ($filterIntern) {
    $where  .= " AND i.internship_id = ?";
    $params[] = $filterIntern;
}

$stmt = $pdo->prepare("
    SELECT a.*, i.title AS internship_title, i.internship_id, i.requirements,
           u.name AS student_name, u.email AS student_email,
           s.university, s.course, s.skills, s.student_id, s.cv_file
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN students s ON s.student_id = a.student_id
    JOIN users u ON u.user_id = s.user_id
    $where
    ORDER BY a.applied_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Internships for filter dropdown
$internships = $pdo->prepare("SELECT internship_id, title FROM internships WHERE company_id = ? ORDER BY title");
$internships->execute([$company_id]);
$internList = $internships->fetchAll();

// Status counts
$countStmt = $pdo->prepare("
    SELECT a.status, COUNT(*) as cnt
    FROM applications a JOIN internships i ON i.internship_id = a.internship_id
    WHERE i.company_id = ? GROUP BY a.status
");
$countStmt->execute([$company_id]);
$statusCounts = array_column($countStmt->fetchAll(), 'cnt', 'status');
$totalCount   = array_sum($statusCounts);

$pageTitle = 'Manage Applicants – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Manage Applicants</h1>
            <p>Review, approve, reject and schedule interviews</p>
        </div>
        <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<section class="section-sm">
<div class="container">

    <!-- Filter Bar -->
    <form method="GET" class="filter-bar">
        <div class="form-group" style="min-width:220px;">
            <label class="form-label">Filter by Internship</label>
            <select name="internship_id" class="form-control" onchange="this.form.submit()">
                <option value="">All Internships</option>
                <?php foreach ($internList as $i): ?>
                <option value="<?= $i['internship_id'] ?>" <?= $filterIntern == $i['internship_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($i['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
    </form>

    <!-- Status Tabs -->
    <div class="applicant-tabs">
        <?php
        $tabs = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
        foreach ($tabs as $val => $label):
            $cnt    = $val === 'all' ? $totalCount : ($statusCounts[$val] ?? 0);
            $active = $filterStatus === $val;
        ?>
        <a href="?status=<?= $val ?><?= $filterIntern ? '&internship_id='.$filterIntern : '' ?>"
           class="applicant-tab <?= $active ? 'applicant-tab-active' : '' ?>">
            <?= $label ?>
            <span class="applicant-tab-count <?= $active ? 'applicant-tab-count-active' : '' ?>"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($applications)): ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h3>No applicants <?= $filterStatus !== 'all' ? "with status \"$filterStatus\"" : 'yet' ?></h3>
        <p>Post internships to start receiving applications.</p>
    </div>

    <?php else: ?>
    <div class="applicant-list">
        <?php foreach ($applications as $app): ?>
        <div class="applicant-card">

            <!-- ── TOP ROW: avatar + info + status + actions ── -->
            <div class="applicant-card-top">

                <!-- Avatar + student details -->
                <div class="applicant-left">
                    <div class="applicant-avatar">
                        <?= strtoupper(substr($app['student_name'], 0, 1)) ?>
                    </div>
                    <div class="applicant-info">
                        <h3 class="applicant-name"><?= htmlspecialchars($app['student_name']) ?></h3>
                        <div class="applicant-meta">
                            <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($app['student_email']) ?></span>
                            <span><i class="fas fa-university"></i> <?= htmlspecialchars($app['university']) ?></span>
                            <span><i class="fas fa-book"></i> <?= htmlspecialchars($app['course']) ?></span>
                        </div>
                        <div class="applicant-position">
                            <i class="fas fa-briefcase"></i> Applied for:
                            <strong><?= htmlspecialchars($app['internship_title']) ?></strong>
                        </div>
                        <?php $match = SkillMatcher::match($app['skills'] ?? '', $app['requirements'] ?? ''); ?>
                        <div class="skill-match-bar" style="max-width:360px;">
                            <div class="smb-header">
                                <span class="smb-label"><?= htmlspecialchars(SkillMatcher::label($match['percent'])) ?></span>
                                <span class="smb-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</span>
                            </div>
                            <div class="smb-track"><div class="smb-fill" style="width:<?= $match['percent'] ?>%;background:<?= SkillMatcher::color($match['percent']) ?>"></div></div>
                        </div>
                        <?php if ($app['skills']): ?>
                        <div class="ic-tags" style="margin-top:8px;">
                            <?php foreach (array_slice(explode(',', $app['skills']), 0, 5) as $skill): ?>
                                <span class="ic-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status badge + date + action buttons -->
                <div class="applicant-right">
                    <span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                    <span class="applicant-date"><i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($app['applied_at'])) ?></span>
                    <a href="applicant_profile.php?id=<?= (int)$app['application_id'] ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-user"></i> View Profile
                    </a>

                    <!-- Approve / Reject (only when pending) -->
                    <?php if ($app['status'] === 'pending'): ?>
                    <div class="applicant-actions">
                        <form action="../../controllers/internship_controller.php" method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form action="../../controllers/internship_controller.php" method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Reject this application? The student will be notified.">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- ── SCHEDULE INTERVIEW BUTTON — always visible when approved ── -->
                    <?php if ($app['status'] === 'approved'): ?>
                    <button class="btn btn-schedule btn-sm"
                            onclick="openScheduleModal(<?= (int)$app['application_id'] ?>, '<?= htmlspecialchars(addslashes($app['student_name'])) ?>')">
                        <i class="fas fa-calendar-alt"></i>
                        <?= $app['interview_date'] ? 'Reschedule' : 'Schedule Interview' ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── INTERVIEW SCHEDULED BANNER — dark-mode safe ── -->
            <?php if ($app['interview_date']): ?>
            <div class="interview-scheduled-banner">
                <div class="isb-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="isb-body">
                    <div class="isb-title">Interview Scheduled</div>
                    <div class="isb-datetime">
                        <i class="fas fa-clock"></i>
                        <?= date('l, F j, Y', strtotime($app['interview_date'])) ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-hourglass-start"></i>
                        <?= date('g:i A', strtotime($app['interview_date'])) ?>
                    </div>
                    <?php if (!empty($app['interview_location'])): ?>
                    <div class="isb-note"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['interview_location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="isb-badge">Confirmed</div>
            </div>
            <?php endif; ?>

        </div><!-- /.applicant-card -->
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</section>

<!-- ── Schedule Interview Modal ── -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:8px;"></i>Schedule Interview</h3>
            <button class="modal-close" onclick="closeModal('scheduleModal')">×</button>
        </div>
        <form action="../../controllers/internship_controller.php" method="POST" data-validate>
            <div class="modal-body">
                <input type="hidden" name="action" value="schedule">
                <input type="hidden" name="application_id" id="schedule_app_id">

                <div class="schedule-for-badge">
                    <i class="fas fa-user-graduate"></i>
                    Scheduling for: <strong id="schedule_student_name"></strong>
                </div>

                <div class="form-group" style="margin-bottom:0; margin-top:20px;">
                    <label class="form-label">Interview Date &amp; Time <span style="color:var(--c-danger)">*</span></label>
                    <input type="datetime-local" name="interview_date" class="form-control" required
                           min="<?= date('Y-m-d\TH:i') ?>">
                    <span class="form-hint"><i class="fas fa-info-circle"></i> The student will receive an email notification automatically.</span>
                </div>
                <div class="form-group" style="margin-bottom:0; margin-top:16px;">
                    <label class="form-label">Location / Meeting Link</label>
                    <input type="text" name="interview_location" class="form-control" placeholder="Office address, campus room, Zoom link, Google Meet...">
                </div>
                <div class="form-group" style="margin-bottom:0; margin-top:16px;">
                    <label class="form-label">Notes for Student</label>
                    <textarea name="interview_notes" class="form-control" rows="4" placeholder="What to prepare, documents to bring, interview panel..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('scheduleModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-blue">
                    <i class="fas fa-calendar-check"></i> Schedule &amp; Notify Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal(appId, studentName) {
    document.getElementById('schedule_app_id').value = appId;
    document.getElementById('schedule_student_name').textContent = studentName;
    openModal('scheduleModal');
}
</script>

<?php require_once '../../includes/footer.php'; ?>
