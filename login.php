<?php
/**
 * Campus2Career - Login Page
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header('Location: ' . BASE_URL . ($role === 'student' ? 'views/student/dashboard.php' : ($role === 'company' ? 'views/company/dashboard.php' : 'views/admin/dashboard.php')));
    exit;
}

$pageTitle = 'Login – Campus2Career';
require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card fade-up">
        <div class="auth-logo">
            <div class="nav-brand" style="justify-content:center; margin-bottom:8px;">
                <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="brand-text">Campus<span>2</span>Career</span>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to your account to continue</p>
        </div>

        <form action="controllers/auth_controller.php" method="POST" data-validate>
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;justify-content:space-between;">
                    Password
                    <a href="#" style="font-weight:400; font-size:0.82rem; color:var(--c-mid);">Forgot password?</a>
                </label>
                <div class="input-icon" style="position:relative;">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required style="padding-right:44px;">
                    <button type="button" class="toggle-password" data-target="#loginPassword"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--c-text-sub);">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-blue btn-block btn-lg">
                Sign In <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-divider"><span>or sign in as</span></div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <a href="#" onclick="prefill('john@student.com','123456')" class="btn btn-outline" style="font-size:0.82rem;">
                <i class="fas fa-graduation-cap"></i> Student Demo
            </a>
            <a href="#" onclick="prefill('mtn@company.com','123456')" class="btn btn-outline" style="font-size:0.82rem;">
                <i class="fas fa-building"></i> Company Demo
            </a>
        </div>
       
       <!-- <div style="text-align:center; margin-top:10px;">
            <a href="#" onclick="prefill('admin@c2c.com','admin123')" class="btn btn-outline" style="font-size:0.82rem; width:100%;">
                <i class="fas fa-shield-alt"></i> Admin Demo
            </a>
        </div>
        -->

        <div class="auth-switch">
            Don't have an account? <a href="register.php">Create one free</a>
        </div>
    </div>
</section>

<script>
function prefill(email, pw) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = pw;
}
</script>

<?php require_once 'includes/footer.php'; ?>
