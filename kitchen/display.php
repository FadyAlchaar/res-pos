<?php
require_once '../config/config.php';
require_once '../config/language.php';

// Kitchen display doesn't require login - it's a public display
// But we can restrict by IP if needed

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all active kitchens
    $query = "SELECT id, name, printer_ip FROM kitchens WHERE is_active = 1 ORDER BY name";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kitchen Display System | Restaurant POS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Header */
        .kds-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 16px 24px;
            border-bottom: 2px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f97316, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .time {
            font-size: 1.8rem;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            color: #facc15;
        }
        
        .stats {
            display: flex;
            gap: 24px;
        }
        
        .stat-card {
            background: #1e293b;
            padding: 8px 20px;
            border-radius: 40px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f97316;
        }
        
        .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        /* Kitchen Tabs */
        .kitchen-tabs {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: #1e293b;
            padding: 8px 24px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            z-index: 99;
            border-bottom: 1px solid #334155;
        }
        
        .kitchen-tab {
            padding: 10px 24px;
            background: #334155;
            border: none;
            border-radius: 40px;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .kitchen-tab.active {
            background: #f97316;
            color: white;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        
        .kitchen-tab:hover:not(.active) {
            background: #475569;
        }
        
        /* Main Content */
        .orders-container {
            margin-top: 140px;
            padding: 20px 24px;
            height: calc(100vh - 140px);
            overflow-y: auto;
        }
        
        /* Orders Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }
        
        /* Order Card */
        .order-card {
            background: #1e293b;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            animation: slideIn 0.3s ease;
            border: 1px solid #334155;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .order-card.urgent {
            border-left: 4px solid #ef4444;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { border-left-color: #ef4444; }
            50% { border-left-color: #f97316; }
        }
        
        .order-header {
            background: #0f172a;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        
        .order-number {
            font-size: 1.1rem;
            font-weight: bold;
            color: #facc15;
        }
        
        .table-number {
            background: #334155;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .order-time {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .order-items {
            padding: 16px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #334155;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .item-notes {
            font-size: 0.7rem;
            color: #facc15;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .item-quantity {
            background: #f97316;
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 0.8rem;
            min-width: 50px;
            text-align: center;
        }
        
        .order-footer {
            padding: 12px 16px;
            background: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #334155;
        }
        
        .timer {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        
        .btn-complete {
            background: #22c55e;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-complete:hover {
            background: #16a34a;
            transform: scale(1.02);
        }
        
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #94a3b8;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #64748b;
        }
        
        /* Alert Banner */
        .alert-banner {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: none;
            animation: slideUp 0.3s;
            z-index: 200;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Sound Wave Animation for New Orders */
        .sound-wave {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 3px;
            align-items: center;
            background: #f97316;
            padding: 8px 16px;
            border-radius: 40px;
            display: none;
        }
        
        .wave-bar {
            width: 3px;
            height: 15px;
            background: white;
            animation: wave 0.5s ease-in-out infinite;
        }
        
        .wave-bar:nth-child(1) { animation-delay: 0s; height: 8px; }
        .wave-bar:nth-child(2) { animation-delay: 0.1s; height: 15px; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; height: 12px; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; height: 10px; }
        .wave-bar:nth-child(5) { animation-delay: 0.4s; height: 14px; }
        
        @keyframes wave {
            0%, 100% { height: 5px; }
            50% { height: 20px; }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .kds-header {
                padding: 12px 16px;
            }
            
            .logo h1 {
                font-size: 1rem;
            }
            
            .time {
                font-size: 1rem;
            }
            
            .stats {
                gap: 8px;
            }
            
            .stat-card {
                padding: 4px 12px;
            }
            
            .stat-number {
                font-size: 1rem;
            }
            
            .kitchen-tabs {
                top: 70px;
                padding: 6px 16px;
            }
            
            .kitchen-tab {
                padding: 6px 16px;
                font-size: 0.7rem;
            }
            
            .orders-container {
                margin-top: 120px;
                padding: 12px;
            }
            
            .orders-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316;
        }
    </style>
</head>
<body>
    <div class="kds-header">
        <div class="logo">
            <span style="font-size: 2rem;">🍳</span>
            <h1>Kitchen Display System</h1>
        </div>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="pending-count">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="preparing-count">0</div>
                <div class="stat-label">Preparing</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="ready-count">0</div>
                <div class="stat-label">Ready</div>
            </div>
        </div>
        <div class="time" id="current-time">--:--:--</div>
    </div>

    <div class="kitchen-tabs" id="kitchen-tabs">
        <button class="kitchen-tab active" data-kitchen="all">🍽️ All Orders</button>
        <?php foreach ($kitchens as $kitchen): ?>
        <button class="kitchen-tab" data-kitchen="<?php echo $kitchen['id']; ?>">
            🏠 <?php echo htmlspecialchars($kitchen['name']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="orders-container">
        <div class="orders-grid" id="orders-grid">
            <div class="empty-state">
                <div class="empty-state-icon">🍽️</div>
                <h3>No Active Orders</h3>
                <p>Orders will appear here when placed by waiters</p>
            </div>
        </div>
    </div>

    <div class="alert-banner" id="alert-banner">
        🔔 New order received!
    </div>

    <div class="sound-wave" id="sound-wave">
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
    </div>

    <script>
        let currentKitchen = 'all';
        let orders = [];
        let audio = null;
        let lastOrderCount = 0;
        
        // Initialize audio (must be triggered by user interaction)
        function initAudio() {
            audio = new Audio();
            // We'll create an audio context on first order
        }
        
        function playNotification() {
            if (!audio) {
                audio = new Audio();
                audio.src = 'data:audio/wav;base64,U3RlYWQgd2F2ZSBzb3VuZA==';
            }
            // Try to play (may require user interaction first)
            audio.play().catch(e => console.log('Audio play failed:', e));
            
            // Show visual alert
            const banner = document.getElementById('alert-banner');
            const soundWave = document.getElementById('sound-wave');
            banner.style.display = 'block';
            soundWave.style.display = 'flex';
            
            setTimeout(() => {
                banner.style.display = 'none';
            }, 3000);
            
            setTimeout(() => {
                soundWave.style.display = 'none';
            }, 2000);
        }
        
        // Load orders via AJAX
        function loadOrders() {
            fetch(`get_kitchen_orders.php?kitchen=${currentKitchen}&t=${Date.now()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.orders) {
                        // Check if new orders arrived
                        if (lastOrderCount > 0 && data.orders.length > lastOrderCount) {
                            playNotification();
                        }
                        lastOrderCount = data.orders.length;
                        orders = data.orders;
                        renderOrders();
                        updateStats(data.stats);
                    }
                })
                .catch(error => console.error('Error loading orders:', error));
        }
        
        // Mark order item as ready
        function markReady(orderItemId) {
            fetch('mark_order_ready.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_item_id: orderItemId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadOrders();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Update statistics
        function updateStats(stats) {
            document.getElementById('pending-count').textContent = stats?.pending || 0;
            document.getElementById('preparing-count').textContent = stats?.preparing || 0;
            document.getElementById('ready-count').textContent = stats?.ready || 0;
        }
        
        // Render orders
        function renderOrders() {
            const grid = document.getElementById('orders-grid');
            
            if (!orders || orders.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🍽️</div>
                        <h3>No Active Orders</h3>
                        <p>Orders will appear here when placed by waiters</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = orders.map(order => `
                <div class="order-card ${order.is_urgent ? 'urgent' : ''}">
                    <div class="order-header">
                        <div>
                            <span class="order-number">#${order.order_number}</span>
                            <span class="table-number">Table ${order.table_number}</span>
                        </div>
                        <div class="order-time">${order.time_ago}</div>
                    </div>
                    <div class="order-items">
                        ${order.items.map(item => `
                            <div class="order-item">
                                <div class="item-info">
                                    <div class="item-name">
                                        ${item.quantity}x ${item.name}
                                    </div>
                                    ${item.notes ? `
                                        <div class="item-notes">
                                            📝 ${item.notes}
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="item-quantity ${item.status === 'pending' ? 'pending' : ''}">
                                    ${item.status === 'pending' ? '⏳' : '✅'} ${item.status}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="order-footer">
                        <div class="timer">
                            ⏱️ ${order.waiting_time}
                        </div>
                        <button class="btn-complete" onclick="markReady(${order.id})">
                            ✅ Mark All Ready
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Tab switching
        function initTabs() {
            const tabs = document.querySelectorAll('.kitchen-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentKitchen = tab.dataset.kitchen;
                    loadOrders();
                });
            });
        }
        
        // Auto-refresh every 5 seconds
        setInterval(loadOrders, 5000);
        setInterval(updateTime, 1000);
        
        // Initial load
        window.onload = () => {
            initTabs();
            loadOrders();
            updateTime();
            initAudio();
        };
    </script>
</body>
</html>