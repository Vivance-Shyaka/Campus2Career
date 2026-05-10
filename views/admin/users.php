<?php
/**
 * Campus2Career - Admin: Manage Users
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');
$pdo = getDBConnection();

$filterRole = $_GET['role'] ?? 'all';
$search     = trim($_GET['search'] ?? '');

$params = [];
$where  = "WHERE 1=1";
if ($filterRole !== 'all') {
    $where  .= " AND u.role = ?";
    $params[] = $filterRole;
}
if (!empty($search)) {
    $where  .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT u.*,
        COALESCE(s.university, c.company_name, 'Admin') AS detail,
        COALESCE(s.course, c.location, '') AS detail2,
        COALESCE(u.account_status, 'active') AS account_status
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN companies c ON c.user_id = u.user_id
    $where
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Count per role
$roleCounts = $pdo->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll();
$roleCounts = array_column($roleCounts, 'cnt', 'role');

$pageTitle = 'Manage Users – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Manage Users</h1>
            <p>View and manage all platform users</p>
        </div>
        <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<section class="section-sm">
<div class="container">

    <!-- Filter Bar -->
    <form method="GET" class="filter-bar">
        <div class="form-group" style="flex:2;">
            <label class="form-label">Search Users</label>
            <div class="input-icon">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?= htmlspecialchars($search) ?>" id="tableSearch">
            </div>
        </div>
        <div class="form-group" style="min-width:180px;">
            <label class="form-label">Filter by Role</label>
            <select name="role" class="form-control" onchange="this.form.submit()">
                <option value="all">All Roles (<?= array_sum($roleCounts) ?>)</option>
                <option value="student"  <?= $filterRole==='student'  ? 'selected':'' ?>>Students (<?= $roleCounts['student']  ?? 0 ?>)</option>
                <option value="company"  <?= $filterRole==='company'  ? 'selected':'' ?>>Companies (<?= $roleCounts['company']  ?? 0 ?>)</option>
                <option value="admin"    <?= $filterRole==='admin'    ? 'selected':'' ?>>Admins (<?= $roleCounts['admin']    ?? 0 ?>)</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-blue">Search</button>
            <?php if ($search || $filterRole !== 'all'): ?>
            <a href="users.php" class="btn btn-outline" style="margin-left:8px;">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-users" style="color:var(--c-blue);margin-right:8px;"></i>
                Users <span style="font-weight:400;color:var(--c-text-sub)">(<?= count($users) ?>)</span>
            </h3>
        </div>
        <div class="table-wrapper">
        <?php if (empty($users)): ?>
        <div class="empty-state"><i class="fas fa-users"></i><h3>No users found</h3></div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Details</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="filterable-row">
                <td>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <div class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                        <div>
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                            <?php if (($u['account_status'] ?? 'active') === 'disabled'): ?>
                            <span class="badge badge-disabled" style="margin-left:6px;">Disabled</span>
                            <?php endif; ?>
                            <br>
                            <span class="text-small text-muted"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                    </div>
                </td>
                <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td class="text-small text-muted">
                    <?= htmlspecialchars($u['detail']) ?>
                    <?php if ($u['detail2']): ?><br><?= htmlspecialchars($u['detail2']) ?><?php endif; ?>
                </td>
                <td class="text-muted text-small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if (($u['account_status'] ?? 'active') === 'active'): ?>
                        <a href="../../controllers/admin_controller.php?action=toggle_status&id=<?= $u['user_id'] ?>&toggle=disable"
                           class="btn btn-outline btn-sm"
                           data-confirm="Disable this account? The user won't be able to log in."
                           title="Disable account">
                            <i class="fas fa-ban"></i>
                        </a>
                        <?php else: ?>
                        <a href="../../controllers/admin_controller.php?action=toggle_status&id=<?= $u['user_id'] ?>&toggle=enable"
                           class="btn btn-success btn-sm"
                           title="Re-enable account">
                            <i class="fas fa-check"></i>
                        </a>
                        <?php endif; ?>
                        <a href="../../controllers/admin_controller.php?action=delete_user&id=<?= $u['user_id'] ?>"
                           class="btn btn-danger btn-sm"
                           data-confirm="Permanently delete user '<?= htmlspecialchars(addslashes($u['name'])) ?>'? All their data will be removed.">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <span class="text-muted text-small">Current user</span>
                    <?php endif; ?>
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
