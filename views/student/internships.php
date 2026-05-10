<?php
/**
 * Campus2Career - Student: Browse and apply for internships
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/SkillMatcher.php';

requireRole('student');

$pdo = getDBConnection();
$studentId = (int)$_SESSION['role_id'];

$skillRow = $pdo->prepare("SELECT skills FROM students WHERE user_id = ?");
$skillRow->execute([$_SESSION['user_id']]);
$studentSkills = $skillRow->fetch()['skills'] ?? '';

$search = trim($_GET['search'] ?? '');
$companyFilter = trim($_GET['company'] ?? '');
$locationFilter = trim($_GET['location'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');

$params = [$studentId];
$where = "WHERE 1=1";

if ($search !== '') {
    $where .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.requirements LIKE ? OR c.company_name LIKE ? OR c.location LIKE ? OR i.internship_location LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%");
}
if ($companyFilter !== '') {
    $where .= " AND c.company_name = ?";
    $params[] = $companyFilter;
}
if ($locationFilter !== '') {
    $where .= " AND COALESCE(NULLIF(i.internship_location,''), c.location) = ?";
    $params[] = $locationFilter;
}
if ($skillFilter !== '') {
    $where .= " AND i.requirements LIKE ?";
    $params[] = "%$skillFilter%";
}

$stmt = $pdo->prepare("
    SELECT i.*, c.company_name, COALESCE(NULLIF(i.internship_location,''), c.location) AS location,
           (SELECT COUNT(*) FROM applications a2 WHERE a2.internship_id = i.internship_id) AS applicant_count,
           (SELECT application_id FROM applications WHERE student_id = ? AND internship_id = i.internship_id LIMIT 1) AS already_applied
    FROM internships i
    JOIN companies c ON c.company_id = i.company_id
    $where
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$internships = $stmt->fetchAll();

$companies = $pdo->query("SELECT DISTINCT company_name FROM companies WHERE company_name IS NOT NULL AND company_name <> '' ORDER BY company_name")->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query("
    SELECT DISTINCT COALESCE(NULLIF(i.internship_location,''), c.location) AS location
    FROM internships i JOIN companies c ON c.company_id = i.company_id
    WHERE COALESCE(NULLIF(i.internship_location,''), c.location) IS NOT NULL
      AND COALESCE(NULLIF(i.internship_location,''), c.location) <> ''
    ORDER BY location
")->fetchAll(PDO::FETCH_COLUMN);

$skillPool = [];
foreach ($internships as $job) {
    foreach (explode(',', $job['requirements'] ?? '') as $skill) {
        $skill = trim($skill);
        if ($skill !== '') {
            $skillPool[$skill] = true;
        }
    }
}
$skillOptions = array_slice(array_keys($skillPool), 0, 12);

$pageTitle = 'Browse Internships - Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Browse Internships</h1>
            <p>Search, filter, compare match scores, and apply with confidence</p>
        </div>
        <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<section class="section-sm">
<div class="container">

    <form method="GET" class="filter-bar" style="align-items:flex-end;">
        <div class="form-group" style="flex:2;margin-bottom:0;">
            <label class="form-label">Instant Search</label>
            <div class="input-icon live-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="liveSearchInput" name="search" class="form-control"
                       placeholder="Search by title, skill, company, or location"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Company</label>
            <select name="company" id="companyFilter" class="form-control" onchange="applyFilters()" style="min-width:160px;">
                <option value="">All Companies</option>
                <?php foreach ($companies as $company): ?>
                <option value="<?= htmlspecialchars($company) ?>" <?= $companyFilter === $company ? 'selected' : '' ?>>
                    <?= htmlspecialchars($company) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Location</label>
            <select name="location" id="locationFilter" class="form-control" onchange="applyFilters()" style="min-width:150px;">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= htmlspecialchars($loc) ?>" <?= $locationFilter === $loc ? 'selected' : '' ?>>
                    <?= htmlspecialchars($loc) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-blue" type="submit"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $companyFilter || $locationFilter || $skillFilter): ?>
        <a href="internships.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($skillOptions): ?>
    <div class="skill-filter-chips">
        <?php foreach ($skillOptions as $skill): ?>
        <a href="?skill=<?= urlencode($skill) ?>" class="skill-filter-chip <?= strcasecmp($skillFilter, $skill) === 0 ? 'skill-filter-active' : '' ?>" data-skill="<?= htmlspecialchars($skill) ?>">
            <?= htmlspecialchars($skill) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div id="searchResultCount" class="search-result-count"></div>
    <div style="margin-bottom:20px;color:var(--c-text-sub);font-size:0.88rem;">
        Showing <strong><?= count($internships) ?></strong> internship<?= count($internships) !== 1 ? 's' : '' ?>
    </div>

    <?php if (empty($internships)): ?>
    <div class="empty-state">
        <i class="fas fa-briefcase"></i>
        <h3>No internships found</h3>
        <p>Try a different search or check back later for new postings.</p>
    </div>
    <?php else: ?>
    <div class="internship-grid">
        <?php foreach ($internships as $job):
            $match = SkillMatcher::match($studentSkills, $job['requirements'] ?? '');
            $searchBlob = strtolower(($job['title'] ?? '') . ' ' . ($job['company_name'] ?? '') . ' ' . ($job['location'] ?? '') . ' ' . ($job['requirements'] ?? '') . ' ' . ($job['description'] ?? ''));
            $description = $job['description'] ?? '';
        ?>
        <div class="internship-card"
             data-search="<?= htmlspecialchars($searchBlob) ?>"
             data-location="<?= htmlspecialchars(strtolower($job['location'] ?? '')) ?>"
             data-company="<?= htmlspecialchars(strtolower($job['company_name'] ?? '')) ?>">
            <div class="ic-header">
                <div class="ic-logo"><i class="fas fa-building"></i></div>
                <div>
                    <div class="ic-title"><?= htmlspecialchars($job['title']) ?></div>
                    <div class="ic-company"><?= htmlspecialchars($job['company_name']) ?></div>
                </div>
            </div>
            <div class="ic-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></div>
            <div class="skill-match-bar">
                <div class="smb-header">
                    <span class="smb-label"><?= htmlspecialchars(SkillMatcher::label($match['percent'])) ?></span>
                    <span class="smb-pct" style="color:<?= SkillMatcher::color($match['percent']) ?>"><?= $match['percent'] ?>%</span>
                </div>
                <div class="smb-track"><div class="smb-fill" style="width:<?= $match['percent'] ?>%;background:<?= SkillMatcher::color($match['percent']) ?>"></div></div>
            </div>
            <div class="ic-desc"><?= htmlspecialchars(mb_substr($description, 0, 180)) ?><?= mb_strlen($description) > 180 ? '...' : '' ?></div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;color:var(--c-text-sub);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Required Skills</div>
                <div class="ic-tags">
                    <?php foreach (array_slice(explode(',', $job['requirements'] ?? ''), 0, 6) as $req): if (trim($req) === '') continue; ?>
                        <span class="ic-tag"><?= htmlspecialchars(trim($req)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ic-footer">
                <span class="ic-date">
                    <i class="fas fa-users"></i> <?= (int)$job['applicant_count'] ?> applicant<?= (int)$job['applicant_count'] !== 1 ? 's' : '' ?>
                    &nbsp;|&nbsp;
                    <i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($job['created_at'])) ?>
                </span>
                <?php if ($job['already_applied']): ?>
                    <span class="badge badge-pending"><i class="fas fa-check"></i> Applied</span>
                <?php else: ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="internship_details.php?id=<?= (int)$job['internship_id'] ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-eye"></i> Read More
                        </a>
                        <form action="../../controllers/internship_controller.php" method="POST">
                            <input type="hidden" name="action" value="apply">
                            <input type="hidden" name="internship_id" value="<?= (int)$job['internship_id'] ?>">
                            <button type="submit" class="btn btn-blue btn-sm">Apply</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</section>

<script>
function applyFilters() {
    const q = (document.getElementById('liveSearchInput')?.value || '').toLowerCase().trim();
    const loc = (document.getElementById('locationFilter')?.value || '').toLowerCase();
    const company = (document.getElementById('companyFilter')?.value || '').toLowerCase();
    const cards = document.querySelectorAll('.internship-card[data-search]');
    let shown = 0;
    cards.forEach(card => {
        const text = card.dataset.search || '';
        const cloc = card.dataset.location || '';
        const cco = card.dataset.company || '';
        const show = (!q || text.includes(q)) && (!loc || cloc === loc) && (!company || cco === company);
        card.style.display = show ? '' : 'none';
        if (show) shown++;
    });
    const counter = document.getElementById('searchResultCount');
    if (counter) counter.textContent = shown + ' internship' + (shown !== 1 ? 's' : '') + ' found';
}
document.getElementById('liveSearchInput')?.addEventListener('input', () => {
    clearTimeout(window._st);
    window._st = setTimeout(applyFilters, 180);
});
window.addEventListener('DOMContentLoaded', applyFilters);
</script>
<?php require_once '../../includes/footer.php'; ?>
