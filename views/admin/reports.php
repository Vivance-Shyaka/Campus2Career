<?php
/**
 * Campus2Career – Admin: Reports & Analytics
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('admin');
$pdo = getDBConnection();

// ── Core counts ──
$data = [
    'students'     => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'companies'    => $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'internships'  => $pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn(),
    'applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'approved'     => $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn(),
    'rejected'     => $pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn(),
    'pending'      => $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
    'interviews'   => $pdo->query("SELECT COUNT(*) FROM applications WHERE interview_date IS NOT NULL")->fetchColumn(),
];

// ── Top companies by applicants ──
$topCompanies = $pdo->query("
    SELECT c.company_name, c.location,
           COUNT(a.application_id) AS total_apps,
           COUNT(CASE WHEN a.status='approved' THEN 1 END) AS approved,
           COUNT(i.internship_id) AS total_internships
    FROM companies c
    LEFT JOIN internships i ON i.company_id = c.company_id
    LEFT JOIN applications a ON a.internship_id = i.internship_id
    GROUP BY c.company_id
    ORDER BY total_apps DESC LIMIT 8
")->fetchAll();

// ── Top students by applications ──
$topStudents = $pdo->query("
    SELECT u.name, s.university, s.course, s.skills,
           COUNT(a.application_id) AS total_apps,
           COUNT(CASE WHEN a.status='approved' THEN 1 END) AS approved
    FROM students s
    JOIN users u ON u.user_id = s.user_id
    LEFT JOIN applications a ON a.student_id = s.student_id
    GROUP BY s.student_id
    ORDER BY total_apps DESC LIMIT 8
")->fetchAll();

// ── Recent applications ──
$recentApps = $pdo->query("
    SELECT a.*, i.title, u.name AS student_name, c.company_name
    FROM applications a
    JOIN internships i ON i.internship_id = a.internship_id
    JOIN students s    ON s.student_id    = a.student_id
    JOIN users u       ON u.user_id       = s.user_id
    JOIN companies c   ON c.company_id    = i.company_id
    ORDER BY a.applied_at DESC LIMIT 10
")->fetchAll();

// ── Monthly registrations (last 6 months) ──
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS month,
           SUM(role='student')  AS students,
           SUM(role='company')  AS companies
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at ASC
")->fetchAll();

$pageTitle = 'Reports & Analytics – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1><i class="fas fa-chart-bar" style="margin-right:10px;opacity:.85"></i>Reports &amp; Analytics</h1>
            <p>Platform-wide statistics, activity logs and exportable reports</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button onclick="window.print()" class="btn btn-light">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="exportCSV()" class="btn btn-blue">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>
</div>

<section class="section-sm">
<div class="container" id="reportContent">

    <!-- ── KPI Cards ── -->
    <div class="report-kpi-grid">
        <?php
        $kpis = [
            ['fas fa-user-graduate', 'Students',       $data['students'],     '#3B82F6', 'Total registered students'],
            ['fas fa-building',      'Companies',       $data['companies'],    '#6366F1', 'Registered employers'],
            ['fas fa-briefcase',     'Internships',     $data['internships'],  '#8B5CF6', 'Active postings'],
            ['fas fa-file-alt',      'Applications',    $data['applications'], '#10B981', 'Total submitted'],
            ['fas fa-check-circle',  'Approved',        $data['approved'],     '#059669', 'Successful applications'],
            ['fas fa-times-circle',  'Rejected',        $data['rejected'],     '#EF4444', 'Unsuccessful applications'],
            ['fas fa-hourglass-half','Pending',         $data['pending'],      '#F59E0B', 'Awaiting review'],
            ['fas fa-calendar-check','Interviews',      $data['interviews'],   '#EC4899', 'Interviews scheduled'],
        ];
        foreach ($kpis as [$ico, $lbl, $val, $col, $desc]):
        ?>
        <div class="report-kpi-card">
            <div class="report-kpi-icon" style="background:<?= $col ?>18;color:<?= $col ?>">
                <i class="<?= $ico ?>"></i>
            </div>
            <div class="report-kpi-body">
                <div class="report-kpi-val" style="color:<?= $col ?>"><?= number_format($val) ?></div>
                <div class="report-kpi-label"><?= $lbl ?></div>
                <div class="report-kpi-desc"><?= $desc ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Application Rate Visual ── -->
    <div class="page-grid" style="margin-bottom:28px;">
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:8px;"></i>Application Outcomes</h3>
                <span class="text-muted text-small"><?= date('M Y') ?></span>
            </div>
            <div class="content-card-body">
                <?php
                $outcomes = [
                    ['Approved', $data['approved'], '#10B981'],
                    ['Pending',  $data['pending'],  '#F59E0B'],
                    ['Rejected', $data['rejected'], '#EF4444'],
                ];
                $total = max($data['applications'], 1);
                foreach ($outcomes as [$lbl, $val, $col]):
                    $pct = round(($val / $total) * 100);
                ?>
                <div class="report-bar-row">
                    <span class="report-bar-label"><?= $lbl ?></span>
                    <div class="report-bar-track">
                        <div class="report-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
                    </div>
                    <span class="report-bar-pct" style="color:<?= $col ?>"><?= $pct ?>%</span>
                    <span class="report-bar-num">(<?= $val ?>)</span>
                </div>
                <?php endforeach; ?>

                <div class="report-conversion">
                    <div class="report-conv-item">
                        <div class="report-conv-num"><?= $data['applications'] > 0 ? round(($data['approved']/$data['applications'])*100) : 0 ?>%</div>
                        <div class="report-conv-label">Approval Rate</div>
                    </div>
                    <div class="report-conv-item">
                        <div class="report-conv-num"><?= $data['interviews'] > 0 && $data['approved'] > 0 ? round(($data['interviews']/$data['approved'])*100) : 0 ?>%</div>
                        <div class="report-conv-label">Interview Rate</div>
                    </div>
                    <div class="report-conv-item">
                        <div class="report-conv-num"><?= $data['students'] > 0 ? round(($data['applications']/$data['students'])*100) : 0 ?>%</div>
                        <div class="report-conv-label">Application Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly registrations -->
        <div class="content-card">
            <div class="content-card-header">
                <h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:8px;"></i>Monthly Registrations</h3>
            </div>
            <div class="content-card-body">
                <?php if (empty($monthly)): ?>
                <div class="empty-state" style="padding:20px;"><i class="fas fa-chart-line"></i><p>No data yet</p></div>
                <?php else: ?>
                <div class="monthly-chart">
                    <?php
                    $maxVal = max(array_map(fn($r) => $r['students'] + $r['companies'], $monthly)) ?: 1;
                    foreach ($monthly as $row):
                        $sH = round(($row['students'] / $maxVal) * 100);
                        $cH = round(($row['companies'] / $maxVal) * 100);
                    ?>
                    <div class="monthly-bar-group">
                        <div class="monthly-bars">
                            <div class="monthly-bar" style="height:<?= $sH ?>%;background:#3B82F6;" title="<?= $row['students'] ?> students"></div>
                            <div class="monthly-bar" style="height:<?= $cH ?>%;background:#6366F1;" title="<?= $row['companies'] ?> companies"></div>
                        </div>
                        <div class="monthly-label"><?= $row['month'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="monthly-legend">
                    <span><span class="legend-dot" style="background:#3B82F6"></span> Students</span>
                    <span><span class="legend-dot" style="background:#6366F1"></span> Companies</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Top Companies Table ── -->
    <div class="content-card mb-24">
        <div class="content-card-header">
            <h3><i class="fas fa-building" style="color:var(--primary);margin-right:8px;"></i>Company Activity</h3>
            <span class="text-muted text-small">Top <?= count($topCompanies) ?> companies</span>
        </div>
        <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Internships</th>
                    <th>Applicants</th>
                    <th>Approved</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topCompanies as $idx => $co): ?>
            <tr>
                <td class="text-muted"><?= $idx + 1 ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="user-avatar" style="width:32px;height:32px;font-size:.85rem;">
                            <?= strtoupper(substr($co['company_name'],0,1)) ?>
                        </div>
                        <strong><?= htmlspecialchars($co['company_name']) ?></strong>
                    </div>
                </td>
                <td class="text-muted text-small"><?= htmlspecialchars($co['location']) ?></td>
                <td><span class="badge badge-approved"><?= $co['total_internships'] ?></span></td>
                <td><?= $co['total_apps'] ?></td>
                <td><span class="badge badge-approved"><?= $co['approved'] ?></span></td>
                <td>
                    <?php $rate = $co['total_apps'] > 0 ? round(($co['approved']/$co['total_apps'])*100) : 0; ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="report-mini-bar">
                            <div style="width:<?= $rate ?>%;background:#10B981;height:100%;border-radius:4px;"></div>
                        </div>
                        <span style="font-size:.78rem;font-weight:600;color:#10B981"><?= $rate ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ── Top Students Table ── -->
    <div class="content-card mb-24">
        <div class="content-card-header">
            <h3><i class="fas fa-user-graduate" style="color:var(--primary);margin-right:8px;"></i>Student Activity</h3>
            <span class="text-muted text-small">Most active students</span>
        </div>
        <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>#</th><th>Student</th><th>University</th><th>Course</th><th>Applications</th><th>Approved</th></tr>
            </thead>
            <tbody>
            <?php foreach ($topStudents as $idx => $st): ?>
            <tr>
                <td class="text-muted"><?= $idx + 1 ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="user-avatar" style="width:32px;height:32px;font-size:.85rem;">
                            <?= strtoupper(substr($st['name'],0,1)) ?>
                        </div>
                        <strong><?= htmlspecialchars($st['name']) ?></strong>
                    </div>
                </td>
                <td class="text-small text-muted"><?= htmlspecialchars($st['university']) ?></td>
                <td class="text-small text-muted"><?= htmlspecialchars($st['course']) ?></td>
                <td><?= $st['total_apps'] ?></td>
                <td><span class="badge badge-approved"><?= $st['approved'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ── Recent Applications ── -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-file-alt" style="color:var(--primary);margin-right:8px;"></i>Recent Applications</h3>
        </div>
        <div class="table-wrapper">
        <table class="table" id="recentAppsTable">
            <thead>
                <tr><th>Student</th><th>Internship</th><th>Company</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentApps as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['student_name']) ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td class="text-muted text-small"><?= htmlspecialchars($a['company_name']) ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td class="text-muted text-small"><?= date('M j, Y', strtotime($a['applied_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="report-footer-note">
        <i class="fas fa-info-circle"></i>
        Report generated on <?= date('l, F j, Y \a\t g:i A') ?> by <?= htmlspecialchars($_SESSION['name']) ?> (Admin)
    </div>

</div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<!-- Charts Section -->
<div class="page-grid" style="margin-bottom:28px;">
    <div class="content-card">
        <div class="content-card-header"><h3><i class="fas fa-chart-doughnut" style="color:var(--primary);margin-right:8px;"></i>Applications Breakdown</h3></div>
        <div class="content-card-body" style="display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="position:relative;width:220px;height:220px;">
                <canvas id="doughnutChart"></canvas>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                    <div style="font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800;color:var(--c-text)"><?= $data['applications'] ?></div>
                    <div style="font-size:.72rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.05em;">Total</div>
                </div>
            </div>
        </div>
    </div>
    <div class="content-card">
        <div class="content-card-header"><h3><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i>Platform Growth</h3></div>
        <div class="content-card-body"><canvas id="barChart" height="180"></canvas></div>
    </div>
</div>

<script>
// CSV Export
function exportCSV() {
    const rows = [['Student','Internship','Company','Status','Date']];
    document.querySelectorAll('#recentAppsTable tbody tr').forEach(tr => {
        const cells = [...tr.querySelectorAll('td')].map(td => '"' + td.textContent.trim().replace(/"/g,'""') + '"');
        rows.push(cells);
    });
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'campus2career-report-<?= date('Y-m-d') ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// ── Doughnut Chart ──
const dCtx = document.getElementById('doughnutChart');
if (dCtx) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    new Chart(dCtx, {
        type: 'doughnut',
        data: {
            labels: ['Approved','Pending','Rejected'],
            datasets: [{
                data: [<?= $data['approved'] ?>, <?= $data['pending'] ?>, <?= $data['rejected'] ?>],
                backgroundColor: ['#10B981','#F59E0B','#EF4444'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            cutout: '72%',
            plugins: { legend: { position: 'bottom', labels: { color: isDark?'#9CA3AF':'#4B5563', padding:12, font:{size:11} } } }
        }
    });
}

// ── Bar Chart (monthly) ──
const bCtx = document.getElementById('barChart');
if (bCtx) {
    const isDark2 = document.documentElement.getAttribute('data-theme') === 'dark';
    <?php
    $months   = array_column($monthly, 'month');
    $students = array_column($monthly, 'students');
    $comps    = array_column($monthly, 'companies');
    ?>
    new Chart(bCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months ?: ['No data']) ?>,
            datasets: [
                { label:'Students', data: <?= json_encode(array_map('intval',$students)) ?>, backgroundColor:'#3B82F6', borderRadius:4 },
                { label:'Companies', data: <?= json_encode(array_map('intval',$comps)) ?>, backgroundColor:'#6366F1', borderRadius:4 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:true,
            plugins:{ legend:{ labels:{ color:isDark2?'#9CA3AF':'#4B5563', font:{size:11} } } },
            scales:{
                x:{ ticks:{ color:isDark2?'#9CA3AF':'#4B5563' }, grid:{ color:isDark2?'#1F2937':'#F3F4F6' } },
                y:{ ticks:{ color:isDark2?'#9CA3AF':'#4B5563' }, grid:{ color:isDark2?'#1F2937':'#F3F4F6' } }
            }
        }
    });
}
</script>

<style>
@media print {
    .navbar, .page-banner .btn, .flash-message, button { display: none !important; }
    body { background: white !important; color: #000 !important; }
    .content-card, .report-kpi-card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .report-kpi-val { color: #000 !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
