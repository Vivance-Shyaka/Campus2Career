<?php
/**
 * Campus2Career - Admin: Manage Internships
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');
$pdo = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = "WHERE 1=1";
if (!empty($search)) {
    $where  .= " AND (i.title LIKE ? OR c.company_name LIKE ? OR i.requirements LIKE ?)";
    $params  = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("
    SELECT i.*, c.company_name, c.location,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.internship_id) AS app_count,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.internship_id AND status='pending') AS pending_count,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.internship_id AND status='approved') AS approved_count
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    $where
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$internships = $stmt->fetchAll();

$pageTitle = 'Manage Internships – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Manage Internships</h1>
            <p>Oversee all internship postings across the platform</p>
        </div>
        <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<section class="section-sm">
<div class="container">

    <form method="GET" class="filter-bar">
        <div class="form-group" style="flex:2;">
            <label class="form-label">Search Internships</label>
            <div class="input-icon">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="form-control" placeholder="Title, company, requirements..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-blue">Search</button>
            <?php if ($search): ?><a href="internships.php" class="btn btn-outline" style="margin-left:8px;">Clear</a><?php endif; ?>
        </div>
    </form>

    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-briefcase" style="color:var(--c-blue);margin-right:8px;"></i>
                Internships <span style="font-weight:400;color:var(--c-text-sub)">(<?= count($internships) ?>)</span>
            </h3>
        </div>
        <div class="table-wrapper">
        <?php if (empty($internships)): ?>
        <div class="empty-state"><i class="fas fa-briefcase"></i><h3>No internships found</h3></div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Internship</th>
                    <th>Company</th>
                    <th>Requirements</th>
                    <th>Applications</th>
                    <th>Posted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($internships as $i): ?>
            <tr class="filterable-row">
                <td>
                    <strong><?= htmlspecialchars($i['title']) ?></strong><br>
                    <span class="text-small text-muted"><?= htmlspecialchars(mb_substr($i['description'], 0, 70)) ?>...</span>
                </td>
                <td>
                    <strong><?= htmlspecialchars($i['company_name']) ?></strong><br>
                    <span class="text-small text-muted"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($i['location']) ?></span>
                </td>
                <td>
                    <div class="ic-tags">
                        <?php foreach (array_slice(explode(',', $i['requirements']), 0, 3) as $req): ?>
                            <span class="ic-tag"><?= htmlspecialchars(trim($req)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <span class="badge badge-approved"><?= $i['app_count'] ?> total</span>
                        <span class="badge badge-pending"><?= $i['pending_count'] ?> pending</span>
                        <span class="badge badge-approved"><?= $i['approved_count'] ?> approved</span>
                    </div>
                </td>
                <td class="text-muted text-small"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                <td>
                    <a href="../../controllers/admin_controller.php?action=delete_internship&id=<?= $i['internship_id'] ?>"
                       class="btn btn-danger btn-sm"
                       data-confirm="Delete internship '<?= htmlspecialchars(addslashes($i['title'])) ?>' and all its applications?">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>

</div>
</section>

<?php require_once '../../includes/footer.php'; ?>
