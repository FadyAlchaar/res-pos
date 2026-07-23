<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/language.php';

$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo t('parking_request'); ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px; }
        .container { background: white; border-radius: 30px; padding: 30px; max-width: 400px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        input { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 20px; font-size: 1rem; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 30px; font-size: 1rem; font-weight: bold; cursor: pointer; }
        .message { margin-top: 15px; padding: 10px; border-radius: 15px; display: none; }
        .message.success { background: #d4edda; color: #155724; display: block; }
        .message.error { background: #f8d7da; color: #721c24; display: block; }
        .lang-switch { margin-top: 20px; display: flex; justify-content: center; gap: 10px; }
        .lang-btn { padding: 5px 12px; border-radius: 20px; text-decoration: none; background: #e9ecef; color: #2c3e50; }
        .lang-btn.active { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 <?php echo t('parking_request'); ?></h1>
        <form id="parkingForm">
            <div class="form-group">
                <label><?php echo t('parking_lot_numbers'); ?></label>
                <input type="text" id="lot_numbers" inputmode="numeric" pattern="[0-9, ]+" placeholder="e.g., 14, 15, 16" required>
            </div>
            <button type="submit"><?php echo t('send'); ?></button>
        </form>
        <div id="message" class="message"></div>
        <div class="lang-switch">
            <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
        </div>
    </div>
    <script>
        document.getElementById('parkingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const lotNumbers = document.getElementById('lot_numbers').value.trim();
            if (!lotNumbers) return;
            const msgDiv = document.getElementById('message');
            msgDiv.className = 'message';
            msgDiv.style.display = 'none';
            fetch('../api/customer_parking_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    lot_numbers: lotNumbers,
                    lang: '<?php echo $lang; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                msgDiv.textContent = data.message;
                if (data.success) {
                    msgDiv.className = 'message success';
                    document.getElementById('lot_numbers').value = '';
                } else {
                    msgDiv.className = 'message error';
                }
                msgDiv.style.display = 'block';
                setTimeout(() => msgDiv.style.display = 'none', 4000);
            })
            .catch(error => {
                msgDiv.textContent = '<?php echo t('error'); ?>';
                msgDiv.className = 'message error';
                msgDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>