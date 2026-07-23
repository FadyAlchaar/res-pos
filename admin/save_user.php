<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users.php');
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$username = trim($_POST['username'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$role = $_POST['role'] ?? 'waiter';
$is_active = isset($_POST['is_active']) ? 1 : 0;
$password = $_POST['password'] ?? '';

// Allowed roles
$allowed_roles = ['admin', 'waiter', 'kitchen', 'parking'];
if (!in_array($role, $allowed_roles)) {
    $role = 'waiter';
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($user_id > 0) {
        // Update existing user
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = :username, full_name = :full_name, 
                      password = :password, role = :role, is_active = :is_active 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':username' => $username,
                ':full_name' => $full_name,
                ':password' => $hashed,
                ':role' => $role,
                ':is_active' => $is_active,
                ':id' => $user_id
            ]);
        } else {
            $query = "UPDATE users SET username = :username, full_name = :full_name, 
                      role = :role, is_active = :is_active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':username' => $username,
                ':full_name' => $full_name,
                ':role' => $role,
                ':is_active' => $is_active,
                ':id' => $user_id
            ]);
        }
    } else {
        // Insert new user
        if (empty($password)) {
            throw new Exception("Password is required for new users");
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password, full_name, role, is_active) 
                  VALUES (:username, :password, :full_name, :role, :is_active)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':password' => $hashed,
            ':full_name' => $full_name,
            ':role' => $role,
            ':is_active' => $is_active
        ]);
    }
    
    header("Location: users.php?success=1");
    exit;
    
} catch (Exception $e) {
    header("Location: users.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>