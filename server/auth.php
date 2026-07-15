<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

if ($path === '/register' && $method === 'POST') {
    $input = get_input();
    $email = $conn->real_escape_string($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $firstName = $conn->real_escape_string($input['first_name'] ?? '');
    $lastName = $conn->real_escape_string($input['last_name'] ?? '');
    $role = $conn->real_escape_string($input['role'] ?? 'customer');

    if (!$email || !$password) {
        json_response(['error' => 'Email and password required'], 400);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
        $conn->query("INSERT INTO users (email, password_hash) VALUES ('$email', '$passwordHash')");
        $userId = $conn->insert_id;

        $conn->query("INSERT INTO profiles (user_id, first_name, last_name) VALUES ($userId, '$firstName', '$lastName')");

        $roleResult = $conn->query("SELECT id FROM roles WHERE name = '$role'");
        $roleData = $roleResult->fetch_assoc();
        $roleId = $roleData['id'];
        $conn->query("INSERT INTO user_roles (user_id, role_id) VALUES ($userId, $roleId)");

        if ($role === 'customer') {
            $conn->query("INSERT INTO customers (user_id) VALUES ($userId)");
        } elseif ($role === 'vendor') {
            $storeName = $conn->real_escape_string($input['store_name'] ?? 'Store ' . $userId);
            $slug = strtolower(str_replace(' ', '-', $storeName));
            $conn->query("INSERT INTO vendors (user_id, store_name, slug) VALUES ($userId, '$storeName', '$slug')");
        } elseif ($role === 'admin') {
            $conn->query("INSERT INTO admins (user_id) VALUES ($userId)");
        }

        $conn->commit();

        $token = generate_token(['user_id' => $userId]);

        json_response(['message' => 'Registered successfully', 'token' => $token, 'user_id' => $userId]);
    } catch (Exception $e) {
        $conn->rollback();
        json_response(['error' => 'Registration failed', 'details' => $e->getMessage()], 500);
    }
} elseif ($path === '/login' && $method === 'POST') {
    $input = get_input();
    $email = $conn->real_escape_string($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        json_response(['error' => 'Email and password required'], 400);
    }

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(['error' => 'Invalid credentials'], 401);
    }

    $conn->query("UPDATE users SET last_login_at = NOW() WHERE id = " . $user['id']);

    $token = generate_token(['user_id' => $user['id']]);

    $roleResult = $conn->query("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = " . $user['id']);
    $roleData = $roleResult->fetch_assoc();
    $role = $roleData['name'];

    json_response([
        'message' => 'Login successful',
        'token' => $token,
        'user_id' => $user['id'],
        'role' => $role,
        'email' => $user['email']
    ]);
} elseif ($path === '/me' && $method === 'GET') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $profileResult = $conn->query("SELECT * FROM profiles WHERE user_id = " . $user['id']);
    $profile = $profileResult->fetch_assoc();

    $roleResult = $conn->query("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = " . $user['id']);
    $roleData = $roleResult->fetch_assoc();
    $role = $roleData['name'];

    $actorData = null;
    if ($role === 'customer') {
        $actorResult = $conn->query("SELECT * FROM customers WHERE user_id = " . $user['id']);
        $actorData = $actorResult->fetch_assoc();
    } elseif ($role === 'vendor') {
        $actorResult = $conn->query("SELECT * FROM vendors WHERE user_id = " . $user['id']);
        $actorData = $actorResult->fetch_assoc();
    } elseif ($role === 'admin') {
        $actorResult = $conn->query("SELECT * FROM admins WHERE user_id = " . $user['id']);
        $actorData = $actorResult->fetch_assoc();
    }

    json_response([
        'user' => $user,
        'profile' => $profile,
        'role' => $role,
        'actor' => $actorData
    ]);
} else {
    json_response(['error' => 'Not found'], 404);
}
?>
