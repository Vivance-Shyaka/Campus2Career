<?php
/**
 * Campus2Career – Student Profile & CV Upload
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/FileUploader.php';

requireRole('student');

$pdo     = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $university = trim($_POST['university'] ?? '');
        $course     = trim($_POST['course']     ?? '');
        $skills     = trim($_POST['skills']     ?? '');
        $name       = trim($_POST['name']       ?? '');

        if (empty($name)) { setFlash('error', 'Name is required.'); }
        else {
            $pdo->prepare("UPDATE users SET name=? WHERE user_id=?")->execute([$name, $user_id]);
            $pdo->prepare("UPDATE students SET university=?, course=?, skills=? WHERE user_id=?")
                ->execute([$university, $course, $skills, $user_id]);
            $_SESSION['name'] = $name;
            setFlash('success', 'Profile updated successfully!');
        }
    }

    if ($action === 'upload_cv' && isset($_FILES['cv_file'])) {
        $uploader = new FileUploader(__DIR__ . '/../../uploads/cv/', 'cv');
        $result   = $uploader->upload($_FILES['cv_file']);
        if ($result['success']) {
            // Delete old file
            $old = $pdo->prepare("SELECT cv_file FROM students WHERE user_id=?");
            $old->execute([$user_id]);
            $oldRow = $old->fetch();
            if ($oldRow && $oldRow['cv_file']) $uploader->delete($oldRow['cv_file']);

            $pdo->prepare("UPDATE students SET cv_file=? WHERE user_id=?")->execute([$result['filename'], $user_id]);
            setFlash('success', 'CV uploaded successfully!');
        } else {
            setFlash('error', $result['error']);
        }
    }

    if ($action === 'upload_certificate' && isset($_FILES['certificate_file'])) {
        $uploader = new FileUploader(__DIR__ . '/../../uploads/certificates/', 'cert');
        $result = $uploader->upload($_FILES['certificate_file']);
        if ($result['success']) {
            $stmt = $pdo->prepare("INSERT INTO student_certificates (student_id, file_name, original_name) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['role_id'], $result['filename'], basename($_FILES['certificate_file']['name'])]);
            setFlash('success', 'Certificate uploaded successfully!');
        } else {
            setFlash('error', $result['error']);
        }
    }

    header('Location: profile.php'); exit;
}

// Load profile
$profile = $pdo->prepare("
    SELECT u.name, u.email, u.created_at,
           s.university, s.course, s.skills, s.cv_file
    FROM users u JOIN students s ON s.user_id = u.user_id
    WHERE u.user_id = ?
");
$profile->execute([$user_id]);
$p = $profile->fetch();

$certStmt = $pdo->prepare("SELECT * FROM student_certificates WHERE student_id = ? ORDER BY uploaded_at DESC");
$certStmt->execute([$_SESSION['role_id']]);
$certificates = $certStmt->fetchAll();

// Application stats
$stats = [];
foreach (['pending','approved','rejected'] as $st) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id=? AND status=?");
    $q->execute([$_SESSION['role_id'], $st]);
    $stats[$st] = $q->fetchColumn();
}

$pageTitle = 'My Profile – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <div><h1>My Profile</h1><p>Manage your personal information and CV</p></div>
    <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>
</div>

<section class="section-sm">
<div class="container">
<div class="sidebar-layout">

  <!-- Sidebar -->
  <div>
    <div class="content-card" style="overflow:hidden;">
      <div style="background:linear-gradient(135deg,var(--grad-start),var(--grad-end));padding:32px 24px;text-align:center;color:white;">
        <div class="sidebar-avatar"><?= strtoupper(substr($p['name'],0,1)) ?></div>
        <div class="sidebar-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="sidebar-role"><?= htmlspecialchars($p['course'] ?: 'Student') ?></div>
        <div style="margin-top:8px;"><span class="role-badge role-student">Student</span></div>
      </div>
      <div class="content-card-body">
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="id-qf-row"><i class="fas fa-university" style="color:var(--primary)"></i><span><?= htmlspecialchars($p['university'] ?: '—') ?></span></div>
          <div class="id-qf-row"><i class="fas fa-envelope" style="color:var(--primary)"></i><span style="font-size:.82rem"><?= htmlspecialchars($p['email']) ?></span></div>
          <div class="id-qf-row"><i class="fas fa-calendar" style="color:var(--primary)"></i><span>Joined <?= date('M Y', strtotime($p['created_at'])) ?></span></div>
        </div>
        <div class="divider"></div>
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;text-align:center;">
          <?php foreach ([['Pending','#F59E0B',$stats['pending']],['Approved','#10B981',$stats['approved']],['Rejected','#EF4444',$stats['rejected']]] as [$l,$c,$v]): ?>
          <div style="background:var(--c-bg-2);border-radius:var(--radius-sm);padding:10px;">
            <div style="font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:<?= $c ?>"><?= $v ?></div>
            <div style="font-size:.7rem;color:var(--c-text-muted)"><?= $l ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <div style="display:flex;flex-direction:column;gap:24px;">

    <!-- Edit Profile -->
    <div class="content-card">
      <div class="content-card-header">
        <h3><i class="fas fa-user-edit" style="color:var(--primary);margin-right:8px;"></i>Edit Profile</h3>
      </div>
      <div class="content-card-body">
        <form action="profile.php" method="POST" data-validate>
          <input type="hidden" name="action" value="update_profile">
          <div class="page-grid" style="gap:16px;">
            <div class="form-group">
              <label class="form-label">Full Name <span style="color:var(--c-danger)">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($p['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
              <span class="form-hint">Email cannot be changed.</span>
            </div>
          </div>
          <div class="page-grid" style="gap:16px;">
            <div class="form-group">
              <label class="form-label">University</label>
              <input type="text" name="university" class="form-control" value="<?= htmlspecialchars($p['university'] ?? '') ?>" placeholder="e.g. AUCA">
            </div>
            <div class="form-group">
              <label class="form-label">Course / Major</label>
              <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($p['course'] ?? '') ?>" placeholder="e.g. Software Engineering">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Skills <span style="font-weight:400;color:var(--c-text-muted)">(comma-separated)</span></label>
            <input type="text" name="skills" class="form-control" value="<?= htmlspecialchars($p['skills'] ?? '') ?>" placeholder="e.g. HTML, CSS, JavaScript, PHP, MySQL">
            <span class="form-hint"><i class="fas fa-lightbulb"></i> Adding accurate skills improves your internship match score.</span>
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-blue"><i class="fas fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- CV Upload -->
    <div class="content-card">
      <div class="content-card-header">
        <h3><i class="fas fa-file-pdf" style="color:var(--primary);margin-right:8px;"></i>CV / Resume</h3>
        <?php if ($p['cv_file']): ?>
        <span class="badge badge-approved"><i class="fas fa-check"></i> Uploaded</span>
        <?php endif; ?>
      </div>
      <div class="content-card-body">

        <?php if ($p['cv_file']): ?>
        <div class="cv-current-file">
          <div class="cv-file-icon"><i class="fas fa-file-pdf"></i></div>
          <div class="cv-file-info">
            <div class="cv-file-name"><?= htmlspecialchars($p['cv_file']) ?></div>
            <div class="cv-file-meta">Uploaded · Ready for employers</div>
          </div>
          <a href="../../uploads/cv/<?= urlencode($p['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">
            <i class="fas fa-eye"></i> View
          </a>
        </div>
        <div class="divider"></div>
        <p style="font-size:.85rem;color:var(--c-text-muted);margin-bottom:16px;">Upload a new file to replace your current CV.</p>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data" data-validate>
          <input type="hidden" name="action" value="upload_cv">
          <div class="cv-upload-zone" id="cvDropZone">
            <div class="cv-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="cv-upload-text">Drag &amp; drop your CV here, or <span class="cv-upload-link" onclick="document.getElementById('cvInput').click()">browse</span></div>
            <div class="cv-upload-hint">PDF, DOC, DOCX · Max 5MB</div>
            <input type="file" name="cv_file" id="cvInput" accept=".pdf,.doc,.docx" style="display:none;" required onchange="showFileName(this)">
            <div id="cvFileName" class="cv-filename-preview" style="display:none;"></div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:14px;">
            <button type="submit" class="btn btn-blue"><i class="fas fa-upload"></i> Upload CV</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Certificates Upload -->
    <div class="content-card">
      <div class="content-card-header">
        <h3><i class="fas fa-award" style="color:var(--primary);margin-right:8px;"></i>Certificates</h3>
        <span class="badge badge-approved"><?= count($certificates) ?> uploaded</span>
      </div>
      <div class="content-card-body">
        <?php if ($certificates): ?>
        <div class="certificate-list">
          <?php foreach ($certificates as $cert): ?>
          <div class="cv-current-file">
            <div class="cv-file-icon"><i class="fas fa-certificate"></i></div>
            <div class="cv-file-info">
              <div class="cv-file-name"><?= htmlspecialchars($cert['original_name']) ?></div>
              <div class="cv-file-meta">Uploaded <?= date('M j, Y', strtotime($cert['uploaded_at'])) ?></div>
            </div>
            <a href="../../uploads/certificates/<?= urlencode($cert['file_name']) ?>" target="_blank" class="btn btn-outline btn-sm">
              <i class="fas fa-download"></i> View
            </a>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="divider"></div>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data" data-validate>
          <input type="hidden" name="action" value="upload_certificate">
          <div class="cv-upload-zone">
            <div class="cv-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="cv-upload-text">Upload a certificate, transcript, or achievement document</div>
            <div class="cv-upload-hint">PDF, DOC, DOCX - Max 5MB</div>
            <input type="file" name="certificate_file" accept=".pdf,.doc,.docx" class="form-control mt-16" required>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:14px;">
            <button type="submit" class="btn btn-blue"><i class="fas fa-upload"></i> Upload Certificate</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
</div>
</section>

<script>
function showFileName(input) {
    const el = document.getElementById('cvFileName');
    if (input.files && input.files[0]) {
        el.textContent = '📄 ' + input.files[0].name;
        el.style.display = 'block';
    }
}
// Drag & drop
const zone = document.getElementById('cvDropZone');
if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('cv-drop-active'); });
    zone.addEventListener('dragleave', ()  => zone.classList.remove('cv-drop-active'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('cv-drop-active');
        const input = document.getElementById('cvInput');
        input.files = e.dataTransfer.files;
        showFileName(input);
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
