<?php
// Database Setup Wizard
session_start();

// Check if already configured
$config_file = __DIR__ . '/database_config.json';
$is_configured = file_exists($config_file);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_config.php';
    
    $config = new DBConfig();
    
    $host = $_POST['host'] ?? 'localhost';
    $database = $_POST['database'] ?? 'restaurant_pos';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? 'P@ssw0rd';
    $port = $_POST['port'] ?? 3306;
    
    // Test connection
    $test = $config->testConnection($host, $database, $username, $password, $port);
    
    if ($test['success']) {
        // Save settings
        $config->updateSettings($host, $database, $username, $password, $port);
        $message = $test['message'];
        $message_type = 'success';
        
        // Redirect to main page after 2 seconds
        header("Refresh:2; url=../login.php");
    } else {
        $message = $test['message'];
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Restaurant POS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3rem;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 0.85rem;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        .help-text {
            font-size: 0.7rem;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39,174,96,0.3);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 0.85rem;
            color: #2c3e50;
        }
        
        .info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: #95a5a6;
            margin-top: 0;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            box-shadow: none;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            font-size: 0.75rem;
            color: #95a5a6;
        }
        
        @media (max-width: 480px) {
            .setup-container {
                padding: 25px;
            }
            
            .logo h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <h1>🍽️ Restaurant POS</h1>
            <p>Database Configuration Wizard</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_configured && !$_POST): ?>
            <div class="message info">
                ℹ️ Database is already configured. You can update settings below if needed.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <h2>Database Connection Settings</h2>
            
            <div class="form-group">
                <label>Host *</label>
                <input type="text" name="host" value="localhost" required>
                <div class="help-text">Usually 'localhost' or '127.0.0.1' for local servers</div>
            </div>
            
            <div class="form-group">
                <label>Port *</label>
                <input type="number" name="port" value="3306" required>
                <div class="help-text">Default MySQL port is 3306</div>
            </div>
            
            <div class="form-group">
                <label>Database Name *</label>
                <input type="text" name="database" value="restaurant_pos" required>
                <div class="help-text">The database name (must exist or be created)</div>
            </div>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="root" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="">
                <div class="help-text">Leave empty for default XAMPP/WAMP installations</div>
            </div>
            
            <div class="info-box">
                <p><strong>📌 Need to create the database?</strong></p>
                <p>Run this SQL in phpMyAdmin first:</p>
                <code>CREATE DATABASE IF NOT EXISTS restaurant_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code>
                <p style="margin-top: 10px;"><strong>🔧 Then import the schema:</strong></p>
                <p>Use the <code>restaurant_pos_final.sql</code> file to create all tables.</p>
            </div>
            
            <button type="submit">🔌 Test & Save Connection</button>
        </form>
        
        <div class="footer">
            <p>After successful configuration, the system will use these settings for database connection.</p>
            <p>Settings are stored in <code>config/database_config.json</code></p>
        </div>
    </div>
</body>
</html>