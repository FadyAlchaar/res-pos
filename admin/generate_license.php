<?php
require_once '../config/hardware_id.php';

// This is a private tool - only you should have access
// Password protect this file!

$password = 'admin'; // Change this!

if (!isset($_POST['password']) || $_POST['password'] !== $password) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>License Generator</title>
        <style>
            body { font-family: monospace; padding: 40px; text-align: center; }
            input { padding: 10px; margin: 10px; width: 300px; }
            button { padding: 10px 30px; background: #27ae60; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>🔐 License Generator</h2>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Unlock</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Password correct - show generator
$hardware_id = $_POST['hardware_id'] ?? '';
$generated_license = '';

if ($hardware_id) {
    $secret = 'MyRestaurantPOS_2026_SecretKey_!@#$%^&*()_+';
    $license_data = $hardware_id . '|' . $secret;
    $generated_license = hash('sha256', $license_data);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>License Generator</title>
    <style>
        body {
            font-family: monospace;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
        }
        button {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .license {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔑 License Key Generator</h1>
        <p>Paste the Hardware ID from the customer's system:</p>
        
        <form method="POST">
            <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
            <textarea name="hardware_id" rows="3" placeholder="Paste Hardware ID here..."></textarea>
            <button type="submit">Generate License Key</button>
        </form>
        
        <?php if ($generated_license): ?>
            <div class="license">
                <strong>License Key:</strong><br>
                <?php echo $generated_license; ?>
            </div>
            <p style="margin-top: 15px; font-size: 12px; color: #666;">
                Send this license key to the customer. They can paste it in the activation form.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>