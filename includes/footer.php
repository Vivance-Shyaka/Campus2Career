<?php
/**
 * Campus2Career - Shared Footer
 */
?>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <span class="brand-icon"><i class="fas fa-graduation-cap"></i></span>
            <span class="brand-text">Campus<span>2</span>Career</span>
            <p>Bridging the gap between education and opportunity.</p>
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4>For Students</h4>
                <a href="<?= BASE_URL ?>register.php">Register</a>
                <a href="<?= BASE_URL ?>views/public/internships.php">Browse Internships</a>
            </div>
            <div class="footer-col">
                <h4>For Companies</h4>
                <a href="<?= BASE_URL ?>register.php">Post Internships</a>
                <a href="<?= BASE_URL ?>login.php">Company Login</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> Campus2Career. All rights reserved.</p>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
