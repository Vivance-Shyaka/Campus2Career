<?php
/**
 * Campus2Career – Internship Details Page
 * Full description, skill matching, apply workflow
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/SkillMatcher.php';

requireRole('student');

$id  = (int)($_GET['id'] ?? 0);
$pdo = getDBConnection();

if (!$id) {
    setFlash('error', 'Invalid internship.');
    header('Location: internships.php'); exit;
}

$stmt = $pdo->prepare("
    SELECT i.*, c.company_name, c.description AS company_description, c.website,
           COALESCE(NULLIF(i.internship_location,''), c.location) AS location, u.email AS company_email
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    JOIN users     u ON u.user_id    = c.user_id
    WHERE i.internship_id = ?
");
$stmt->execute([$id]);
$job = $stmt->fetch();
if (!$job) {
    setFlash('error', 'Internship not found.');
    header('Location: internships.php'); exit;
}

$student_id = $_SESSION['role_id'];

// Has student already applied?
$applied = $pdo->prepare("SELECT application_id FROM applications WHERE student_id=? AND internship_id=?");
$applied->execute([$student_id, $id]);
$existingApp = $applied->fetch();

// Student skills for matching
$sr = $pdo->prepare("SELECT skills FROM students WHERE user_id=?");
$sr->execute([$_SESSION['user_id']]);
$skillRow      = $sr->fetch();
$studentSkills = $skillRow['skills'] ?? '';
$match         = SkillMatcher::match($studentSkills, $job['requirements']);

// Related internships (same company or overlapping skills)
$related = $pdo->prepare("
    SELECT i.*, c.company_name
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    WHERE i.internship_id != ? AND (
        i.company_id = ? OR
        i.requirements LIKE CONCAT('%', SUBSTRING_INDEX(?, ',', 1), '%')
    )
    LIMIT 3
");
$related->execute([$id, $job['company_id'], $job['requirements']]);
$relatedJobs = $related->fetchAll();

$pageTitle = htmlspecialchars($job['title']) . ' – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="id-hero">
  <div class="container">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="sep">›</span>
      <a href="internships.php">Internships</a>
      <span class="sep">›</span>
      <span><?= htmlspecialchars($job['title']) ?></span>
    </div>

    <div class="id-hero-inner">
      <!-- Left: title block -->
      <div class="id-hero-left">
        <div class="id-company-logo">
          <?= strtoupper(substr($job['company_name'], 0, 2)) ?>
        </div>
        <div>
          <h1 class="id-title"><?= htmlspecialchars($job['title']) ?></h1>
          <div class="id-meta-row">
            <span class="id-meta-chip"><i class="fas fa-building"></i> <?= htmlspecialchars($job['company_name']) ?></span>
            <span class="id-meta-chip"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
            <span class="id-meta-chip"><i class="fas fa-calendar-plus"></i> Posted <?= date('M j, Y', strtotime($job['created_at'])) ?></span>
          </div>
        </div>
      </div>

      <!-- Right: apply card -->
      <div class="id-apply-card">
        <?php if ($match['percent'] > 0): ?>
        <div class="id-match-ring" style="--mp:<?= $match['percent'] ?>%;--mc:<?= SkillMatcher::color($match['percent']) ?>">
          <div class="id-match-inner">
            <div class="id-match-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</div>
            <div class="id-match-label">Match</div>
          </div>
        </div>
        <div class="id-match-text" style="color:<?= SkillMatcher::color($match['percent']) ?>">
          <?= SkillMatcher::label($match['percent']) ?>
        </div>
        <?php endif; ?>

        <?php if ($existingApp): ?>
          <div class="id-applied-badge">
            <i class="fas fa-check-circle"></i> Already Applied
          </div>
          <a href="applications.php" class="btn btn-outline btn-block mt-16">
            <i class="fas fa-list"></i> View My Applications
          </a>
        <?php else: ?>
          <form action="../../controllers/internship_controller.php" method="POST">
            <input type="hidden" name="action" value="apply">
            <input type="hidden" name="internship_id" value="<?= $id ?>">
            <button type="submit" class="btn btn-blue btn-block btn-lg">
              <i class="fas fa-paper-plane"></i> Apply Now
            </button>
          </form>
          <p class="id-apply-note"><i class="fas fa-bolt"></i> Instant application · No CV required</p>
        <?php endif; ?>

        <div class="id-quick-facts">
          <div class="id-qf-row"><i class="fas fa-map-pin"></i><span><?= htmlspecialchars($job['location']) ?></span></div>
          <div class="id-qf-row"><i class="fas fa-envelope"></i><span><?= htmlspecialchars($job['company_email']) ?></span></div>
          <div class="id-qf-row"><i class="fas fa-clock"></i><span><?= date('M j, Y', strtotime($job['created_at'])) ?></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Body -->
<section class="section-sm">
<div class="container">
  <div class="id-body-grid">

    <!-- Main Content -->
    <div class="id-main">

      <!-- Description -->
      <div class="id-section-card">
        <div class="id-section-title">
          <i class="fas fa-align-left"></i> About This Internship
        </div>
        <div class="id-prose">
          <?= nl2br(htmlspecialchars($job['description'])) ?>
        </div>
      </div>

      <?php if (!empty($job['responsibilities'])): ?>
      <div class="id-section-card">
        <div class="id-section-title">
          <i class="fas fa-list-check"></i> Responsibilities
        </div>
        <div class="id-prose">
          <?= nl2br(htmlspecialchars($job['responsibilities'])) ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($job['expectations'])): ?>
      <div class="id-section-card">
        <div class="id-section-title">
          <i class="fas fa-bullseye"></i> Expectations & Learning Outcomes
        </div>
        <div class="id-prose">
          <?= nl2br(htmlspecialchars($job['expectations'])) ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Requirements -->
      <?php if (!empty($job['requirements'])): ?>
      <div class="id-section-card">
        <div class="id-section-title">
          <i class="fas fa-tasks"></i> Requirements & Skills
        </div>
        <div class="id-skills-grid">
          <?php
          $reqs = array_filter(array_map('trim', explode(',', $job['requirements'])));
          foreach ($reqs as $req):
            $matched = in_array(strtolower($req), array_map('strtolower', $match['matched']));
          ?>
          <div class="id-skill-chip <?= $matched ? 'id-skill-matched' : '' ?>">
            <?php if ($matched): ?><i class="fas fa-check"></i><?php else: ?><i class="fas fa-code"></i><?php endif; ?>
            <?= htmlspecialchars($req) ?>
            <?php if ($matched): ?><span class="id-skill-match-dot"></span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($match['matched'])): ?>
        <div class="id-match-summary">
          <i class="fas fa-star" style="color:#F59E0B"></i>
          You match <strong><?= count($match['matched']) ?></strong> of <strong><?= $match['total'] ?></strong> required skills
          — <span style="color:<?= SkillMatcher::color($match['percent']) ?>;font-weight:700"><?= SkillMatcher::label($match['percent']) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Apply CTA (bottom) -->
      <?php if (!$existingApp): ?>
      <div class="id-bottom-cta">
        <div>
          <h3>Ready to Apply?</h3>
          <p>Submit your application in seconds. The company will review and contact you.</p>
        </div>
        <form action="../../controllers/internship_controller.php" method="POST">
          <input type="hidden" name="action" value="apply">
          <input type="hidden" name="internship_id" value="<?= $id ?>">
          <button type="submit" class="btn btn-blue btn-lg">
            <i class="fas fa-paper-plane"></i> Apply for this Internship
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="id-sidebar">

      <!-- Skill match gauge (sidebar) -->
      <?php if ($match['percent'] > 0): ?>
      <div class="id-sidebar-card">
        <div class="id-sidebar-card-title"><i class="fas fa-brain"></i> Your Skill Match</div>
        <div class="skill-match-bar" style="margin:0;">
          <div class="smb-header">
            <span class="smb-label">Overall Match</span>
            <span class="smb-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</span>
          </div>
          <div class="smb-track"><div class="smb-fill" style="width:<?= $match['percent'] ?>%;background:<?= SkillMatcher::color($match['percent']) ?>"></div></div>
        </div>
        <?php if (!empty($match['matched'])): ?>
        <div style="margin-top:12px;">
          <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--c-text-muted);margin-bottom:8px;">Matched Skills</div>
          <div class="ic-tags">
            <?php foreach ($match['matched'] as $ms): ?>
            <span class="ic-tag" style="background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.3);color:#059669"><?= htmlspecialchars($ms) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Company info -->
      <div class="id-sidebar-card">
        <div class="id-sidebar-card-title"><i class="fas fa-building"></i> About the Company</div>
        <div class="id-company-info">
          <div class="id-co-logo-sm"><?= strtoupper(substr($job['company_name'],0,2)) ?></div>
          <div>
            <div class="id-co-name"><?= htmlspecialchars($job['company_name']) ?></div>
            <div class="id-co-loc"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></div>
          </div>
        </div>
        <?php if (!empty($job['company_description'])): ?>
        <p class="text-small text-muted mt-16"><?= nl2br(htmlspecialchars($job['company_description'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($job['website'])): ?>
        <a href="<?= htmlspecialchars($job['website']) ?>" target="_blank" class="btn btn-outline btn-sm mt-16"><i class="fas fa-globe"></i> Website</a>
        <?php endif; ?>
      </div>

      <!-- Related -->
      <?php if (!empty($relatedJobs)): ?>
      <div class="id-sidebar-card">
        <div class="id-sidebar-card-title"><i class="fas fa-th-large"></i> Similar Internships</div>
        <div class="id-related-list">
          <?php foreach ($relatedJobs as $rj): ?>
          <a href="?id=<?= $rj['internship_id'] ?>" class="id-related-item">
            <div class="id-related-logo"><?= strtoupper(substr($rj['company_name'],0,1)) ?></div>
            <div>
              <div class="id-related-title"><?= htmlspecialchars($rj['title']) ?></div>
              <div class="id-related-co"><?= htmlspecialchars($rj['company_name']) ?></div>
            </div>
            <i class="fas fa-chevron-right" style="margin-left:auto;color:var(--c-text-muted);font-size:.75rem;"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </aside>
  </div>
</div>
</section>

<?php require_once '../../includes/footer.php'; ?>
