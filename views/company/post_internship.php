<?php
/**
 * Campus2Career - Company: Post Internship
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole('company');
$pageTitle = 'Post Internship – Campus2Career';
require_once '../../includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <div>
            <h1>Post a New Internship</h1>
            <p>Attract talented students to your organization</p>
        </div>
        <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<section class="section-sm">
<div class="container" style="max-width:760px;">
    <div class="card" style="padding:40px;">
        <h3 style="margin-bottom:6px;"><i class="fas fa-briefcase" style="color:var(--c-blue);margin-right:8px;"></i>Internship Details</h3>
        <p style="margin-bottom:28px;">Fill in the details to attract the right candidates</p>

        <form action="../../controllers/internship_controller.php" method="POST" data-validate>
            <input type="hidden" name="action" value="post">

            <div class="form-group">
                <label class="form-label">Internship Title <span style="color:var(--c-danger)">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Frontend Developer Intern" required>
                <span class="form-hint">Be specific about the role to attract relevant candidates</span>
            </div>

            <div class="form-group">
                <label class="form-label">Work Location</label>
                <input type="text" name="internship_location" class="form-control" placeholder="e.g. Kigali, Remote, Hybrid">
                <span class="form-hint">Leave blank to use your company profile location.</span>
            </div>

            <div class="form-group">
                <label class="form-label">Full Internship Description <span style="color:var(--c-danger)">*</span></label>
                <textarea name="description" class="form-control textarea-large" rows="9"
                    placeholder="Describe the internship, team, product, mentorship, duration, and the real problem the student will help solve."
                    required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Responsibilities</label>
                <textarea name="responsibilities" class="form-control textarea-large" rows="7"
                    placeholder="Use one responsibility per line. Example: Build PHP/MySQL features, participate in sprint reviews, document APIs."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Expectations / Requirements</label>
                <textarea name="expectations" class="form-control textarea-large" rows="7"
                    placeholder="Explain academic background, availability, communication expectations, and what success looks like."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Required Skills <span style="color:var(--c-danger)">*</span></label>
                <textarea name="requirements" class="form-control" rows="4"
                    placeholder="PHP, MySQL, JavaScript, HTML/CSS, Git, Communication"
                    required></textarea>
                <span class="form-hint">Separate keywords with commas. These power the skill match score.</span>
            </div>

            <div style="background:var(--c-bg); border-radius:var(--radius-sm); padding:18px; margin-bottom:24px;">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i class="fas fa-lightbulb" style="color:var(--c-warning); margin-top:3px;"></i>
                    <div>
                        <strong style="font-size:0.9rem;">Tips for a great posting:</strong>
                        <ul style="list-style:disc; padding-left:18px; margin-top:6px; font-size:0.85rem; color:var(--c-text-sub);">
                            <li>Be specific about what the intern will do day-to-day</li>
                            <li>Mention any learning opportunities or mentorship offered</li>
                            <li>List both technical and soft skill requirements</li>
                            <li>Include duration and any benefits if applicable</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-blue btn-lg">
                    <i class="fas fa-paper-plane"></i> Post Internship
                </button>
            </div>
        </form>
    </div>
</div>
</section>

<?php require_once '../../includes/footer.php'; ?>
