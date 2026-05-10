<?php
/**
 * Campus2Career - Register Page
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$defaultRole = $_GET['role'] ?? 'student';
$pageTitle   = 'Register – Campus2Career';
require_once 'includes/header.php';
?>

<section class="auth-page" style="align-items: flex-start; padding-top: 48px;">
    <div class="auth-card fade-up" style="max-width: 540px;">
        <div class="auth-logo">
            <div class="nav-brand" style="justify-content:center; margin-bottom:8px;">
                <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="brand-text">Campus<span>2</span>Career</span>
            </div>
            <h2>Create Your Account</h2>
            <p>Join thousands of students and companies</p>
        </div>

        <!-- Role Tabs -->
        <div class="role-tabs">
            <button type="button" class="role-tab <?= $defaultRole === 'student' ? 'active' : '' ?>" data-role="student">
                <i class="fas fa-graduation-cap"></i> Student
            </button>
            <button type="button" class="role-tab <?= $defaultRole === 'company' ? 'active' : '' ?>" data-role="company">
                <i class="fas fa-building"></i> Company
            </button>
        </div>

        <form action="controllers/auth_controller.php" method="POST" data-validate>
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($defaultRole) ?>">

            <!-- Common Fields -->
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" class="form-control" placeholder="Your full name" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>
            </div>
            <div class="page-grid" style="gap:16px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="regPassword" class="form-control" placeholder="Min. 6 characters" required data-minlength="6" style="padding-right:44px;">
                        <button type="button" class="toggle-password" data-target="#regPassword"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--c-text-sub);">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>
            </div>

            <!-- Student Fields -->
            <div id="section-student" class="role-section mt-24" style="display: <?= $defaultRole === 'student' ? 'block' : 'none' ?>;">
                <div class="divider"></div>
                <p style="font-size:0.82rem; color:var(--c-blue); font-weight:600; margin-bottom:16px; text-transform:uppercase; letter-spacing:0.05em;"><i class="fas fa-graduation-cap"></i> Student Details</p>
                <div class="page-grid" style="gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">University</label>
                        <input type="text" name="university" class="form-control" placeholder="e.g. AUCA">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Course / Major</label>
                        <input type="text" name="course" class="form-control" placeholder="e.g. Computer Science">
                    </div>
                </div>
                <div class="form-group mt-16">
                    <label class="form-label">Skills <span style="font-weight:400;color:var(--c-text-sub)">(comma-separated)</span></label>
                    <input type="text" name="skills" class="form-control" placeholder="e.g. HTML, CSS, JavaScript, PHP">
                    <span class="form-hint">List your key technical and soft skills</span>
                </div>
            </div>

            <!-- Company Fields -->
            <div id="section-company" class="role-section mt-24" style="display: <?= $defaultRole === 'company' ? 'block' : 'none' ?>;">
                <div class="divider"></div>
                <p style="font-size:0.82rem; color:var(--c-blue); font-weight:600; margin-bottom:16px; text-transform:uppercase; letter-spacing:0.05em;"><i class="fas fa-building"></i> Company Details</p>
                <div class="page-grid" style="gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. MTN Rwanda">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g. Kigali">
                    </div>
                </div>
            </div>

            <div class="mt-24">
                <button type="submit" class="btn btn-blue btn-block btn-lg">
                    Create Account <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <p style="font-size:0.78rem; color:var(--c-text-sub); text-align:center; margin-top:14px;">
                By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </p>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
