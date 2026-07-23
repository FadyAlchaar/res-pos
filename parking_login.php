<?php
session_start();
require_once 'config/config.php';
require_once 'config/language.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'parking') {
    header('Location: admin/parking_dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT id, full_name, role, password FROM users WHERE username = ? AND role = 'parking'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header('Location: admin/parking_dashboard.php');
            exit;
        } else {
            $error = t('invalid_credentials');
        }
    } catch (Exception $e) {
        $error = t('error_occurred');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('parking_login'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .login-card h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .lang-switch {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .lang-btn {
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
            background: #e9ecef;
            color: #2c3e50;
        }
        .lang-btn.active {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2><?php echo t('parking_login'); ?></h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label><?php echo t('username'); ?></label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label><?php echo t('password'); ?></label>
                <input type="password" name="password" required>
            </div>
            <button type="submit"><?php echo t('login'); ?></button>
        </form>
        <div class="lang-switch">
            <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
        </div>
    </div>
</body>
</html>