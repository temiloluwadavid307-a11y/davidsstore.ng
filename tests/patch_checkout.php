<?php
$file = __DIR__ . '/../checkout.php';
$src = file_get_contents($file);
if ($src === false) { echo "Failed to read file\n"; exit(1); }
$orig = "\$stmt = \$conn->prepare('\\n                    INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\\n                        shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\\n                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \"pending\", \"paystack\")\\n                ');\n                $stmt->bind_param('sisssssssddd', $order_number, $user_id, $name, $email, $phone, $address, $city, $state, $notes, $subtotal, $shipping, $total);";
$repl = "\$stmt = \$conn->prepare(\"INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\n                        shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\n                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paystack')\");\n                $stmt->bind_param('sisssssssddd', $order_number, $user_id, $name, $email, $phone, $address, $city, $state, $notes, $subtotal, $shipping, $total);";
if (strpos($src, $orig) !== false) {
    $src = str_replace($orig, $repl, $src);
    file_put_contents($file, $src);
    echo "Replaced paystack INSERT block.\n";
} else {
    echo "Pattern not found for paystack block, attempting relaxed replace.\n";
    // Relaxed regex replacement for single-quoted prepare with \n leading
    $pattern = "/\\$stmt = \\$conn->prepare\('\\n\s*INSERT INTO orders[\\s\\S]*?\\);\\n\s*\\$stmt->bind_param\([^;]*;\\n/";
    $replacement = "\$stmt = \\$conn->prepare(\"INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\n                        shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\n                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paystack')\");\n                $stmt->bind_param('sisssssssddd', $order_number, $user_id, $name, $email, $phone, $address, $city, $state, $notes, $subtotal, $shipping, $total);\n";
    $new = preg_replace($pattern, $replacement, $src);
    if ($new === null) { echo "Regex error\n"; exit(1); }
    if ($new !== $src) {
        file_put_contents($file, $new);
        echo "Relaxed replace applied.\n";
    } else {
        echo "No replacements applied.\n";
    }
}

// Now fix pay_on_delivery block similarly
$orig2 = "\$stmt = \$conn->prepare('\\n                INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\\n                    shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \"pending\", \"pay_on_delivery\")\\n            ');\n            $stmt->bind_param(\n                'sisssssssddd',\n                $order_number, $user_id, $name, $email, $phone,\n                $address, $city, $state, $notes, $subtotal, $shipping, $total\n            );";
$repl2 = "\$stmt = \$conn->prepare(\"INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\n                    shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pay_on_delivery')\");\n            $stmt->bind_param(\n                'sisssssssddd',\n                $order_number, $user_id, $name, $email, $phone,\n                $address, $city, $state, $notes, $subtotal, $shipping, $total\n            );";
if (strpos($src, $orig2) !== false) {
    $src = str_replace($orig2, $repl2, $src);
    file_put_contents($file, $src);
    echo "Replaced pay_on_delivery INSERT block.\n";
} else {
    echo "pay_on_delivery pattern not found; attempting relaxed replace.\n";
    $pattern2 = "/\\$stmt = \\$conn->prepare\('\\n\s*INSERT INTO orders[\\s\\S]*?pay_on_delivery\\')\\;\\n\\s*\\$stmt->bind_param\([^;]*;\\n/";
    $replacement2 = "\$stmt = \\$conn->prepare(\"INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone,\n                    shipping_address, shipping_city, shipping_state, notes, subtotal, shipping_fee, total, status, payment_method)\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pay_on_delivery')\");\n            $stmt->bind_param(\n                'sisssssssddd',\n                $order_number, $user_id, $name, $email, $phone,\n                $address, $city, $state, $notes, $subtotal, $shipping, $total\n            );\n";
    $new2 = preg_replace($pattern2, $replacement2, $src);
    if ($new2 !== null && $new2 !== $src) {
        file_put_contents($file, $new2);
        echo "Relaxed replace for pay_on_delivery applied.\n";
    } else {
        echo "No pay_on_delivery replacements applied.\n";
    }
}
