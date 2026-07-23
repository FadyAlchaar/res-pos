<?php
session_start();
require_once '../config/config.php';
require_once '../config/language.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'parking')) {
    header('Location: ../login.php');
    exit;
}

$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo t('parking_dashboard'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            padding-bottom: 80px;
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
            background: white;
            padding: 15px 20px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        h1 {
            font-size: 1.5rem;
            color: #2c3e50;
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
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease;
            border-left: 5px solid #3498db;
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
            border-radius: 30px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: bold;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            background: white;
            border-radius: 20px;
        }
        #enable-sound-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-weight: bold;
            transition: all 0.2s;
        }
        #enable-sound-btn.sound-enabled {
            background: #27ae60;
        }
        @media (max-width: 600px) {
            body { padding: 10px; padding-bottom: 80px; }
            .request-details { font-size: 1rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🚗 <?php echo t('parking_dashboard'); ?></h1>
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

<button id="enable-sound-btn">🔊 <?php echo t('enable_sound'); ?></button>

<audio id="notification-sound" preload="auto" style="display: none;">
    <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    <source src="../assets/sounds/notification.wav" type="audio/wav">
</audio>

<script>
    // ============================================
    // GLOBALS
    // ============================================
    let lastId = 0;
    let soundEnabled = false;
    const container = document.getElementById('requests-container');
    const soundBtn = document.getElementById('enable-sound-btn');
    const audio = document.getElementById('notification-sound');

    // ============================================
    // SOUND PREFERENCES (localStorage)
    // ============================================
    function loadSoundPreference() {
        const saved = localStorage.getItem('parking_sound_enabled');
        if (saved === 'true') {
            soundEnabled = true;
            soundBtn.textContent = '🔊 <?php echo t('sound_enabled'); ?>';
            soundBtn.classList.add('sound-enabled');
        }
    }

    function saveSoundPreference() {
        localStorage.setItem('parking_sound_enabled', soundEnabled);
    }

    function playNotificationSound() {
        if (soundEnabled && audio) {
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    }

    soundBtn.addEventListener('click', () => {
        soundEnabled = true;
        saveSoundPreference();
        soundBtn.textContent = '🔊 <?php echo t('sound_enabled'); ?>';
        soundBtn.classList.add('sound-enabled');
        // Play a test sound to unlock audio context (browser policy)
        audio.play().catch(e => console.log('Test sound failed:', e));
    });

    // ============================================
    // UI FUNCTIONS
    // ============================================
    function addRequest(request) {
        // Remove empty state if present
        if (container.children.length === 1 && container.children[0].classList.contains('empty-state')) {
            container.innerHTML = '';
        }

        const card = document.createElement('div');
        card.className = 'request-card';
        card.setAttribute('data-id', request.id);
        const date = new Date(request.created_at);
        const dateStr = date.toLocaleDateString();
        const timeStr = date.toLocaleTimeString();
        const tableDisplay = request.table_number == 0 ? '—' : request.table_number;

        card.innerHTML = `
            <div class="request-time">${dateStr} ${timeStr}</div>
            <div class="request-details">
                🅿️ <?php echo t('table'); ?> ${tableDisplay}<br>
                🚗 <?php echo t('parking_lots'); ?>: ${request.lot_numbers}
            </div>
            <button class="btn-complete" onclick="markComplete(${request.id})">✅ <?php echo t('mark_completed'); ?></button>
        `;
        container.insertBefore(card, container.firstChild);
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
                if (container.children.length === 0) {
                    container.innerHTML = '<div class="empty-state"><?php echo t('waiting_for_requests'); ?></div>';
                }
            }
        })
        .catch(error => console.error('Error marking complete:', error));
    }

    // ============================================
    // POLLING (every 3 seconds)
    // ============================================
    function fetchRequests() {
        fetch(`api/get_parking_requests.php?last_id=${lastId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.requests && data.requests.length > 0) {
                    data.requests.forEach(req => {
                        addRequest(req);
                        if (req.id > lastId) lastId = req.id;
                    });
                }
            })
            .catch(error => console.error('Polling error:', error));
    }

    // ============================================
    // INITIALISE
    // ============================================
    loadSoundPreference();
    fetchRequests();
    setInterval(fetchRequests, 3000);
</script>
</body>
</html>