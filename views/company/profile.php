<?php
/**
 * Campus2Career – Company Profile Edit
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireRole('company');

$pdo     = getDBConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']         ?? '');
    $coName   = trim($_POST['company_name'] ?? '');
    $location = trim($_POST['location']     ?? '');
    $description = trim($_POST['description'] ?? '');
    $website = trim($_POST['website'] ?? '');
    if (empty($name) || empty($coName)) {
        setFlash('error', 'Name and company name are required.');
    } else {
        $pdo->prepare("UPDATE users SET name=? WHERE user_id=?")->execute([$name, $user_id]);
        $pdo->prepare("UPDATE companies SET company_name=?, location=?, description=?, website=? WHERE user_id=?")
            ->execute([$coName, $location, $description, $website, $user_id]);
        $_SESSION['name'] = $name;
        setFlash('success', 'Profile updated!');
    }
    header('Location: profile.php'); exit;
}

$p = $pdo->prepare("
    SELECT u.name, u.email, u.created_at, c.company_name, c.location, c.description, c.website
    FROM users u JOIN companies c ON c.user_id = u.user_id WHERE u.user_id=?
");
$p->execute([$user_id]);
$profile = $p->fetch();

// Stats
$cid = $_SESSION['role_id'];
$si  = $pdo->prepare("SELECT COUNT(*) FROM internships WHERE company_id=?"); $si->execute([$cid]);
$sa  = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN internships i ON i.internship_id=a.internship_id WHERE i.company_id=?"); $sa->execute([$cid]);

$pageTitle = 'Company Profile – Campus2Career';
require_once '../../includes/header.php';
?>
<div class="page-banner">
  <div class="container">
    <div><h1>Company Profile</h1><p>Manage your company information</p></div>
    <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>
</div>
<section class="section-sm"><div class="container"><div class="sidebar-layout">
  <div>
    <div class="content-card" style="overflow:hidden;">
      <div style="background:linear-gradient(135deg,var(--grad-start),var(--grad-end));padding:32px 24px;text-align:center;color:white;">
        <div class="sidebar-avatar" style="font-size:1.4rem;"><i class="fas fa-building"></i></div>
        <div class="sidebar-name"><?= htmlspecialchars($profile['company_name']) ?></div>
        <div class="sidebar-role"><?= htmlspecialchars($profile['location']) ?></div>
        <span class="role-badge role-company">Company</span>
      </div>
      <div class="content-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center;">
          <div style="background:var(--c-bg-2);border-radius:var(--radius-sm);padding:14px;">
            <div style="font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:var(--primary)"><?= $si->fetchColumn() ?></div>
            <div style="font-size:.72rem;color:var(--c-text-muted)">Internships</div>
          </div>
          <div style="background:var(--c-bg-2);border-radius:var(--radius-sm);padding:14px;">
            <div style="font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:#6366F1"><?= $sa->fetchColumn() ?></div>
            <div style="font-size:.72rem;color:var(--c-text-muted)">Applicants</div>
          </div>
        </div>
        <div class="divider"></div>
        <div class="id-qf-row"><i class="fas fa-envelope" style="color:var(--primary)"></i><span style="font-size:.82rem"><?= htmlspecialchars($profile['email']) ?></span></div>
        <div class="id-qf-row mt-8"><i class="fas fa-calendar" style="color:var(--primary)"></i><span>Joined <?= date('M Y', strtotime($profile['created_at'])) ?></span></div>
      </div>
    </div>
  </div>
  <div>
    <div class="content-card">
      <div class="content-card-header"><h3><i class="fas fa-edit" style="color:var(--primary);margin-right:8px;"></i>Edit Company Profile</h3></div>
      <div class="content-card-body">
        <form action="profile.php" method="POST" data-validate>
          <div class="page-grid" style="gap:16px;">
            <div class="form-group">
              <label class="form-label">Contact Name *</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($profile['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Company Name *</label>
              <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($profile['company_name']) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($profile['location']) ?>" placeholder="e.g. Kigali, Rwanda">
          </div>
          <div class="form-group">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" placeholder="https://company.com">
          </div>
          <div class="form-group">
            <label class="form-label">Company Description</label>
            <textarea name="description" class="form-control textarea-large" rows="8" placeholder="Write a professional company overview, mission, industry focus, and what interns can expect."><?= htmlspecialchars($profile['description'] ?? '') ?></textarea>
            <span class="form-hint">This helps students understand your organization before applying.</span>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
            <span class="form-hint">Email cannot be changed.</span>
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-blue"><i class="fas fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div></div></section>
<?php require_once '../../includes/footer.php'; ?>
