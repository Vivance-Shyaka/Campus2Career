<?php
/**
 * Campus2Career - Company applicant profile review
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/SkillMatcher.php';

requireRole('company');

$pdo = getDBConnection();
$companyId = (int)$_SESSION['role_id'];
$applicationId = (int)($_GET['id'] ?? 0);

if (!$applicationId) {
    setFlash('error', 'Invalid applicant profile.');
    header('Location: applicants.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, i.title AS internship_title, i.description, i.requirements, i.responsibilities, i.expectations,
           c.company_name,
           s.student_id, s.university, s.course, s.skills, s.cv_file,
           u.name AS student_name, u.email AS student_email, u.created_at AS student_joined
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN companies c ON c.company_id = i.company_id
    JOIN students s ON s.student_id = a.student_id
    JOIN users u ON u.user_id = s.user_id
    WHERE a.application_id = ? AND i.company_id = ?
");
$stmt->execute([$applicationId, $companyId]);
$app = $stmt->fetch();

if (!$app) {
    setFlash('error', 'Applicant not found or access denied.');
    header('Location: applicants.php');
    exit;
}

$certStmt = $pdo->prepare("SELECT * FROM student_certificates WHERE student_id = ? ORDER BY uploaded_at DESC");
$certStmt->execute([(int)$app['student_id']]);
$certificates = $certStmt->fetchAll();

$match = SkillMatcher::match($app['skills'] ?? '', $app['requirements'] ?? '');
$requiredSkills = array_filter(array_map('trim', explode(',', $app['requirements'] ?? '')));
$matchedLower = array_map('strtolower', $match['matched']);
$missingSkills = [];
foreach ($requiredSkills as $skill) {
    if (!in_array(strtolower($skill), $matchedLower, true)) {
        $missingSkills[] = $skill;
    }
}

$pageTitle = 'Applicant Profile - Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Applicant Profile</h1>
            <p>Review qualifications, documents, match score, and application decision</p>
        </div>
        <a href="applicants.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Applicants</a>
    </div>
</div>

<section class="section-sm">
<div class="container">
    <div class="applicant-profile-grid">
        <div class="applicant-profile-main">
            <div class="content-card">
                <div class="content-card-body">
                    <div class="profile-review-head">
                        <div class="applicant-avatar profile-avatar-lg"><?= strtoupper(substr($app['student_name'], 0, 1)) ?></div>
                        <div>
                            <h2><?= htmlspecialchars($app['student_name']) ?></h2>
                            <p><?= htmlspecialchars($app['student_email']) ?></p>
                            <div class="applicant-meta mt-8">
                                <span><i class="fas fa-university"></i> <?= htmlspecialchars($app['university'] ?: 'University not listed') ?></span>
                                <span><i class="fas fa-book"></i> <?= htmlspecialchars($app['course'] ?: 'Course not listed') ?></span>
                                <span><i class="fas fa-calendar"></i> Joined <?= date('M Y', strtotime($app['student_joined'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-code" style="color:var(--primary);margin-right:8px;"></i>Skills</h3></div>
                <div class="content-card-body">
                    <div class="ic-tags profile-skill-tags">
                        <?php foreach (array_filter(array_map('trim', explode(',', $app['skills'] ?? ''))) as $skill): ?>
                        <span class="ic-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                        <?php if (trim($app['skills'] ?? '') === ''): ?>
                        <span class="text-muted">No skills listed yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-file-alt" style="color:var(--primary);margin-right:8px;"></i>CV / Resume</h3></div>
                <div class="content-card-body">
                    <?php if ($app['cv_file']): ?>
                    <div class="cv-current-file">
                        <div class="cv-file-icon"><i class="fas fa-file-pdf"></i></div>
                        <div class="cv-file-info">
                            <div class="cv-file-name"><?= htmlspecialchars($app['cv_file']) ?></div>
                            <div class="cv-file-meta">Available for employer review</div>
                        </div>
                        <a href="../../uploads/cv/<?= urlencode($app['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Preview</a>
                        <a href="../../uploads/cv/<?= urlencode($app['cv_file']) ?>" download class="btn btn-blue btn-sm"><i class="fas fa-download"></i> Download</a>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:26px;"><i class="fas fa-file-circle-xmark"></i><p>No CV uploaded yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-award" style="color:var(--primary);margin-right:8px;"></i>Certificates</h3></div>
                <div class="content-card-body">
                    <?php if ($certificates): ?>
                        <?php foreach ($certificates as $cert): ?>
                        <div class="cv-current-file">
                            <div class="cv-file-icon"><i class="fas fa-certificate"></i></div>
                            <div class="cv-file-info">
                                <div class="cv-file-name"><?= htmlspecialchars($cert['original_name']) ?></div>
                                <div class="cv-file-meta">Uploaded <?= date('M j, Y', strtotime($cert['uploaded_at'])) ?></div>
                            </div>
                            <a href="../../uploads/certificates/<?= urlencode($cert['file_name']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
                            <a href="../../uploads/certificates/<?= urlencode($cert['file_name']) ?>" download class="btn btn-blue btn-sm"><i class="fas fa-download"></i> Download</a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="empty-state" style="padding:26px;"><i class="fas fa-award"></i><p>No certificates uploaded yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="applicant-profile-side">
            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-brain" style="color:var(--primary);margin-right:8px;"></i>Compatibility</h3></div>
                <div class="content-card-body">
                    <div class="id-match-ring profile-match-ring" style="--mp:<?= $match['percent'] ?>%;--mc:<?= SkillMatcher::color($match['percent']) ?>">
                        <div class="id-match-inner">
                            <div class="id-match-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</div>
                            <div class="id-match-label">Match</div>
                        </div>
                    </div>
                    <div class="id-match-text mt-8" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= SkillMatcher::label($match['percent']) ?></div>
                    <div class="divider"></div>
                    <div class="smb-label mb-8">Matching Skills</div>
                    <div class="ic-tags mb-16">
                        <?php foreach ($match['matched'] as $skill): ?><span class="ic-tag" style="color:#059669"><?= htmlspecialchars($skill) ?></span><?php endforeach; ?>
                        <?php if (!$match['matched']): ?><span class="text-muted text-small">No direct keyword matches.</span><?php endif; ?>
                    </div>
                    <div class="smb-label mb-8">Missing Skills</div>
                    <div class="ic-tags">
                        <?php foreach ($missingSkills as $skill): ?><span class="ic-tag" style="color:#DC2626"><?= htmlspecialchars($skill) ?></span><?php endforeach; ?>
                        <?php if (!$missingSkills): ?><span class="text-muted text-small">No missing required skills detected.</span><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-briefcase" style="color:var(--primary);margin-right:8px;"></i>Application</h3></div>
                <div class="content-card-body">
                    <div class="id-qf-row"><i class="fas fa-briefcase"></i><span><?= htmlspecialchars($app['internship_title']) ?></span></div>
                    <div class="id-qf-row mt-8"><i class="fas fa-clock"></i><span>Applied <?= date('M j, Y g:i A', strtotime($app['applied_at'])) ?></span></div>
                    <div class="id-qf-row mt-8"><i class="fas fa-signal"></i><span class="badge badge-<?= htmlspecialchars($app['status']) ?>"><?= ucfirst($app['status']) ?></span></div>
                    <?php if ($app['interview_date']): ?>
                    <div class="interview-scheduled-banner mt-16">
                        <div class="isb-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="isb-body">
                            <div class="isb-title">Interview Scheduled</div>
                            <div class="isb-datetime"><?= date('M j, Y g:i A', strtotime($app['interview_date'])) ?></div>
                            <?php if ($app['interview_location']): ?><div class="isb-note"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($app['interview_location']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><h3><i class="fas fa-check-double" style="color:var(--primary);margin-right:8px;"></i>Decision</h3></div>
                <div class="content-card-body">
                    <div class="applicant-actions stacked">
                        <form action="../../controllers/internship_controller.php" method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="return_to" value="profile">
                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check"></i> Approve Applicant</button>
                        </form>
                        <form action="../../controllers/internship_controller.php" method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="return_to" value="profile">
                            <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-danger btn-block" data-confirm="Reject this application? The student will be notified."><i class="fas fa-times"></i> Reject Applicant</button>
                        </form>
                        <button type="button" class="btn btn-blue btn-block" onclick="openModal('scheduleProfileModal')"><i class="fas fa-calendar-alt"></i> Schedule Interview</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</section>

<div class="modal-overlay" id="scheduleProfileModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:8px;"></i>Schedule Interview</h3>
            <button class="modal-close" onclick="closeModal('scheduleProfileModal')">&times;</button>
        </div>
        <form action="../../controllers/internship_controller.php" method="POST" data-validate>
            <div class="modal-body">
                <input type="hidden" name="action" value="schedule">
                <input type="hidden" name="return_to" value="profile">
                <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                <div class="form-group">
                    <label class="form-label">Interview Date & Time</label>
                    <input type="datetime-local" name="interview_date" class="form-control" required min="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Location / Meeting Link</label>
                    <input type="text" name="interview_location" class="form-control" placeholder="Boardroom, Zoom link, Google Meet, or campus location">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Notes for Student</label>
                    <textarea name="interview_notes" class="form-control" rows="4" placeholder="Documents to bring, interview panel, preparation notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('scheduleProfileModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-blue"><i class="fas fa-calendar-check"></i> Schedule & Notify</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
