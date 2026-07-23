<?php
require_once '../config/config.php';
require_once '../config/hardware_id.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/login.php');
}

$message = '';
$message_type = '';
$hardware = HardwareID::generateHardwareId();

// Handle activation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['activate'])) {
        $license_key = trim($_POST['license_key']);
        
        // Verify license key matches this hardware
        $expected_key = HardwareID::generateLicenseKey();
        
        if ($license_key === $expected_key) {
            // Store the hardware ID (not the license key)
            $stored_id = $hardware['hardware_id'];
            
            $query = "INSERT INTO restaurant_settings (setting_key, setting_value) 
                      VALUES ('hardware_id', :hardware_id)
                      ON DUPLICATE KEY UPDATE setting_value = :hardware_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':hardware_id' => $stored_id]);
            
            $message = "✅ License activated successfully for this hardware!";
            $message_type = "success";
        } else {
            $message = "❌ Invalid license key for this hardware!";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['deactivate'])) {
        $query = "DELETE FROM restaurant_settings WHERE setting_key = 'hardware_id'";
        $db->exec($query);
        $message = "License deactivated";
        $message_type = "warning";
    }
}

// Check current status
$is_licensed = false;
$query = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'hardware_id'";
$stmt = $db->query($query);
$result = $stmt->fetch();

if ($result) {
    $is_licensed = HardwareID::verify($result['setting_value']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>License Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 550px;
            width: 100%;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 25px;
            font-size: 0.85rem;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .hardware-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 0.75rem;
            word-break: break-all;
        }
        .hardware-info p {
            margin: 5px 0;
        }
        .license-key {
            font-family: monospace;
            background: #2c3e50;
            color: #ecf0f1;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            letter-spacing: 1px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-family: monospace;
            font-size: 0.9rem;
            margin: 10px 0;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover {
            background: #219a52;
        }
        button.danger {
            background: #e74c3c;
        }
        button.danger:hover {
            background: #c0392b;
        }
        button.secondary {
            background: #3498db;
        }
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #f39c12;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔐 License Management</h1>
            <div class="subtitle">Activate your software license</div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <strong>Status:</strong> 
                <span class="status-badge <?php echo $is_licensed ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $is_licensed ? '✅ LICENSED' : '❌ NOT LICENSED'; ?>
                </span>
            </div>
            
            <div class="hardware-info">
                <p><strong>🔧 Hardware ID:</strong></p>
                <p style="font-size: 0.7rem;"><?php echo $hardware['hardware_id']; ?></p>
                <hr>
                <p><strong>💻 CPU:</strong> <?php echo $hardware['components']['cpu']; ?></p>
                <p><strong>🖥️ Motherboard:</strong> <?php echo $hardware['components']['motherboard']; ?></p>
            </div>
            
            <?php if ($is_licensed): ?>
                <form method="POST">
                    <button type="submit" name="deactivate" class="danger">🔓 Deactivate License</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <label>Enter License Key:</label>
                    <input type="text" name="license_key" placeholder="Paste your license key here" required>
                    <button type="submit" name="activate">🔑 Activate License</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📋 License Information</h2>
            <div class="subtitle">For administrator use only</div>
            
            <p style="margin-bottom: 10px;"><strong>This license is tied to your hardware:</strong></p>
            <ul style="margin-left: 20px; color: #555; font-size: 0.85rem;">
                <li>CPU Serial Number</li>
                <li>Motherboard Serial Number</li>
            </ul>
            
            <p style="margin: 15px 0 10px;"><strong>What this means:</strong></p>
            <ul style="margin-left: 20px; color: #555; font-size: 0.85rem;">
                <li>✅ You can reinstall Windows as many times as you want</li>
                <li>✅ License works on same hardware</li>
                <li>❌ License will NOT work on a different computer</li>
                <li>❌ Changing motherboard or CPU will require new license</li>
            </ul>
            
            <?php if (!$is_licensed): ?>
                <hr>
                <p><strong>To get a license key, send this Hardware ID to support:</strong></p>
                <div class="license-key"><?php echo $hardware['hardware_id']; ?></div>
                <button class="secondary" onclick="copyHardwareId()">📋 Copy Hardware ID</button>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function copyHardwareId() {
        const hardwareId = "<?php echo $hardware['hardware_id']; ?>";
        navigator.clipboard.writeText(hardwareId).then(() => {
            alert('Hardware ID copied to clipboard!');
        });
    }
    </script>
</body>
</html>