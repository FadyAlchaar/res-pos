<?php
session_start();
require_once '../config/config.php';
require_once '../config/language.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parking') {
    header('Location: ../parking_login.php');
    exit;
}

// Handle language switch (from URL)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['language'] = $_GET['lang'];
    $lang = $_GET['lang'];
} else {
    $lang = $_SESSION['language'] ?? 'en';
}
$GLOBALS['lang'] = $lang;

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo t('parking_dashboard'); ?></title>
    <meta http-equiv="refresh" content="60">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .lang-switch {
            display: flex;
            gap: 5px;
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
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
        }
        .request-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .request-time {
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
        .request-details {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .btn-complete {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo t('parking_dashboard'); ?></h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="lang-switch">
                    <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                    <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
                </div>
                <a href="../logout.php" class="logout-btn"><?php echo t('logout'); ?></a>
            </div>
        </div>
        <div id="requests-container">
            <div class="empty-state"><?php echo t('waiting_for_requests'); ?></div>
        </div>
    </div>

    <audio id="notification-sound" preload="auto">
        <source src="data:audio/wav;base64,U3RlYWx0aCBpcwpvbmUgb2YgdGhlIG1vc3QgcG9wdWxhciBzb3VuZHMgZm9yIG5vdGlmaWNhdGlvbnMuIFRoaXMgaXMgYSBmYWxsYmFjayBpbiBjYXNlIHRoZSBicm93c2VyIGRvZXMgbm90IHN1cHBvcnQgdGhlIFdlYiBBdWRpbyBBUEku" type="audio/wav">
    </audio>

    <script>
        const requestsContainer = document.getElementById('requests-container');
        const notificationSound = document.getElementById('notification-sound');
        let lastId = 0;
        let pollingInterval = null;

        function playNotificationSound() {
            notificationSound.play().catch(e => console.log('Audio play failed:', e));
        }

        function addRequest(request) {
            const tableInfo = request.table_number && request.table_number != 0 ? `<?php echo t('table'); ?> ${request.table_number}<br>` : '';
            const card = document.createElement('div');
            card.className = 'request-card';
            card.setAttribute('data-id', request.id);
            card.innerHTML = `
                <div class="request-time">${new Date(request.created_at).toLocaleString()}</div>
                <div class="request-details">
                    🅿️ ${tableInfo}
                    🚗 <?php echo t('parking_lots'); ?>: ${request.lot_numbers}
                </div>
                <button class="btn-complete" onclick="markComplete(${request.id})">✅ <?php echo t('mark_completed'); ?></button>
            `;
            requestsContainer.insertBefore(card, requestsContainer.firstChild);
            playNotificationSound();
        }

        function markComplete(requestId) {
            fetch('api/mark_parking_complete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: requestId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector(`.request-card[data-id="${requestId}"]`);
                    if (card) card.remove();
                    if (requestsContainer.children.length === 0 || (requestsContainer.children.length === 1 && requestsContainer.children[0].classList.contains('empty-state'))) {
                        requestsContainer.innerHTML = '<div class="empty-state"><?php echo t('waiting_for_requests'); ?></div>';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function fetchRequests() {
            fetch(`api/get_parking_requests.php?last_id=${lastId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.requests && data.requests.length > 0) {
                        // Remove empty state if present
                        if (requestsContainer.children.length === 1 && requestsContainer.children[0].classList.contains('empty-state')) {
                            requestsContainer.innerHTML = '';
                        }
                        data.requests.forEach(req => {
                            addRequest(req);
                            if (req.id > lastId) lastId = req.id;
                        });
                    }
                })
                .catch(error => console.error('Polling error:', error));
        }

        // Start polling every 3 seconds
        pollingInterval = setInterval(fetchRequests, 3000);
        fetchRequests();
    </script>
</body>
</html>