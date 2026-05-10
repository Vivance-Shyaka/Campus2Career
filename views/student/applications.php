<?php
/**
 * Campus2Career – Student: My Applications
 * Redesigned to match company/applicants.php style
 * Dark-mode safe · fully responsive
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('student');
$pdo        = getDBConnection();
$student_id = $_SESSION['role_id'];

$filterStatus = $_GET['status'] ?? 'all';
$params       = [$student_id];
$where        = "WHERE a.student_id = ?";
if ($filterStatus !== 'all') {
    $where  .= " AND a.status = ?";
    $params[] = $filterStatus;
}

$stmt = $pdo->prepare("
    SELECT a.*, i.title, i.requirements, i.description,
           c.company_name, c.location
    FROM applications a
    JOIN internships i  ON i.internship_id  = a.internship_id
    JOIN companies  c  ON c.company_id      = i.company_id
    $where
    ORDER BY a.applied_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Counts per status
$counts = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt FROM applications WHERE student_id = ? GROUP BY status"
);
$counts->execute([$student_id]);
$statusCounts = array_column($counts->fetchAll(), 'cnt', 'status');
$totalCount   = array_sum($statusCounts);

$pageTitle = 'My Applications – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>My Applications</h1>
            <p>Track the status of all your internship applications</p>
        </div>
        <a href="internships.php" class="btn btn-light">
            <i class="fas fa-search"></i> Find More Internships
        </a>
    </div>
</div>

<section class="section-sm">
<div class="container">

    <!-- ── Quick-stats strip ── -->
    <div class="app-stats-strip">
        <?php
        $strip = [
            ['all',      'fas fa-layer-group',   $totalCount,                    'Total',    ''],
            ['pending',  'fas fa-hourglass-half', $statusCounts['pending']  ?? 0, 'Pending',  'yellow'],
            ['approved', 'fas fa-check-circle',   $statusCounts['approved'] ?? 0, 'Approved', 'green'],
            ['rejected', 'fas fa-times-circle',   $statusCounts['rejected'] ?? 0, 'Rejected', 'red'],
        ];
        foreach ($strip as [$val, $ico, $num, $lbl, $col]):
            $active = $filterStatus === $val;
        ?>
        <a href="?status=<?= $val ?>"
           class="app-stat-chip <?= $active ? 'app-stat-chip-active' : '' ?> <?= $col ? "app-stat-chip-$col" : '' ?>">
            <i class="<?= $ico ?>"></i>
            <span class="app-stat-num"><?= $num ?></span>
            <span class="app-stat-lbl"><?= $lbl ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Empty state ── -->
    <?php if (empty($applications)): ?>
    <div class="empty-state">
        <i class="fas fa-file-alt"></i>
        <h3>No applications <?= $filterStatus !== 'all' ? "with status \"$filterStatus\"" : 'yet' ?></h3>
        <p>Browse internships and apply to start your career journey!</p>
        <a href="internships.php" class="btn btn-blue mt-16">
            <i class="fas fa-search"></i> Browse Internships
        </a>
    </div>

    <?php else: ?>
    <div class="applicant-list">

        <?php foreach ($applications as $idx => $app):
            // first letter for logo
            $initial = strtoupper(substr($app['company_name'], 0, 1));
            // color cycling for company logo
            $colors  = ['#3B82F6','#6366F1','#8B5CF6','#10B981','#F59E0B','#EC4899'];
            $color   = $colors[$idx % count($colors)];
        ?>
        <div class="applicant-card">

            <!-- ── TOP ROW ── -->
            <div class="applicant-card-top">

                <!-- Company logo + internship info -->
                <div class="applicant-left">
                    <div class="app-company-logo" style="background:<?= $color ?>20;color:<?= $color ?>">
                        <?= $initial ?>
                    </div>
                    <div class="applicant-info">
                        <h3 class="applicant-name"><?= htmlspecialchars($app['title']) ?></h3>
                        <div class="applicant-meta">
                            <span><i class="fas fa-building"></i> <?= htmlspecialchars($app['company_name']) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['location']) ?></span>
                            <span><i class="fas fa-calendar-alt"></i> Applied <?= date('M j, Y', strtotime($app['applied_at'])) ?></span>
                        </div>
                        <?php if (!empty($app['requirements'])): ?>
                        <div class="ic-tags" style="margin-top:10px;">
                            <?php foreach (array_slice(explode(',', $app['requirements']), 0, 5) as $req): ?>
                                <span class="ic-tag"><?= htmlspecialchars(trim($req)) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status badge + date -->
                <div class="applicant-right">
                    <span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                    <span class="applicant-date">
                        <i class="fas fa-clock"></i>
                        <?= date('M j, Y', strtotime($app['applied_at'])) ?>
                    </span>
                </div>
            </div>

            <!-- ── STATUS BANNERS ── -->

            <?php if ($app['interview_date']): ?>
            <!-- Interview scheduled banner -->
            <div class="interview-scheduled-banner">
                <div class="isb-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="isb-body">
                    <div class="isb-title">Interview Scheduled</div>
                    <div class="isb-datetime">
                        <i class="fas fa-calendar-day"></i>
                        <?= date('l, F j, Y', strtotime($app['interview_date'])) ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-clock"></i>
                        <?= date('g:i A', strtotime($app['interview_date'])) ?>
                    </div>
                    <div class="isb-note">
                        <i class="fas fa-envelope"></i>
                        Check your email for details and confirm your attendance.
                    </div>
                    <?php if (!empty($app['interview_location'])): ?>
                    <div class="isb-note">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($app['interview_location']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($app['interview_notes'])): ?>
                    <div class="isb-note">
                        <i class="fas fa-clipboard-list"></i>
                        <?= nl2br(htmlspecialchars($app['interview_notes'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="isb-badge">Confirmed</div>
            </div>

            <?php elseif ($app['status'] === 'approved'): ?>
            <!-- Approved (no interview yet) -->
            <div class="app-status-banner app-status-approved">
                <div class="asb-icon"><i class="fas fa-check-circle"></i></div>
                <div class="asb-body">
                    <div class="asb-title">Application Approved! 🎉</div>
                    <div class="asb-text">
                        Congratulations! <strong><?= htmlspecialchars($app['company_name']) ?></strong>
                        has approved your application. They will contact you soon to schedule an interview.
                    </div>
                </div>
            </div>

            <?php elseif ($app['status'] === 'rejected'): ?>
            <!-- Rejected -->
            <div class="app-status-banner app-status-rejected">
                <div class="asb-icon"><i class="fas fa-times-circle"></i></div>
                <div class="asb-body">
                    <div class="asb-title">Not Selected This Time</div>
                    <div class="asb-text">
                        <strong><?= htmlspecialchars($app['company_name']) ?></strong>
                        has decided not to move forward with your application.
                        Don't give up — keep applying and the right opportunity will come!
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Pending -->
            <div class="app-status-banner app-status-pending">
                <div class="asb-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="asb-body">
                    <div class="asb-title">Under Review</div>
                    <div class="asb-text">
                        Your application is being reviewed by
                        <strong><?= htmlspecialchars($app['company_name']) ?></strong>.
                        You'll receive an email notification as soon as there's an update.
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.applicant-card -->
        <?php endforeach; ?>
    </div><!-- /.applicant-list -->
    <?php endif; ?>

</div>
</section>

<?php require_once '../../includes/footer.php'; ?>
