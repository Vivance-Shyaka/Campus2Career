<?php
/**
 * Campus2Career – Company: View Student Applicant Profile
 * Full profile review with approve/reject/schedule actions
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/SkillMatcher.php';

requireRole('company');

$app_id     = (int)($_GET['app_id'] ?? 0);
$company_id = $_SESSION['role_id'];
$pdo        = getDBConnection();

if (!$app_id) {
    setFlash('error', 'Invalid application.');
    header('Location: applicants.php'); exit;
}

// Fetch application + student + internship — verify company ownership
$stmt = $pdo->prepare("
    SELECT
        a.application_id, a.status, a.interview_date, a.applied_at,
        i.title          AS internship_title,
        i.requirements   AS internship_requirements,
        i.internship_id,
        s.student_id, s.university, s.course, s.skills, s.cv_file,
        u.name           AS student_name,
        u.email          AS student_email,
        u.created_at     AS member_since
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN companies   c ON c.company_id   = i.company_id
    JOIN students    s ON s.student_id   = a.student_id
    JOIN users       u ON u.user_id      = s.user_id
    WHERE a.application_id = ? AND c.company_id = ?
");
$stmt->execute([$app_id, $company_id]);
$data = $stmt->fetch();

if (!$data) {
    setFlash('error', 'Application not found or access denied.');
    header('Location: applicants.php'); exit;
}

// Skill match
$match = SkillMatcher::match($data['skills'] ?? '', $data['internship_requirements'] ?? '');

// Other applications from same student (at this company)
$otherApps = $pdo->prepare("
    SELECT a.status, a.applied_at, i.title
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    WHERE a.student_id = ? AND i.company_id = ? AND a.application_id != ?
    ORDER BY a.applied_at DESC LIMIT 5
");
$otherApps->execute([$data['student_id'], $company_id, $app_id]);
$otherApplications = $otherApps->fetchAll();

$pageTitle = 'Applicant Profile – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <div class="breadcrumb" style="margin-bottom:8px;">
                <a href="dashboard.php">Dashboard</a>
                <span class="sep">›</span>
                <a href="applicants.php">Applicants</a>
                <span class="sep">›</span>
                <span><?= htmlspecialchars($data['student_name']) ?></span>
            </div>
            <h1>Applicant Profile</h1>
            <p>Reviewing application for <strong style="color:rgba(255,255,255,.9)"><?= htmlspecialchars($data['internship_title']) ?></strong></p>
        </div>
        <a href="applicants.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Back to Applicants</a>
    </div>
</div>

<section class="section-sm">
<div class="container">
<div class="ap-layout">

    <!-- ══ LEFT: Student profile ══ -->
    <div class="ap-main">

        <!-- Identity card -->
        <div class="ap-profile-card">
            <div class="ap-profile-header">
                <div class="ap-avatar"><?= strtoupper(substr($data['student_name'],0,1)) ?></div>
                <div class="ap-identity">
                    <h2 class="ap-name"><?= htmlspecialchars($data['student_name']) ?></h2>
                    <div class="ap-meta-row">
                        <span class="ap-meta"><i class="fas fa-envelope"></i><?= htmlspecialchars($data['student_email']) ?></span>
                        <span class="ap-meta"><i class="fas fa-university"></i><?= htmlspecialchars($data['university'] ?: 'N/A') ?></span>
                        <span class="ap-meta"><i class="fas fa-graduation-cap"></i><?= htmlspecialchars($data['course'] ?: 'N/A') ?></span>
                        <span class="ap-meta"><i class="fas fa-calendar-alt"></i>Member since <?= date('M Y', strtotime($data['member_since'])) ?></span>
                    </div>
                </div>
                <span class="badge badge-<?= $data['status'] ?>" style="flex-shrink:0;"><?= ucfirst($data['status']) ?></span>
            </div>
        </div>

        <!-- Skills -->
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-code" style="color:var(--primary);margin-right:8px;"></i>Skills</h3>
                <?php if ($match['percent'] > 0): ?>
                <span style="font-size:.82rem;font-weight:700;color:<?= SkillMatcher::color($match['percent']) ?>">
                    <?= $match['percent'] ?>% match
                </span>
                <?php endif; ?>
            </div>
            <div class="content-card-body">
                <?php if (empty($data['skills'])): ?>
                    <p class="text-muted">No skills listed.</p>
                <?php else: ?>
                <div class="ap-skills-grid">
                    <?php
                    $skills  = array_filter(array_map('trim', explode(',', $data['skills'])));
                    $matched = array_map('strtolower', $match['matched']);
                    foreach ($skills as $skill):
                        $isMatch = in_array(strtolower($skill), $matched);
                    ?>
                    <span class="ap-skill-tag <?= $isMatch ? 'ap-skill-matched' : '' ?>">
                        <?php if ($isMatch): ?><i class="fas fa-check"></i><?php else: ?><i class="fas fa-circle" style="font-size:.4rem;opacity:.5;"></i><?php endif; ?>
                        <?= htmlspecialchars($skill) ?>
                    </span>
                    <?php endforeach; ?>
                </div>

                <!-- Skill match bar -->
                <?php if ($match['percent'] > 0): ?>
                <div class="skill-match-bar" style="margin-top:16px;">
                    <div class="smb-header">
                        <span class="smb-label">Match with "<?= htmlspecialchars($data['internship_title']) ?>"</span>
                        <span class="smb-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>">
                            <?= $match['percent'] ?>% — <?= SkillMatcher::label($match['percent']) ?>
                        </span>
                    </div>
                    <div class="smb-track">
                        <div class="smb-fill" style="width:<?= $match['percent'] ?>%;background:<?= SkillMatcher::color($match['percent']) ?>"></div>
                    </div>
                </div>
                <?php if (!empty($match['matched'])): ?>
                <div class="ap-match-breakdown">
                    <div class="ap-match-col">
                        <div class="ap-match-col-label"><i class="fas fa-check-circle" style="color:#10B981"></i> Matched (<?= count($match['matched']) ?>)</div>
                        <div class="ic-tags">
                            <?php foreach ($match['matched'] as $ms): ?>
                            <span class="ic-tag" style="background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);color:#059669"><?= htmlspecialchars($ms) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                    $reqArr  = array_filter(array_map('trim', explode(',', $data['internship_requirements'])));
                    $missing = array_filter($reqArr, fn($r) => !in_array(strtolower(trim($r)), $matched));
                    if (!empty($missing)):
                    ?>
                    <div class="ap-match-col">
                        <div class="ap-match-col-label"><i class="fas fa-times-circle" style="color:#EF4444"></i> Missing (<?= count($missing) ?>)</div>
                        <div class="ic-tags">
                            <?php foreach ($missing as $ms): ?>
                            <span class="ic-tag" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);color:#DC2626"><?= htmlspecialchars(trim($ms)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CV / Resume -->
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-file-pdf" style="color:var(--primary);margin-right:8px;"></i>CV / Resume</h3>
                <?php if ($data['cv_file']): ?>
                <span class="badge badge-approved">Uploaded</span>
                <?php endif; ?>
            </div>
            <div class="content-card-body">
                <?php if ($data['cv_file']): ?>
                <div class="ap-cv-box">
                    <div class="ap-cv-icon"><i class="fas fa-file-pdf"></i></div>
                    <div class="ap-cv-info">
                        <div class="ap-cv-name"><?= htmlspecialchars($data['student_name']) ?>'s CV</div>
                        <div class="ap-cv-meta">PDF Document · Ready for review</div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <a href="../../uploads/cv/<?= urlencode($data['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                        <a href="../../uploads/cv/<?= urlencode($data['cv_file']) ?>" download class="btn btn-blue btn-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding:24px;">
                    <i class="fas fa-file-slash" style="font-size:2rem;"></i>
                    <h3>No CV uploaded</h3>
                    <p>This student hasn't uploaded a CV yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Application history at this company -->
        <?php if (!empty($otherApplications)): ?>
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-history" style="color:var(--primary);margin-right:8px;"></i>Other Applications at Your Company</h3>
            </div>
            <div class="table-wrapper">
            <table class="table">
                <thead><tr><th>Internship</th><th>Applied</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($otherApplications as $oa): ?>
                <tr>
                    <td><?= htmlspecialchars($oa['title']) ?></td>
                    <td class="text-muted text-small"><?= date('M j, Y', strtotime($oa['applied_at'])) ?></td>
                    <td><span class="badge badge-<?= $oa['status'] ?>"><?= ucfirst($oa['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ RIGHT: Actions sidebar ══ -->
    <aside class="ap-sidebar">

        <!-- Application info -->
        <div class="ap-action-card">
            <div class="ap-action-title"><i class="fas fa-file-alt"></i> Application Details</div>
            <div class="ap-detail-rows">
                <div class="ap-detail-row">
                    <span>Position</span>
                    <strong><?= htmlspecialchars($data['internship_title']) ?></strong>
                </div>
                <div class="ap-detail-row">
                    <span>Applied</span>
                    <strong><?= date('M j, Y', strtotime($data['applied_at'])) ?></strong>
                </div>
                <div class="ap-detail-row">
                    <span>Status</span>
                    <span class="badge badge-<?= $data['status'] ?>"><?= ucfirst($data['status']) ?></span>
                </div>
                <?php if ($data['interview_date']): ?>
                <div class="ap-detail-row">
                    <span>Interview</span>
                    <strong style="color:var(--primary)"><?= date('M j, Y g:i A', strtotime($data['interview_date'])) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="ap-action-card">
            <div class="ap-action-title"><i class="fas fa-tasks"></i> Take Action</div>

            <?php if ($data['status'] === 'pending'): ?>
            <div class="ap-action-btns">
                <form action="../../controllers/internship_controller.php" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="application_id" value="<?= $app_id ?>">
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check-circle"></i> Approve Application
                    </button>
                </form>
                <form action="../../controllers/internship_controller.php" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="application_id" value="<?= $app_id ?>">
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" class="btn btn-danger btn-block"
                            data-confirm="Reject this application? The student will be notified.">
                        <i class="fas fa-times-circle"></i> Reject Application
                    </button>
                </form>
            </div>
            <?php elseif ($data['status'] === 'approved'): ?>
            <div class="app-status-banner app-status-approved" style="margin-bottom:14px;">
                <div class="asb-icon"><i class="fas fa-check-circle"></i></div>
                <div class="asb-body">
                    <div class="asb-title">Approved</div>
                    <div class="asb-text">This application has been approved.</div>
                </div>
            </div>
            <?php elseif ($data['status'] === 'rejected'): ?>
            <div class="app-status-banner app-status-rejected" style="margin-bottom:14px;">
                <div class="asb-icon"><i class="fas fa-times-circle"></i></div>
                <div class="asb-body">
                    <div class="asb-title">Rejected</div>
                    <div class="asb-text">This application was rejected.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($data['status'] === 'approved'): ?>
            <button class="btn btn-schedule btn-block" style="margin-top:8px;"
                    onclick="openModal('scheduleModal')">
                <i class="fas fa-calendar-alt"></i>
                <?= $data['interview_date'] ? 'Reschedule Interview' : 'Schedule Interview' ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- Match score card -->
        <?php if ($match['percent'] > 0): ?>
        <div class="ap-action-card" style="text-align:center;">
            <div class="ap-action-title" style="text-align:left;"><i class="fas fa-brain"></i> Match Score</div>
            <div class="ap-match-ring-lg" style="--mp:<?= $match['percent'] ?>%;--mc:<?= SkillMatcher::color($match['percent']) ?>">
                <div class="ap-match-ring-inner">
                    <div style="font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800;color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</div>
                    <div style="font-size:.65rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.04em;">Match</div>
                </div>
            </div>
            <div style="font-weight:700;font-size:.9rem;color:<?= SkillMatcher::color($match['percent']) ?>;margin-top:8px;">
                <?= SkillMatcher::label($match['percent']) ?>
            </div>
        </div>
        <?php endif; ?>

    </aside>
</div>
</div>
</section>

<!-- Schedule Interview Modal -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:8px;"></i>Schedule Interview</h3>
            <button class="modal-close" onclick="closeModal('scheduleModal')">×</button>
        </div>
        <form action="../../controllers/internship_controller.php" method="POST" data-validate>
            <div class="modal-body">
                <input type="hidden" name="action" value="schedule">
                <input type="hidden" name="application_id" value="<?= $app_id ?>">
                <div class="schedule-for-badge" style="margin-bottom:20px;">
                    <i class="fas fa-user-graduate"></i>
                    Scheduling for: <strong><?= htmlspecialchars($data['student_name']) ?></strong>
                    &nbsp;·&nbsp; <?= htmlspecialchars($data['internship_title']) ?>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Interview Date &amp; Time <span style="color:var(--c-danger)">*</span></label>
                    <input type="datetime-local" name="interview_date" class="form-control" required
                           min="<?= date('Y-m-d\TH:i') ?>"
                           value="<?= $data['interview_date'] ? date('Y-m-d\TH:i', strtotime($data['interview_date'])) : '' ?>">
                    <span class="form-hint"><i class="fas fa-envelope"></i> Student will receive email notification automatically.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('scheduleModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-blue">
                    <i class="fas fa-calendar-check"></i> Confirm &amp; Notify Student
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
