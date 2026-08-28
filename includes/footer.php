<?php
/**
 * Site footer
 */
?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>Need Help?</h3>
                <ul>
                    <li><a href="<?= APP_URL ?>/contact.php">Contact Us</a></li>
                    <li><a href="<?= APP_URL ?>/products.php">Browse Products</a></li>
                    <li><a href="<?= APP_URL ?>/cart.php">Shopping Cart</a></li>
                    <li><a href="<?= APP_URL ?>/checkout.php">Checkout</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#shipping">Delivery Options</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#returns">Returns Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>About <?= STORE_NAME ?></h3>
                <ul>
                    <li><a href="<?= APP_URL ?>/about.php">Our Story</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#quality">Quality Promise</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#sustainability">Sustainability</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#terms">Terms & Conditions</a></li>
                    <li><a href="<?= APP_URL ?>/about.php#privacy">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Shop Categories</h3>
                <ul>
                    <?php
                        $footer_cats = get_storefront_categories();
                        foreach (array_slice($footer_cats, 0, 6) as $cat):
                        ?>
                    <li><a href="<?= APP_URL ?>/products.php?category=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Join Us On</h3>
                <div class="social-icons">
                    <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
                <h3 class="footer-subtitle">Newsletter</h3>
                <p class="footer-text">Subscribe for exclusive drops, early access, and style updates.</p>
                <form class="newsletter-form" action="<?= APP_URL ?>/actions/newsletter.php" method="post" id="newsletterForm">
                    <input type="email" name="email" placeholder="Enter email address" required aria-label="Email for newsletter">
                    <button type="submit">Join</button>
                </form>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date('Y') ?> <?= STORE_NAME ?> — All Rights Reserved. Shop the Brands That Set You Apart.
        </div>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
