<?php
// First Time Setup - Create Admin User (FULLY FIXED)
require_once 'config/config.php';

// Check if setup has been run before
$setup_file = 'setup.lock';

if (file_exists($setup_file)) {
    die("Setup has already been completed. Delete 'setup.lock' to run again.");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>First Time Setup - Restaurant POS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .setup-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #219a52;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .credentials {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .credentials code {
            display: block;
            background: #1a2632;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class='setup-container'>
        <h1>🚀 First Time Setup</h1>
        <div class='subtitle'>Create your administrator account</div>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Start transaction
            $db->beginTransaction();
            
            // Check if any admin already exists
            $check_result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $check = $check_result->fetch();
            
            if ($check['count'] > 0) {
                echo "<div class='error'>⚠️ An admin user already exists! Use existing credentials to login.</div>";
            } else {
                // Create admin user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $admin_query = "INSERT INTO users (username, password, full_name, role, is_active) 
                                VALUES (:username, :password, :full_name, 'admin', 1)";
                $admin_stmt = $db->prepare($admin_query);
                $admin_result = $admin_stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':full_name' => $full_name
                ]);
                
                if ($admin_result) {
                    // Create waiter user - separate query without ON DUPLICATE KEY
                    $waiter_password = password_hash('password123', PASSWORD_DEFAULT);
                    
                    // First check if waiter exists
                    $check_waiter = $db->query("SELECT COUNT(*) FROM users WHERE username = 'waiter'")->fetchColumn();
                    
                    if ($check_waiter > 0) {
                        // Update existing waiter
                        $waiter_query = "UPDATE users SET password = :password, full_name = 'Demo Waiter', is_active = 1 WHERE username = 'waiter'";
                        $waiter_stmt = $db->prepare($waiter_query);
                        $waiter_stmt->execute([':password' => $waiter_password]);
                    } else {
                        // Insert new waiter
                        $waiter_query = "INSERT INTO users (username, password, full_name, role, is_active) 
                                        VALUES ('waiter', :password, 'Demo Waiter', 'waiter', 1)";
                        $waiter_stmt = $db->prepare($waiter_query);
                        $waiter_stmt->execute([':password' => $waiter_password]);
                    }
                    
                    // Commit transaction
                    $db->commit();
                    
                    // Create lock file
                    file_put_contents($setup_file, date('Y-m-d H:i:s') . " - Setup completed\n");
                    
                    echo "<div class='success'>✅ Admin user created successfully!</div>";
                    echo "<div class='credentials'>
                            <strong>🔐 Login Credentials:</strong>
                            <code>
                            Username: $username<br>
                            Password: $password
                            </code>
                            <hr style='margin: 15px 0; border-color: #3d5a73;'>
                            <strong>👨‍🍳 Demo Waiter:</strong>
                            <code>
                            Username: waiter<br>
                            Password: password123
                            </code>
                          </div>";
                    echo "<p style='margin-top: 20px; text-align: center;'>
                            <a href='login.php' style='background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>Go to Login →</a>
                          </p>";
                    exit;
                } else {
                    $db->rollBack();
                    echo "<div class='error'>❌ Failed to create admin user.</div>";
                }
            }
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ " . implode("<br>❌ ", $errors) . "</div>";
    }
}
?>

        <form method="POST">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required placeholder="admin" value="admin">
            </div>
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="System Administrator" value="System Administrator">
            </div>
            
            <div class="form-group">
                <label>Password * (min 6 characters)</label>
                <input type="password" name="password" required minlength="6" value="admin123">
            </div>
            
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required minlength="6" value="admin123">
            </div>
            
            <div class="info-box">
                ℹ️ This will create an administrator account. Keep these credentials safe!
            </div>
            
            <button type="submit">Create Admin Account</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 0.85rem;">
            This setup will only run once. After completion, you can create additional users from the admin panel.
        </p>
    </div>
</body>
</html>