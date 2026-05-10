<?php
/**
 * Campus2Career - Public Internships Listing
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

$pdo    = getDBConnection();
$search = trim($_GET['search'] ?? '');
$params = [];
$where  = "WHERE 1=1";

if (!empty($search)) {
    $where   .= " AND (i.title LIKE ? OR i.requirements LIKE ? OR c.company_name LIKE ? OR c.location LIKE ?)";
    $params   = array_fill(0, 4, "%{$search}%");
}

$stmt = $pdo->prepare("
    SELECT i.*, c.company_name, c.location,
           (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.internship_id) AS applicant_count
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    $where
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$internships = $stmt->fetchAll();

$pageTitle = 'Browse Internships – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Internship Opportunities</h1>
            <p><?= count($internships) ?> internship<?= count($internships) !== 1 ? 's' : '' ?> available right now</p>
        </div>
        <?php if (isLoggedIn() && $_SESSION['role'] === 'student'): ?>
        <a href="../student/internships.php" class="btn btn-light"><i class="fas fa-user-graduate"></i> My Student View</a>
        <?php elseif (!isLoggedIn()): ?>
        <a href="../../register.php" class="btn btn-light"><i class="fas fa-rocket"></i> Sign Up to Apply</a>
        <?php endif; ?>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <!-- Search/Filter -->
        <form method="GET" class="filter-bar">
            <div class="form-group" style="flex:2;">
                <label class="form-label">Search Internships</label>
                <div class="input-icon">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="Title, skills, company, location..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn-blue"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?><a href="?" class="btn btn-outline" style="margin-left:8px;">Clear</a><?php endif; ?>
            </div>
        </form>

        <?php if (empty($internships)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>No internships found</h3>
            <p>Try a different search term or check back later.</p>
        </div>
        <?php else: ?>
        <div class="internship-grid">
            <?php foreach ($internships as $job): ?>
            <div class="internship-card">
                <div class="ic-header">
                    <div class="ic-logo"><i class="fas fa-building"></i></div>
                    <div>
                        <div class="ic-title"><?= htmlspecialchars($job['title']) ?></div>
                        <div class="ic-company"><?= htmlspecialchars($job['company_name']) ?></div>
                    </div>
                </div>
                <div class="ic-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></div>
                <div class="ic-desc"><?= htmlspecialchars($job['description']) ?></div>
                <div class="ic-tags">
                    <?php foreach (array_slice(explode(',', $job['requirements']), 0, 5) as $req): ?>
                        <span class="ic-tag"><?= htmlspecialchars(trim($req)) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="ic-footer">
                    <span class="ic-date"><i class="fas fa-users"></i> <?= $job['applicant_count'] ?> applicant<?= $job['applicant_count'] !== '1' ? 's' : '' ?></span>
                    <?php if (isLoggedIn() && $_SESSION['role'] === 'student'): ?>
                    <a href="../student/internships.php" class="btn btn-blue btn-sm">Apply Now</a>
                    <?php else: ?>
                    <a href="../../register.php" class="btn btn-blue btn-sm">Apply Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../../includes/footer.php'; ?>
