<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'About Us — ' . STORE_NAME;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container content-page">
    <div class="content-hero">
        <h1>About <?= STORE_NAME ?></h1>
        <p>Nigeria's destination for premium fashion, streetwear, and lifestyle essentials — curated for those who demand quality.</p>
    </div>

    <div class="content-section" id="story">
        <h2>Our Story</h2>
        <p><?= STORE_NAME ?> was born from a simple belief: Nigerians deserve access to world-class fashion without compromising on quality or authenticity. What started as a passion project in Lagos has grown into a premium online destination serving style-conscious customers across the country.</p>
        <p>We partner with the finest local and international brands to bring you carefully curated collections — from heavyweight hoodies and oversized tees to limited-edition sneakers and handcrafted accessories. Every piece in our catalog is selected for its craftsmanship, durability, and style.</p>
    </div>

    <div class="content-section" id="quality">
        <h2>Quality Promise</h2>
        <p>We don't do fast fashion. Every product on <?= STORE_NAME ?> meets our strict quality standards — premium materials, expert construction, and honest pricing. If it doesn't meet our bar, it doesn't make it to our shelves.</p>
        <div class="values-grid">
            <div class="value-card">
                <i class="fas fa-gem"></i>
                <h3>Premium Materials</h3>
                <p>Only the finest fabrics and materials make it into our collection.</p>
            </div>
            <div class="value-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Authentic Products</h3>
                <p>100% genuine products sourced directly from trusted brands and makers.</p>
            </div>
            <div class="value-card">
                <i class="fas fa-heart"></i>
                <h3>Customer First</h3>
                <p>Your satisfaction drives everything we do — from selection to delivery.</p>
            </div>
        </div>
    </div>

    <div class="content-section" id="shipping">
        <h2>Delivery Options</h2>
        <p><strong>Standard Delivery:</strong> 3–5 business days within Lagos, 5–7 business days nationwide. <?= format_price(1500) ?> shipping fee applies on orders below our free-shipping threshold.</p>
        <p><strong>Express Delivery:</strong> Available in Lagos (1–2 business days) for an additional <?= format_price(3000) ?>.</p>
    </div>

    <div class="content-section" id="returns">
        <h2>Returns Policy</h2>
        <p>We offer a 7-day return policy on unworn items with original tags attached. Contact our support team to initiate a return. Refunds are processed within 5–7 business days after we receive the returned item.</p>
        <p>Items marked as final sale or purchased during flash sales may not be eligible for return. Defective items will be replaced or refunded regardless of the return window.</p>
    </div>

    <div class="content-section" id="sustainability">
        <h2>Sustainability</h2>
        <p>We're committed to responsible fashion. We prioritize brands using organic and recycled materials, minimize packaging waste, and support local artisans and manufacturers across Nigeria.</p>
    </div>

    <div class="content-section" id="terms">
        <h2>Terms & Conditions</h2>
        <p>By using <?= STORE_NAME ?>, you agree to our terms of service. All prices are listed in Nigerian Naira (₦) and include applicable taxes. We reserve the right to modify prices and availability without prior notice.</p>
    </div>

    <div class="content-section" id="privacy">
        <h2>Privacy Policy</h2>
        <p>We respect your privacy. Personal information collected during checkout is used solely for order processing and communication. We never sell or share your data with third parties. You may unsubscribe from our newsletter at any time.</p>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
