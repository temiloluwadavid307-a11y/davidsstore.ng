<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/paystack.php';

init_cart();
$cart_items = $_SESSION['cart'] ?? [];

if (empty($cart_items)) {
    set_flash('error', 'Your cart is empty. Add products before checkout.');
    redirect(APP_URL . '/cart.php');
}

$page_title = 'Checkout — ' . STORE_NAME;
$subtotal = cart_total();
$shipping = $subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : 1500;
$total = $subtotal + $shipping;
$user = current_user();
$errors = [];
$paystack_public_key = PAYSTACK_PUBLIC_KEY;
$paystack_reference = $_SESSION['paystack_reference'] ?? ('DS-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -6)));
$initiate_paystack = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['paystack'] ?? '') === 'success') {
    $pending_checkout = $_SESSION['pending_checkout'] ?? null;
    $reference = sanitize($_GET['reference'] ?? '');

    if ($pending_checkout) {
        // Verify Paystack payment server-side when secret is configured
        $payment_verified = false;
        $reference = $reference ?: ($_GET['reference'] ?? '');
        if (!empty($reference) && defined('PAYSTACK_SECRET_KEY') && PAYSTACK_SECRET_KEY) {
            $verifyUrl = 'https://api.paystack.co/transaction/verify/' . urlencode($reference);
            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
                'Cache-Control: no-cache',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($resp && !$err) {
                $data = json_decode($resp, true);
                if (!empty($data['status']) && $data['status'] === true && !empty($data['data']['status']) && $data['data']['status'] === 'success') {
                    $payment_verified = true;
                }
            }
        }
        $order_number = 'DS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $user_id = $user ? $user['id'] : null;
        $stmt = $conn->prepare('
            INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,
                shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "paystack")
        ');
        $stmt->bind_param(
            'sisssssssddd',
            $order_number, $user_id, $pending_checkout['name'], $pending_checkout['email'], $pending_checkout['phone'],
            $pending_checkout['address'], $pending_checkout['city'], $pending_checkout['state'], $pending_checkout['notes'],
            $pending_checkout['subtotal'], $pending_checkout['shipping'], $pending_checkout['total']
        );

        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            $item_stmt = $conn->prepare('
                INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');

            foreach ($pending_checkout['items'] as $item) {
                $line_total = $item['price'] * $item['quantity'];
                $item_stmt->bind_param(
                    'iissidd',
                    $order_id, $item['id'], $item['name'], $item['sku'],
                    $item['quantity'], $item['price'], $line_total
                );
                $item_stmt->execute();

                $stock_stmt = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
                $stock_stmt->bind_param('iii', $item['quantity'], $item['id'], $item['quantity']);
                $stock_stmt->execute();
            }

            // Clear session and cart
            unset($_SESSION['pending_checkout']);
            unset($_SESSION['paystack_reference']);
            clear_cart();

            // Send order confirmation (always send order receipt)
            send_order_confirmation_email($order_id);

            // If payment was verified, mark order paid and send payment confirmation
            if ($payment_verified) {
                $u = $conn->prepare('UPDATE orders SET payment_status = "paid", status = "processing", updated_at = NOW() WHERE id = ?');
                $u->bind_param('i', $order_id);
                $u->execute();
                send_payment_confirmation_email($order_id);
            }

            set_flash('success', "Payment successful! Order #$order_number has been placed.");
            redirect(APP_URL . '/index.php');
        }
    }

    unset($_SESSION['pending_checkout']);
    unset($_SESSION['paystack_reference']);
    set_flash('error', 'Payment was not completed. Please try again.');
    redirect(APP_URL . '/checkout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $payment_method = sanitize($_POST['payment'] ?? 'pay_on_delivery');
    $paystack_reference = sanitize($_POST['paystack_reference'] ?? $paystack_reference);

    if (empty($name)) $errors[] = 'Full name is required.';
    if (!is_valid_email($email)) $errors[] = 'Valid email is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    if (empty($address)) $errors[] = 'Shipping address is required.';
    if (empty($city)) $errors[] = 'City is required.';
    if (empty($state)) $errors[] = 'State is required.';

    if (empty($errors)) {
        if ($payment_method === 'paystack') {
            // Create pending order, order_items, and order_vendor_payouts, then initialize Paystack transaction with dynamic split.
            $conn->begin_transaction();
            try {
                $order_number = 'DS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                $user_id = $user ? $user['id'] : null;
                $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,
                        shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paystack')");
                $stmt->bind_param('sisssssssddd', $order_number, $user_id, $name, $email, $phone, $address, $city, $state, $notes, $subtotal, $shipping, $total);
                if (!$stmt->execute()) throw new Exception('Unable to create order');
                $order_id = $conn->insert_id;

                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)");

                // Build per-vendor totals
                $vendorTotals = [];
                foreach ($cart_items as $item) {
                    $line_total = $item['price'] * $item['quantity'];
                    $item_stmt->bind_param('iissidd', $order_id, $item['id'], $item['name'], $item['sku'], $item['quantity'], $item['price'], $line_total);
                    $item_stmt->execute();

                    // decrement stock
                    $stock_stmt = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
                    $stock_stmt->bind_param('iii', $item['quantity'], $item['id'], $item['quantity']);
                    $stock_stmt->execute();

                    // fetch vendor id
                    $prodStmt = $conn->prepare('SELECT vendor_id FROM products WHERE id = ? LIMIT 1');
                    $prodStmt->bind_param('i', $item['id']);
                    $prodStmt->execute();
                    $prod = $prodStmt->get_result()->fetch_assoc();
                    $vendor_id = $prod['vendor_id'] ?? 0;
                    if (!isset($vendorTotals[$vendor_id])) $vendorTotals[$vendor_id] = 0.00;
                    $vendorTotals[$vendor_id] += $line_total;
                }

                // Commission percent from settings
                $commission_percent = (float) get_setting('marketplace_commission_percent', 10);

                // Create order_vendor_payouts rows
                $payoutStmt = $conn->prepare("INSERT INTO order_vendor_payouts (order_id, vendor_id, gross_amount, vendor_amount, marketplace_commission, subaccount_code, status) VALUES (?,?,?,?,?,?,'pending')");
                $vendorSubaccounts = [];
                foreach ($vendorTotals as $vid => $gross) {
                    $market_comm = round(($gross * $commission_percent) / 100, 2);
                    $vendor_amt = round($gross - $market_comm, 2);
                    // lookup vendor subaccount
                    $vstmt = $conn->prepare('SELECT paystack_subaccount_code, kyc_status FROM vendors WHERE id = ? LIMIT 1');
                    $vstmt->bind_param('i', $vid);
                    $vstmt->execute();
                    $vrow = $vstmt->get_result()->fetch_assoc();
                    $subcode = $vrow['paystack_subaccount_code'] ?? null;
                    $kyc_status = $vrow['kyc_status'] ?? 'not_started';

                    $payoutStmt->bind_param('iiddds', $order_id, $vid, $gross, $vendor_amt, $market_comm, $subcode);
                    $payoutStmt->execute();

                    // collect for split creation; require verified kyc and existing subaccount
                    if (empty($subcode) || $kyc_status !== 'verified') {
                        // cannot proceed with automatic split-payments when vendor not ready
                        throw new Exception('One or more vendors are not eligible for automatic payouts. Please contact support.');
                    }
                    $vendorSubaccounts[] = ['vendor_id' => $vid, 'subaccount' => $subcode, 'gross' => $gross, 'vendor_amount' => $vendor_amt];
                }

                // Create Paystack split (percentage-based)
                $splitSubaccounts = [];
                foreach ($vendorSubaccounts as $vs) {
                    $sharePercent = ($vs['vendor_amount'] / $total) * 100.0;
                    $splitSubaccounts[] = ['subaccount' => $vs['subaccount'], 'share' => round($sharePercent, 2)];
                }

                $splitParams = ['name' => 'Order ' . $order_number, 'type' => 'percentage', 'subaccounts' => $splitSubaccounts];
                $splitResp = paystack_create_split($splitParams);
                if (!empty($splitResp['error'])) throw new Exception('Paystack split creation error: ' . $splitResp['error']);
                $splitBody = $splitResp['body'] ?? null;
                if (empty($splitBody['status']) || $splitBody['status'] !== true || empty($splitBody['data']['split_code'])) {
                    throw new Exception('Unexpected Paystack split response: ' . json_encode($splitBody));
                }
                $split_code = $splitBody['data']['split_code'];

                // Initialize transaction
                $initParams = [
                    'email' => $email,
                    'amount' => (int) round($total * 100),
                    'reference' => $paystack_reference,
                    'split_code' => $split_code,
                    'metadata' => ['order_id' => $order_id, 'user_id' => $user_id, 'marketplace_commission' => $commission_percent]
                ];
                $initResp = paystack_initialize_transaction($initParams);
                if (!empty($initResp['error'])) throw new Exception('Paystack initialize error: ' . $initResp['error']);
                $initBody = $initResp['body'] ?? null;
                if (empty($initBody['status']) || $initBody['status'] !== true || empty($initBody['data']['authorization_url'])) {
                    throw new Exception('Unexpected Paystack initialize response: ' . json_encode($initBody));
                }

                // create payments record (pending)
                $payIns = $conn->prepare('INSERT INTO payments (order_id, user_id, gateway, paystack_reference, gross_amount, marketplace_commission, status, created_at) VALUES (?,?,?,?,?,?,"pending",NOW())');
                $gross_amount = $total;
                $mc = ($gross_amount * $commission_percent) / 100.0;
                $payIns->bind_param('iissdd', $order_id, $user_id, $gateway='paystack', $paystack_reference, $gross_amount, $mc);
                $payIns->execute();

                $conn->commit();

                // Redirect customer to Paystack authorization
                header('Location: ' . $initBody['data']['authorization_url']);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Payment initialization failed: ' . $e->getMessage();
            }
        } else {
            $order_number = 'DS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $user_id = $user ? $user['id'] : null;

            $stmt = $conn->prepare('
                INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "pay_on_delivery")
            ');
            $stmt->bind_param(
                'sisssssssddd',
                $order_number, $user_id, $name, $email, $phone,
                $address, $city, $state, $notes, $subtotal, $shipping, $total
            );

            if ($stmt->execute()) {
                $order_id = $conn->insert_id;

                $item_stmt = $conn->prepare('
                    INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');

                foreach ($cart_items as $item) {
                    $line_total = $item['price'] * $item['quantity'];
                    $item_stmt->bind_param(
                        'iissidd',
                        $order_id, $item['id'], $item['name'], $item['sku'],
                        $item['quantity'], $item['price'], $line_total
                    );
                    $item_stmt->execute();

                    $stock_stmt = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
                    $stock_stmt->bind_param('iii', $item['quantity'], $item['id'], $item['quantity']);
                    $stock_stmt->execute();
                }

                clear_cart();

                // Send order confirmation email
                send_order_confirmation_email($order_id);

                set_flash('success', "Order #$order_number placed successfully! We'll contact you shortly.");
                redirect(APP_URL . '/index.php');
            } else {
                $errors[] = 'Failed to place order. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
.checkout-page { padding: 32px 20px 60px; }
.checkout-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 22px;
}
.step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}
.step.active {
    border-color: #4f46e5;
    background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(17,24,39,0.04));
}
.step-number {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #111827;
    color: #fff;
    font-weight: 700;
}
.step strong { display: block; font-size: 0.95rem; }
.step small { color: #6b7280; }
.checkout-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 26px;
    padding: 24px 28px;
    border-radius: 24px;
    background: linear-gradient(135deg, #111827 0%, #4f46e5 100%);
    color: #fff;
}
.checkout-hero h1 { margin: 4px 0 8px; font-size: 2rem; }
.eyebrow {
    display: inline-block;
    margin: 0;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    background: rgba(255,255,255,0.16);
}
.hero-copy { margin: 0; color: rgba(255,255,255,0.88); max-width: 650px; }
.checkout-hero-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(255,255,255,0.16);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 999px;
    white-space: nowrap;
}
.checkout-layout { display: grid; grid-template-columns: 1.45fr 0.85fr; gap: 24px; align-items: start; }
.checkout-card, .summary-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}
.checkout-card + .checkout-card { margin-top: 16px; }
.section-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
.section-title-row h2, .section-title-row h3 { margin: 0; font-size: 1.02rem; }
.section-title-row span { color: #6b7280; font-size: 0.9rem; }
.checkout-form .form-group { margin-bottom: 14px; }
.checkout-form label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
.checkout-form input,
.checkout-form textarea,
.checkout-form select {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 14px;
    background: #fff;
}
.checkout-form input:focus,
.checkout-form textarea:focus,
.checkout-form select:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.payment-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #f9fafb;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}
.payment-option:hover { border-color: #c7d2fe; background: #f5f7ff; }
.payment-option input { width: auto; margin-right: 10px; }
.payment-option strong { display: block; margin-bottom: 4px; }
.payment-option p { margin: 0; color: #6b7280; font-size: 13px; }
.payment-option-alt { opacity: 0.7; }
.checkout-submit { width: 100%; display: flex; justify-content: center; gap: 8px; }
.checkout-alert { margin-bottom: 18px; }
.checkout-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
.checkout-item:last-child { border-bottom: 0; }
.checkout-item img { width: 56px; height: 56px; object-fit: cover; border-radius: 12px; }
.checkout-item-info { flex: 1; }
.checkout-item-info h4 { margin: 0 0 2px; font-size: 0.95rem; }
.checkout-item-info span { color: #6b7280; font-size: 0.9rem; }
.checkout-item-price { font-weight: 700; color: #111827; }
.summary-row { display: flex; justify-content: space-between; padding: 10px 0; color: #374151; }
.summary-row.total { margin-top: 8px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 1.05rem; font-weight: 700; }
.summary-row .free { color: #16a34a; font-weight: 700; }
@media (max-width: 900px) {
    .checkout-layout { grid-template-columns: 1fr; }
    .checkout-hero { flex-direction: column; align-items: flex-start; }
    .checkout-steps { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .form-row { grid-template-columns: 1fr; }
    .checkout-page { padding: 20px 14px 40px; }
    .checkout-hero { padding: 20px; }
    .checkout-card, .summary-card { padding: 18px; border-radius: 18px; }
}
</style>

<main class="container checkout-page">
    <div class="checkout-hero">
        <div>
            <p class="eyebrow">Secure checkout</p>
            <h1>Complete your order</h1>
            <p class="hero-copy">Fast delivery, trusted payments, and a smooth experience from cart to doorstep.</p>
        </div>
        <?php /* Promotional free-delivery badge removed per rebrand */ ?>
    </div>

    <div class="checkout-steps" aria-label="Checkout progress">
        <div class="step active">
            <span class="step-number">1</span>
            <div>
                <strong>Shipping</strong>
                <small>Address & contact</small>
            </div>
        </div>
        <div class="step active">
            <span class="step-number">2</span>
            <div>
                <strong>Payment</strong>
                <small>Choose your method</small>
            </div>
        </div>
        <div class="step">
            <span class="step-number">3</span>
            <div>
                <strong>Confirmation</strong>
                <small>Order received</small>
            </div>
        </div>
    </div>

    <?php if ($errors): ?>
    <div class="flash-message flash-error checkout-alert">
        <i class="fas fa-exclamation-circle"></i>
        <ul>
            <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <form method="post" class="checkout-form" data-validate>
            <section class="checkout-card">
                <div class="section-title-row">
                    <h2><i class="fas fa-truck"></i> Shipping details</h2>
                    <span>Step 1</span>
                </div>
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? ($user ? $user['name'] : '')) ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? ($user ? $user['email'] : '')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required placeholder="08012345678" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Delivery Address *</label>
                    <textarea id="address" name="address" required rows="2"><?= e($_POST['address'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required value="<?= e($_POST['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State *</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                            <?php
                            $states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
                            foreach ($states as $s):
                            ?>
                            <option value="<?= $s ?>" <?= ($_POST['state'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Order Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Special delivery instructions..."><?= e($_POST['notes'] ?? '') ?></textarea>
                </div>
            </section>

            <section class="checkout-card">
                <div class="section-title-row">
                    <h2><i class="fas fa-credit-card"></i> Payment</h2>
                    <span>Step 2</span>
                </div>
                <label class="payment-option">
                    <input type="radio" name="payment" value="paystack" checked>
                    <div>
                        <strong>Pay with Card (Paystack)</strong>
                        <p>Secure card payment with the test key integrated for this store.</p>
                    </div>
                    <i class="fas fa-credit-card"></i>
                </label>
                <label class="payment-option payment-option-alt">
                    <input type="radio" name="payment" value="pay_on_delivery">
                    <div>
                        <strong>Pay on Delivery</strong>
                        <p>Pay when your order arrives at your doorstep.</p>
                    </div>
                    <i class="fas fa-money-bill-wave"></i>
                </label>
                <input type="hidden" name="paystack_reference" id="paystack_reference" value="<?= e($paystack_reference) ?>">
                <button type="submit" class="btn btn-primary btn-lg checkout-submit">
                    <i class="fas fa-lock"></i> Continue to secure payment · <?= format_price($total) ?>
                </button>
            </section>
        </form>

        <aside class="checkout-summary">
            <div class="summary-card">
                <div class="section-title-row">
                    <h3><i class="fas fa-shopping-bag"></i> Order summary</h3>
                    <span><?= count($cart_items) ?> item(s)</span>
                </div>
                <?php foreach ($cart_items as $item): ?>
                <div class="checkout-item">
                    <img src="<?= e(image_url($item['image'] ?? '')) ?>" alt="<?= e($item['name']) ?>">
                    <div class="checkout-item-info">
                        <h4><?= e($item['name']) ?></h4>
                        <span>Qty: <?= (int) $item['quantity'] ?></span>
                    </div>
                    <span class="checkout-item-price"><?= format_price($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
                <div class="summary-row"><span>Shipping</span><span class="<?= $shipping === 0 ? 'free' : '' ?>"><?= $shipping === 0 ? 'FREE' : format_price($shipping) ?></span></div>
                <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
            </div>
        </aside>
    </div>
</main>

<?php if ($initiate_paystack): ?>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
(function () {
    const amount = <?= (int) round($total * 100) ?>;
    const email = <?= json_encode($email ?? '') ?>;
    const reference = <?= json_encode($paystack_reference) ?>;
    const currency = 'NGN';
    const handler = PaystackPop.setup({
        key: <?= json_encode($paystack_public_key) ?>,
        email: email,
        amount: amount,
        currency: currency,
        ref: reference,
        callback: function(response) {
            window.location.href = <?= json_encode(APP_URL . '/checkout.php?paystack=success&reference=') ?> + encodeURIComponent(response.reference);
        },
        onClose: function() {
            window.location.href = <?= json_encode(APP_URL . '/checkout.php') ?>;
        }
    });
    handler.openIframe();
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
