<?php
/**
 * Campus2Career - Shared Header
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = getFlash();
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Campus2Career') ?></title>
    <script>
      // Apply saved theme instantly to prevent flash
      (function(){
        var t = localStorage.getItem('c2c-theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);
      })();
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>index.php" class="nav-brand">
            <span class="brand-icon"><i class="fas fa-graduation-cap"></i></span>
            <span class="brand-text">Campus<span>2</span>Career</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <?php if (!$currentUser): ?>
                <li><a href="<?= BASE_URL ?>index.php" class="nav-link">Home</a></li>
                <li><a href="<?= BASE_URL ?>views/public/internships.php" class="nav-link">Internships</a></li>
                <li><a href="<?= BASE_URL ?>login.php" class="nav-link">Login</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="nav-btn">Get Started</a></li>
            <?php elseif ($role === 'student'): ?>
                <li><a href="<?= BASE_URL ?>views/student/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>views/student/internships.php" class="nav-link">Internships</a></li>
                <li><a href="<?= BASE_URL ?>views/student/applications.php" class="nav-link">My Applications</a></li>
                <li><a href="<?= BASE_URL ?>views/student/profile.php" class="nav-link">Profile</a></li>
            <?php elseif ($role === 'company'): ?>
                <li><a href="<?= BASE_URL ?>views/company/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>views/company/post_internship.php" class="nav-link">Post Internship</a></li>
                <li><a href="<?= BASE_URL ?>views/company/applicants.php" class="nav-link">Applicants</a></li>
                <li><a href="<?= BASE_URL ?>views/company/profile.php" class="nav-link">Profile</a></li>
            <?php elseif ($role === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>views/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>views/admin/users.php" class="nav-link">Users</a></li>
                <li><a href="<?= BASE_URL ?>views/admin/internships.php" class="nav-link">Internships</a></li>
                <li><a href="<?= BASE_URL ?>views/admin/reports.php" class="nav-link">Reports</a></li>
            <?php endif; ?>

            <?php if ($currentUser): ?>
                <li class="nav-user-menu">
                    <button class="nav-user-btn" id="userMenuBtn">
                        <span class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></span>
                        <span class="user-name-short"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong><?= htmlspecialchars($currentUser['name']) ?></strong>
                            <span class="role-badge role-<?= $role ?>"><?= ucfirst($role) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>controllers/auth_controller.php?action=logout" class="dropdown-item logout-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($currentUser): ?>
            <li>
                <button class="notif-bell-btn" id="notifBellBtn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notif-bell-dot" id="notifDot" style="display:none;"></span>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-drop-header">
                        <span>Notifications</span>
                        <button onclick="markNotifRead()" class="notif-mark-read">Mark all read</button>
                    </div>
                    <div id="notifList"><div class="notif-loading"><i class="fas fa-spinner fa-spin"></i></div></div>
                </div>
            </li>
            <?php endif; ?>
            <li>
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <span class="toggle-track">
                        <span class="toggle-knob"></span>
                    </span>
                    <span class="toggle-icon" id="toggleIcon">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </span>
                    <span class="toggle-label" id="toggleLabel">Dark</span>
                </button>
            </li>
        </ul>
    </div>
</nav>

<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'times-circle' : 'info-circle') ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
    <button class="flash-close" onclick="document.getElementById('flashMsg').remove()">×</button>
</div>
<?php endif; ?>

<main class="main-content">
