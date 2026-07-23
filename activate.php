<?php
// Standalone Activation Page - No license check!
require_once 'config/hardware_id.php';

// Check if already activated
$already_activated = false;

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS restaurant_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $query = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'hardware_id'";
    $stmt = $db->query($query);
    $result = $stmt->fetch();
    
    if ($result) {
        $already_activated = HardwareID::verify($result['setting_value']);
    }
} catch (Exception $e) {
    // Database might not exist yet, that's OK
}

// Handle activation
$message = '';
$message_type = '';
$hardware = HardwareID::generateHardwareId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_key'])) {
    $license_key = trim($_POST['license_key']);
    
    // Verify license key matches this hardware
    $expected_key = HardwareID::generateLicenseKey();
    
    if ($license_key === $expected_key) {
        // License is valid! Activate the system
        try {
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $stored_id = $hardware['hardware_id'];
            
            // Check if record exists
            $check = $db->prepare("SELECT COUNT(*) FROM restaurant_settings WHERE setting_key = 'hardware_id'");
            $check->execute();
            $exists = $check->fetchColumn() > 0;
            
            if ($exists) {
                $query = "UPDATE restaurant_settings SET setting_value = :value WHERE setting_key = 'hardware_id'";
                $stmt = $db->prepare($query);
                $stmt->execute([':value' => $stored_id]);
            } else {
                $query = "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('hardware_id', :value)";
                $stmt = $db->prepare($query);
                $stmt->execute([':value' => $stored_id]);
            }
            
            $message = "✅ License activated successfully! You can now access the system.";
            $message_type = "success";
            $already_activated = true;
            
            // Auto-redirect after 3 seconds
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
            
        } catch (Exception $e) {
            $message = "❌ Activation failed: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "❌ Invalid license key for this hardware!";
        $message_type = "error";
    }
}

// Get hardware components
$cpu = $hardware['components']['cpu'];
$motherboard = $hardware['components']['motherboard'];
$hardware_id = $hardware['hardware_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Activation - Restaurant POS</title>
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
        .activation-container { max-width: 550px; width: 100%; }
        .card {
            background: white;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            margin-bottom: 20px;
        }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo h1 { font-size: 1.8rem; color: #2c3e50; margin-bottom: 5px; }
        .logo p { color: #7f8c8d; font-size: 0.85rem; }
        h2 { color: #2c3e50; font-size: 1.3rem; margin-bottom: 15px; }
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .hardware-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        .hardware-id {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 0.75rem;
            word-break: break-all;
            margin: 15px 0;
            text-align: center;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #495057; }
        .info-value { color: #2c3e50; font-family: monospace; font-size: 0.8rem; }
        input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-family: monospace;
            font-size: 0.85rem;
            margin: 15px 0;
            transition: all 0.2s;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39,174,96,0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #27ae60; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }
        .copy-btn {
            background: #3498db;
            margin-top: 10px;
            font-size: 0.85rem;
            padding: 10px;
        }
        .copy-btn:hover { background: #2980b9; transform: none; box-shadow: none; }
        .footer { text-align: center; margin-top: 20px; font-size: 0.7rem; color: rgba(255,255,255,0.7); }
        hr { margin: 20px 0; border: none; border-top: 1px solid #e9ecef; }
    </style>
</head>
<body>
    <div class="activation-container">
        <div class="card">
            <div class="logo">
                <h1>🍽️ Restaurant POS</h1>
                <p>License Activation</p>
            </div>
            
            <?php if ($already_activated): ?>
                <div class="alert alert-success">
                    ✅ System is already activated! 
                    <a href="login.php" style="color: #155724; font-weight: bold;">Click here to login →</a>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="hardware-box">
                <h3 style="margin-bottom: 15px;">🔧 Hardware Information</h3>
                
                <div class="info-row">
                    <span class="info-label">CPU Serial:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cpu); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Motherboard Serial:</span>
                    <span class="info-value"><?php echo htmlspecialchars($motherboard); ?></span>
                </div>
                
                <hr>
                
                <div class="info-row">
                    <span class="info-label">Hardware ID:</span>
                </div>
                <div class="hardware-id" id="hardware-id">
                    <?php echo $hardware_id; ?>
                </div>
                <button class="copy-btn" onclick="copyHardwareId()">📋 Copy Hardware ID</button>
                <p style="font-size: 0.7rem; color: #6c757d; margin-top: 10px; text-align: center;">
                    Send this Hardware ID to your software provider to get a license key
                </p>
            </div>
            
            <?php if (!$already_activated): ?>
                <form method="POST">
                    <label style="font-weight: 600; color: #2c3e50;">Enter License Key:</label>
                    <input type="text" name="license_key" placeholder="Paste your license key here" required autocomplete="off">
                    <button type="submit">🔑 Activate License</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>This license is tied to your hardware (CPU + Motherboard)</p>
            <p>You can reinstall Windows freely - license will still work</p>
        </div>
    </div>
    
    <script>
    function copyHardwareId() {
        const hardwareId = document.getElementById('hardware-id').innerText;
        navigator.clipboard.writeText(hardwareId).then(() => {
            alert('Hardware ID copied to clipboard!');
        });
    }
    </script>
</body>
</html>