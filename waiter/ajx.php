<?php
require_once '../config/config.php';
require_once '../config/language.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, k.name as kitchen_name, COUNT(mi.id) as item_count
              FROM categories c
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = 1
              WHERE c.is_active = 1
              GROUP BY c.id
              ORDER BY c.sort_order";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    $query = "SELECT * FROM restaurant_tables WHERE is_active = 1 ORDER BY sort_order, table_number";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load menu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo t('waiter_terminal'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f5f7fb;
            height: 100vh;
            overflow: hidden;
        }
        
        /* ========== HEADER ========== */
        .header {
            background: #1e2a3a;
            color: white;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            height: 48px;
        }
        
        .header h1 {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.65rem;
            background: rgba(255,255,255,0.12);
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }
        .status-dot.online { background: #2ecc71; }
        .status-dot.offline { background: #e74c3c; }
        
        .lang-btn {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            text-decoration: none;
            background: rgba(255,255,255,0.12);
            color: white;
        }
        .lang-btn.active {
            background: white;
            color: #1e2a3a;
        }
        
        .waiter-name {
            font-size: 0.7rem;
            background: rgba(255,255,255,0.12);
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .logout {
            color: white;
            text-decoration: none;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.12);
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        /* ========== MAIN LAYOUT ========== */
        .main-container {
            margin-top: 48px;
            height: calc(100vh - 48px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* ========== TOP BAR (Table + Send Button) ========== */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-bottom: 1px solid #e5e9f0;
            flex-shrink: 0;
            gap: 12px;
        }
        
        .table-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .table-selector label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a5b6e;
            white-space: nowrap;
        }
        
        .table-select {
            padding: 8px 12px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            font-size: 0.85rem;
            background: white;
            flex: 1;
            max-width: 160px;
        }
        
        .send-button {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(39, 174, 96, 0.3);
        }
        
        .send-button:active {
            transform: scale(0.97);
        }
        
        .send-button:disabled {
            background: #adb5bd;
            box-shadow: none;
            cursor: not-allowed;
        }
        
        /* ========== CONTENT AREA ========== */
        .content-area {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 0;
        }
        
        /* ========== LEFT: MENU ========== */
        .menu-area {
            flex: 6;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .categories-row {
            display: flex;
            gap: 6px;
            padding: 8px 12px;
            overflow-x: auto;
            background: white;
            border-bottom: 1px solid #e5e9f0;
            flex-shrink: 0;
        }
        
        .category-chip {
            padding: 6px 16px;
            background: #f1f3f5;
            border: none;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #4a5b6e;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        
        .category-chip.active {
            background: #3498db;
            color: white;
        }
        
        .items-grid {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 8px;
            align-content: start;
        }
        
        .item-card {
            background: white;
            border-radius: 12px;
            padding: 10px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e5e9f0;
        }
        
        .item-card:active {
            transform: scale(0.97);
            background: #f0f2f5;
        }
        
        .item-card h4 {
            font-size: 0.8rem;
            margin-bottom: 6px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .item-price {
            font-size: 0.9rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .item-kitchen {
            font-size: 0.6rem;
            color: #7f8c8d;
            margin-top: 4px;
            padding: 2px 6px;
            background: #f1f3f5;
            border-radius: 20px;
            display: inline-block;
        }
        
        /* ========== RIGHT: ORDER ========== */
        .order-area {
            flex: 4;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: white;
            border-left: 1px solid #e5e9f0;
        }
        
        .order-header {
            padding: 10px 12px;
            background: #fafcfd;
            border-bottom: 1px solid #e5e9f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .order-header h3 {
            font-size: 0.85rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .table-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
        }
        
        .table-badge.empty {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .order-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        
        .order-item {
            background: #f8fafc;
            border-radius: 10px;
            padding: 8px;
            margin-bottom: 6px;
            border-left: 3px solid #3498db;
        }
        
        body[dir="rtl"] .order-item {
            border-left: none;
            border-right: 3px solid #3498db;
        }
        
        .order-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        
        .order-item-name {
            font-weight: 600;
            font-size: 0.8rem;
            color: #2c3e50;
        }
        
        .order-item-subtotal {
            font-size: 0.75rem;
            font-weight: 600;
            color: #27ae60;
        }
        
        .order-item-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .qty-control {
            display: flex;
            align-items: center;
            gap: 6px;
            background: white;
            padding: 2px 4px;
            border-radius: 25px;
            border: 1px solid #e5e9f0;
        }
        
        .qty-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: #3498db;
            color: white;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 20px;
            cursor: pointer;
        }
        
        .qty-btn:disabled {
            background: #cbd5e1;
        }
        
        .qty-number {
            min-width: 28px;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .item-notes-input {
            flex: 1;
            padding: 4px 8px;
            border: 1px solid #e5e9f0;
            border-radius: 20px;
            font-size: 0.65rem;
            background: white;
        }
        
        .remove-item {
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 2px 6px;
        }
        
        .order-footer {
            padding: 10px 12px;
            background: white;
            border-top: 1px solid #e5e9f0;
            flex-shrink: 0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0;
        }
        
        .total-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4a5b6e;
        }
        
        .total-amount {
            font-size: 1rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .empty-order {
            text-align: center;
            color: #95a5a6;
            padding: 30px 15px;
            font-size: 0.75rem;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        
        .modal-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            width: 85%;
            max-width: 280px;
            text-align: center;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .confirm-btn { background: #27ae60; color: white; }
        .cancel-btn { background: #e74c3c; color: white; }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 16px;
            border-radius: 40px;
            color: white;
            z-index: 300;
            font-size: 0.75rem;
            white-space: nowrap;
            background: #2c3e50;
        }
        .toast-success { background: #27ae60; }
        .toast-error { background: #e74c3c; }
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 400;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .spinner {
            width: 35px;
            height: 35px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .content-area {
                flex-direction: column;
            }
            
            .menu-area {
                flex: 5;
                border-bottom: 1px solid #e5e9f0;
            }
            
            .order-area {
                flex: 4;
                border-left: none;
            }
            
            .items-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-bar {
                padding: 6px 10px;
            }
            
            .table-select {
                max-width: 130px;
                font-size: 0.75rem;
                padding: 6px 10px;
            }
            
            .send-button {
                padding: 6px 16px;
                font-size: 0.75rem;
            }
            
            .waiter-name, .status span:last-child {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .items-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            
            .category-chip {
                padding: 4px 12px;
                font-size: 0.7rem;
            }
            
            .table-selector label {
                display: none;
            }
        }
        
        ::-webkit-scrollbar {
            width: 3px;
            height: 3px;
        }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

                /* ============================================
           RTL Support for Arabic Language
           ============================================ */
        body[dir="rtl"] {
            text-align: right;
        }
        
        /* Categories Column in RTL */
        body[dir="rtl"] .categories-column {
            border-right: none;
            border-left: 1px solid #e9ecef;
        }
        
        /* Category Items in RTL */
        body[dir="rtl"] .category-item {
            flex-direction: column;
        }
        
        /* Mobile Categories in RTL */
        @media (max-width: 768px) {
            body[dir="rtl"] .categories-column {
                border-left: none;
                border-right: none;
            }
            
            body[dir="rtl"] .category-item {
                flex-direction: column;
            }
        }
        
        /* Items Grid - No changes needed for RTL */
        
        /* Order Panel in RTL */
        body[dir="rtl"] .order-column {
            border-left: none;
            border-right: 1px solid #e9ecef;
        }
        
        body[dir="rtl"] .order-item-card {
            border-left: none;
            border-right: 4px solid #3498db;
        }
        
        /* Quantity Controls in RTL */
        body[dir="rtl"] .quantity-control {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .order-item-controls {
            flex-direction: row-reverse;
            justify-content: space-between;
        }
        
        /* Remove button in RTL */
        body[dir="rtl"] .remove-item-btn {
            float: left;
        }
        
        /* Order header in RTL */
        body[dir="rtl"] .order-header {
            text-align: right;
        }
        
        /* Total row in RTL */
        body[dir="rtl"] .total-row {
            flex-direction: row-reverse;
            justify-content: space-between;
        }
        
        /* Action Bar in RTL */
        body[dir="rtl"] .action-bar {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .table-selector {
            flex-direction: row-reverse;
        }
        
        /* Send button positioning in RTL */
        body[dir="rtl"] .send-button {
            margin-left: 0;
            margin-right: auto;
        }
        
        /* Modal in RTL */
        body[dir="rtl"] .modal-card {
            text-align: center;
        }
        
        body[dir="rtl"] .modal-buttons {
            flex-direction: row-reverse;
        }
        
        /* Toast in RTL */
        body[dir="rtl"] .toast {
            left: auto;
            right: 50%;
            transform: translateX(50%);
        }
        
        /* Desktop - Categories in RTL */
        @media (min-width: 769px) {
            body[dir="rtl"] .categories-column {
                border-right: none;
                border-left: 1px solid #e9ecef;
            }
        }
        
        /* Mobile adjustments for RTL */
        @media (max-width: 768px) {
            body[dir="rtl"] .order-column {
                border-right: none;
                border-left: none;
            }
            
            body[dir="rtl"] .category-item {
                flex-direction: column;
            }
            
            body[dir="rtl"] .action-bar {
                flex-direction: row;
            }
        }
        
        /* Small mobile RTL */
        @media (max-width: 480px) {
            body[dir="rtl"] .category-item {
                padding: 6px 4px;
            }
        }

                /* ============================================
           RTL Support - Arabic Language
           ============================================ */
        body[dir="rtl"] {
            text-align: right;
        }
        
        body[dir="rtl"] .categories-column {
            border-right: none;
            border-left: 1px solid #e9ecef;
        }
        
        body[dir="rtl"] .order-column {
            border-left: none;
            border-right: 1px solid #e9ecef;
        }
        
        body[dir="rtl"] .order-item-card {
            border-left: none;
            border-right: 4px solid #3498db;
        }
        
        body[dir="rtl"] .quantity-control {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .order-item-controls {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .remove-item-btn {
            float: left;
        }
        
        body[dir="rtl"] .total-row {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .action-bar {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .table-selector {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .send-button {
            margin-left: 0;
            margin-right: auto;
        }
        
        body[dir="rtl"] .modal-buttons {
            flex-direction: row-reverse;
        }
        
        body[dir="rtl"] .toast {
            left: auto;
            right: 50%;
            transform: translateX(50%);
        }
        
        @media (min-width: 769px) {
            body[dir="rtl"] .categories-column {
                border-right: none;
                border-left: 1px solid #e9ecef;
            }
        }
        
        @media (max-width: 768px) {
            body[dir="rtl"] .order-column {
                border-right: none;
            }
            
            body[dir="rtl"] .category-item {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍽️ <?php echo t('waiter_terminal'); ?></h1>
        <div class="header-right">
            <div class="status">
                <span class="status-dot online" id="status-dot"></span>
                <span id="status-text"><?php echo t('connected'); ?></span>
            </div>
            <div class="lang-switch">
                <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
            </div>
            <span class="waiter-name">👨‍🍳 <?php echo $_SESSION['full_name']; ?></span>
            <a href="../logout.php" class="logout">🚪</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Action Bar: Table Selector + Send Button (RIGHT side) -->
        <div class="action-bar">
            <div class="table-selector">
                <label>📋 <?php echo t('select_table'); ?>:</label>
                <select id="table-select" class="table-select">
                    <option value="">-- <?php echo t('choose_table'); ?> --</option>
                    <?php foreach ($tables as $table): ?>
                    <option value="<?php echo $table['id']; ?>"><?php echo htmlspecialchars($table['table_name'] ?: 'Table ' . $table['table_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="send-button" id="send-btn" onclick="submitOrder()" disabled>🚀 <?php echo t('send_to_kitchen'); ?></button>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Left: Menu -->
            <div class="menu-area">
                <div class="categories-row" id="categories-row">
                    <?php foreach ($categories as $cat): ?>
                    <button class="category-chip" onclick="loadCategory(<?php echo $cat['id']; ?>, this)">
                        <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['item_count']; ?>)
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="items-grid" id="items-grid">
                    <div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #95a5a6;">
                        👆 <?php echo t('click_to_add'); ?>
                    </div>
                </div>
            </div>

            <!-- Right: Order -->
            <div class="order-area">
                <div class="order-header">
                    <h3>🛒 <?php echo t('current_order'); ?></h3>
                    <span id="table-badge" class="table-badge empty"><?php echo t('no_table_selected'); ?></span>
                </div>
                <div class="order-list" id="order-list">
                    <div class="empty-order">🍽️ <?php echo t('no_items'); ?></div>
                </div>
                <div class="order-footer">
                    <div class="total-row">
                        <span class="total-label"><?php echo t('total'); ?>:</span>
                        <span class="total-amount" id="total-amount">$0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-card">
            <h3>📋 <?php echo t('confirm_order'); ?></h3>
            <p><?php echo t('confirm_send'); ?></p>
            <p><strong id="modal-table"></strong></p>
            <p><strong>$<span id="modal-total"></span></strong></p>
            <div class="modal-buttons">
                <button class="confirm-btn" onclick="confirmOrder()">✅ <?php echo t('yes_send'); ?></button>
                <button class="cancel-btn" onclick="closeModal()">❌ <?php echo t('cancel'); ?></button>
            </div>
        </div>
    </div>

    <div id="loading" class="loading"><div class="spinner"></div></div>

    <script>
    let currentOrder = [];
    let currentCategoryId = null;
    let categories = <?php echo json_encode($categories); ?>;
    let tables = <?php echo json_encode($tables); ?>;

    window.onload = function() {
        <?php if (!empty($categories)): ?>
        document.querySelector('.category-chip')?.click();
        <?php endif; ?>
        document.getElementById('table-select').addEventListener('change', updateOrderDisplay);
        updateConnection();
        window.addEventListener('online', updateConnection);
        window.addEventListener('offline', updateConnection);
    };

    function updateConnection() {
        const dot = document.getElementById('status-dot');
        const text = document.getElementById('status-text');
        if (navigator.onLine) {
            dot.className = 'status-dot online';
            text.textContent = '<?php echo t('connected'); ?>';
        } else {
            dot.className = 'status-dot offline';
            text.textContent = '<?php echo t('offline'); ?>';
        }
    }

    function loadCategory(id, btn) {
        document.querySelectorAll('.category-chip').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategoryId = id;
        
        document.getElementById('items-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;">⏳ Loading...</div>';
        
        fetch(`ajax_get_items.php?category_id=${id}&t=${Date.now()}`)
            .then(r => r.json())
            .then(items => {
                const grid = document.getElementById('items-grid');
                if (!items.length) {
                    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;">📭 No items</div>';
                    return;
                }
                const cat = categories.find(c => c.id == id);
                const kitchenId = cat?.kitchen_id || 1;
                const kitchenName = cat?.kitchen_name || 'Kitchen';
                grid.innerHTML = items.map(item => `
                    <div class="item-card" onclick="addItem(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price}, ${kitchenId}, '${kitchenName}')">
                        <h4>${item.name}</h4>
                        <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
                        <span class="item-kitchen">${kitchenName}</span>
                    </div>
                `).join('');
            });
    }

    function addItem(id, name, price, kitchenId, kitchenName) {
        const existing = currentOrder.find(i => i.id === id);
        if (existing) {
            existing.qty++;
            existing.subtotal = existing.qty * existing.price;
        } else {
            currentOrder.push({
                id, name, price, kitchenId, kitchenName,
                qty: 1, notes: '', subtotal: price
            });
        }
        updateOrderDisplay();
        showToast(`➕ ${name}`, 'success');
    }

    function updateQty(index, delta) {
        const item = currentOrder[index];
        const newQty = item.qty + delta;
        if (newQty < 1) return;
        item.qty = newQty;
        item.subtotal = item.qty * item.price;
        updateOrderDisplay();
    }

    function updateNotes(index, notes) {
        currentOrder[index].notes = notes;
    }

    function removeItem(index) {
        currentOrder.splice(index, 1);
        updateOrderDisplay();
        showToast('🗑️ Removed', 'error');
    }

    function updateOrderDisplay() {
        const listDiv = document.getElementById('order-list');
        const sendBtn = document.getElementById('send-btn');
        const tableSelect = document.getElementById('table-select');
        const tableBadge = document.getElementById('table-badge');
        let total = 0;

        if (tableSelect.value) {
            const opt = tableSelect.options[tableSelect.selectedIndex];
            tableBadge.textContent = `✅ ${opt.textContent}`;
            tableBadge.className = 'table-badge';
        } else {
            tableBadge.textContent = '⚠️ <?php echo t('no_table_selected'); ?>';
            tableBadge.className = 'table-badge empty';
        }

        if (currentOrder.length === 0) {
            listDiv.innerHTML = '<div class="empty-order">🍽️ <?php echo t('no_items'); ?></div>';
            sendBtn.disabled = true;
        } else {
            listDiv.innerHTML = currentOrder.map((item, idx) => `
                <div class="order-item">
                    <div class="order-item-header">
                        <span class="order-item-name">${item.name}</span>
                        <span class="order-item-subtotal">$${item.subtotal.toFixed(2)}</span>
                        <button class="remove-item" onclick="removeItem(${idx})">✕</button>
                    </div>
                    <div class="order-item-controls">
                        <div class="qty-control">
                            <button class="qty-btn" onclick="updateQty(${idx}, -1)" ${item.qty <= 1 ? 'disabled' : ''}>-</button>
                            <span class="qty-number">${item.qty}</span>
                            <button class="qty-btn" onclick="updateQty(${idx}, 1)">+</button>
                        </div>
                        <input type="text" class="item-notes-input" placeholder="📝 <?php echo t('special_instructions'); ?>" 
                               value="${item.notes || ''}" onchange="updateNotes(${idx}, this.value)">
                    </div>
                </div>
            `).join('');
            total = currentOrder.reduce((sum, i) => sum + i.subtotal, 0);
            sendBtn.disabled = !tableSelect.value;
        }
        
        document.getElementById('total-amount').textContent = `$${total.toFixed(2)}`;
    }

    function submitOrder() {
        const tableSelect = document.getElementById('table-select');
        if (!tableSelect.value) {
            showToast('<?php echo t('select_table_warning'); ?>', 'error');
            return;
        }
        if (currentOrder.length === 0) {
            showToast('<?php echo t('no_items_warning'); ?>', 'error');
            return;
        }
        
        const opt = tableSelect.options[tableSelect.selectedIndex];
        document.getElementById('modal-table').textContent = opt.textContent;
        document.getElementById('modal-total').textContent = document.getElementById('total-amount').textContent.replace('$', '');
        document.getElementById('confirm-modal').style.display = 'flex';
    }

    function confirmOrder() {
        closeModal();
        
        const tableSelect = document.getElementById('table-select');
        const tableId = tableSelect.value;
        const tableNumber = tableSelect.options[tableSelect.selectedIndex].textContent;
        
        const orderData = {
            table_id: tableId,
            table_number: tableNumber,
            items: currentOrder.map(i => ({
                id: i.id, quantity: i.qty, notes: i.notes,
                kitchen_id: i.kitchenId, price: i.price
            }))
        };
        
        showLoading();
        fetch('ajax_submit_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('✅ <?php echo t('order_sent'); ?>', 'success');
                currentOrder = [];
                tableSelect.value = '';
                updateOrderDisplay();
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(() => {
            hideLoading();
            showToast('❌ <?php echo t('error'); ?>', 'error');
        });
    }

    function closeModal() {
        document.getElementById('confirm-modal').style.display = 'none';
    }

    function showToast(msg, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }

    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
    }
    
    function hideLoading() {
        document.getElementById('loading').style.display = 'none';
    }

    window.onclick = e => { if (e.target === document.getElementById('confirm-modal')) closeModal(); };
    </script>
</body>
</html>