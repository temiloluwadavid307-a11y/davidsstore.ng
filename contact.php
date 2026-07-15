<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact Us — ' . APP_NAME;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name)) $errors[] = 'Name is required.';
    if (!is_valid_email($email)) $errors[] = 'Valid email is required.';
    if (empty($subject)) $errors[] = 'Subject is required.';
    if (empty($message)) $errors[] = 'Message is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
        if ($stmt->execute()) {
            set_flash('success', 'Your message has been sent! We\'ll get back to you within 24 hours.');
            redirect(APP_URL . '/contact.php');
        } else {
            $errors[] = 'Failed to send message. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container content-page">
    <div class="content-hero">
        <h1>Contact Us</h1>
        <p>Have a question, feedback, or need help with your order? We're here for you.</p>
    </div>

    <div class="contact-layout">
        <div class="contact-info-card">
            <h2 style="margin-bottom:24px;font-size:20px;">Get In Touch</h2>
            <div class="contact-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Address</h4>
                    <p>14 Admiralty Way, Lekki Phase 1<br>Lagos, Nigeria</p>
                </div>
            </div>
            <div class="contact-info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h4>Phone</h4>
                    <p>+234 801 234 5678<br>Mon–Sat, 9am–6pm WAT</p>
                </div>
            </div>
            <div class="contact-info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email</h4>
                    <p>support@davidsstore.ng<br>orders@davidsstore.ng</p>
                </div>
            </div>
            <div class="contact-info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h4>Business Hours</h4>
                    <p>Monday – Saturday: 9:00 AM – 6:00 PM<br>Sunday: Closed</p>
                </div>
            </div>
        </div>

        <div class="form-card">
            <h1>Send a Message</h1>
            <p class="subtitle">Fill out the form and we'll respond within 24 hours.</p>

            <?php if ($errors): ?>
            <div style="background:#fef2f2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <ul style="margin:0;padding-left:16px;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone (optional)</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a topic</option>
                        <option value="Order Inquiry" <?= ($_POST['subject'] ?? '') === 'Order Inquiry' ? 'selected' : '' ?>>Order Inquiry</option>
                        <option value="Product Question" <?= ($_POST['subject'] ?? '') === 'Product Question' ? 'selected' : '' ?>>Product Question</option>
                        <option value="Returns & Refunds" <?= ($_POST['subject'] ?? '') === 'Returns & Refunds' ? 'selected' : '' ?>>Returns & Refunds</option>
                        <option value="Partnership" <?= ($_POST['subject'] ?? '') === 'Partnership' ? 'selected' : '' ?>>Partnership</option>
                        <option value="Other" <?= ($_POST['subject'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required rows="5"><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
