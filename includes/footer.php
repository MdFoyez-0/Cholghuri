</main>
<footer class="footer">
    <div class="container footer-grid">
        <div>
            <a class="brand" href="<?= BASE_URL ?>index.php">
                <img src="<?= BASE_URL ?>images/logo.png" alt="CholoGhuri Logo">
                <span>Cholo<span>Ghuri</span></span>
            </a>
            <p>Your premier gateway to exploring Bangladesh's most breathtaking horizons. From the sea of clouds in
                Sajek to the pristine corals of Saint Martin.</p>
            <div class="social-links">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i
                        class="fa-brands fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i
                        class="fa-brands fa-instagram"></i></a>
                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i
                        class="fa-brands fa-youtube"></i></a>
                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i
                        class="fa-brands fa-linkedin-in"></i></a>
            </div>
        </div>
        <div>
            <h4>Explore</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>destinations.php"><i class="fa-solid fa-chevron-right"></i> Top
                        Destinations</a></li>
                <li><a href="<?= BASE_URL ?>packages.php"><i class="fa-solid fa-chevron-right"></i> Featured Tour
                        Packages</a></li>
                <li><a href="<?= BASE_URL ?>index.php#about"><i class="fa-solid fa-chevron-right"></i> About
                        CholoGhuri</a></li>
                <li><a href="<?= BASE_URL ?>index.php#contact"><i class="fa-solid fa-chevron-right"></i> Contact &
                        Support</a></li>
            </ul>
        </div>
        <div>
            <h4>Account & Help</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>user/dashboard.php"><i class="fa-solid fa-chevron-right"></i> User
                        Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>my-bookings.php"><i class="fa-solid fa-chevron-right"></i> My Bookings</a>
                </li>
                <li><a href="<?= BASE_URL ?>user/wishlist.php"><i class="fa-solid fa-chevron-right"></i> Wishlist</a>
                </li>
                <li><a href="<?= BASE_URL ?>admin/login.php"><i class="fa-solid fa-shield-halved"></i> Admin Portal</a>
                </li>
            </ul>
        </div>
        <div>
            <h4>Contact Us</h4>
            <?php $contactAddress = get_setting($conn, 'office_address', ''); ?>
            <?php $contactPhone = get_setting($conn, 'contact_phone', ''); ?>
            <?php $contactEmail = get_setting($conn, 'contact_email', ''); ?>
            <?php if ($contactAddress !== ''): ?><p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($contactAddress, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($contactPhone !== ''): ?><p><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($contactEmail !== ''): ?><p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container text-center">
            <p>© <?= date('Y') ?> CholoGhuri Travel & Tour Management System. All rights reserved. Authentic
                destinations across Bangladesh.</p>
        </div>
    </div>
</footer>
<script src="<?= BASE_URL ?>js/script.js"></script>
</body>

</html>
