<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get categories (lightweight, always needed)
    $query = "SELECT c.*, k.name as kitchen_name, 
                     COUNT(mi.id) as item_count
              FROM categories c
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = 1
              WHERE c.is_active = 1
              GROUP BY c.id
              ORDER BY c.sort_order";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Get active tables (lightweight)
    $query = "SELECT * FROM restaurant_tables 
              WHERE is_active = 1 
              ORDER BY sort_order, table_number";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load menu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>AJAX Waiter Terminal - Restaurant POS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f8f9fa;
            height: 100vh;
            overflow: hidden;
        }
        .app-header {
            background: #2c3e50;
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .app-header h2 {
            font-size: 1.2rem;
            font-weight: 500;
        }
        .waiter-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .waiter-badge {
            background: #3498db;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .connection-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-online {
            background: #27ae60;
            box-shadow: 0 0 5px #27ae60;
        }
        .status-offline {
            background: #e74c3c;
            box-shadow: 0 0 5px #e74c3c;
        }
        .logout-btn {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .main-container {
            margin-top: 60px;
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
        }
        .table-selector {
            background: white;
            padding: 12px 16px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .table-selector label {
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }
        .table-dropdown {
            flex: 1;
            min-width: 200px;
            padding: 10px 12px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }
        .table-dropdown:focus {
            border-color: #3498db;
            outline: none;
        }
        .content-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
            flex-direction: row;
        }
        .categories-sidebar {
            width: 120px;
            background: white;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
            padding: 8px 0;
        }
        .category-btn {
            width: 100%;
            padding: 16px 8px;
            border: none;
            border-bottom: 1px solid #e9ecef;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: #495057;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .category-btn.active {
            background: #3498db;
            color: white;
            border-left: 4px solid #2980b9;
        }
        .category-btn small {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .menu-panel {
            flex: 1;
            background: #f8f9fa;
            overflow-y: auto;
            padding: 16px;
        }
        .menu-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .menu-item-card {
            background: white;
            border-radius: 12px;
            padding: 16px 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            text-align: center;
            position: relative;
        }
        .menu-item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        .menu-item-card.loading {
            opacity: 0.5;
            pointer-events: none;
        }
        .menu-item-card h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .menu-item-card .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }
        .menu-item-card .kitchen-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-top: 8px;
            color: #495057;
        }
        .order-panel {
            width: 350px;
            background: white;
            border-left: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .order-header {
            padding: 16px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .order-header h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .table-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .order-items {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        .order-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
            position: relative;
            border-left: 4px solid #3498db;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .order-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .item-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .item-price {
            color: #27ae60;
            font-weight: 600;
        }
        .item-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .qty-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #3498db;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .qty-btn:hover {
            background: #2980b9;
        }
        .qty-btn:disabled {
            background: #ced4da;
            cursor: not-allowed;
        }
        .qty-value {
            font-size: 1rem;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        .qty-input {
            width: 50px;
            padding: 6px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 1rem;
        }
        .item-notes {
            width: 100%;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-top: 8px;
            resize: vertical;
            transition: border 0.2s;
        }
        .item-notes:focus {
            border-color: #3498db;
            outline: none;
        }
        .remove-item {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .remove-item:hover {
            background: #c0392b;
        }
        .order-footer {
            padding: 16px;
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
        }
        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 16px;
            color: #2c3e50;
        }
        .submit-order {
            width: 100%;
            padding: 16px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .submit-order:hover:not(:disabled) {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
        }
        .submit-order:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .empty-order {
            text-align: center;
            color: #adb5bd;
            padding: 32px 16px;
            font-size: 0.95rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            animation: modalSlide 0.3s;
        }
        @keyframes modalSlide {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-content h3 {
            margin-bottom: 16px;
            color: #2c3e50;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .confirm-btn {
            background: #27ae60;
            color: white;
        }
        .confirm-btn:hover {
            background: #219a52;
        }
        .cancel-btn {
            background: #e74c3c;
            color: white;
        }
        .cancel-btn:hover {
            background: #c0392b;
        }
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            z-index: 2000;
            animation: notificationSlide 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .notification.success { background: #27ae60; }
        .notification.error { background: #e74c3c; }
        .notification.warning { background: #f39c12; }
        .notification.info { background: #3498db; }
        @keyframes notificationSlide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .content-wrapper {
                flex-direction: column;
            }
            .categories-sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                overflow-x: auto;
                padding: 8px;
            }
            .category-btn {
                width: auto;
                min-width: 100px;
                border-bottom: none;
                border-right: 1px solid #e9ecef;
            }
            .order-panel {
                width: 100%;
                border-left: none;
                border-top: 1px solid #e9ecef;
                max-height: 50%;
            }
            .menu-items-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="app-header">
        <h2>🍽️ AJAX Waiter Terminal</h2>
        <div class="waiter-info">
            <span class="connection-status status-online" id="connection-status"></span>
            <span class="waiter-badge"><?php echo $_SESSION['full_name']; ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Table Selector -->
        <div class="table-selector">
            <label>Select Table:</label>
            <select id="table-select" class="table-dropdown">
                <option value="">-- Choose Table --</option>
                <?php foreach ($tables as $table): ?>
                <option value="<?php echo $table['id']; ?>" data-number="<?php echo $table['table_number']; ?>">
                    <?php echo htmlspecialchars($table['table_name'] ?: 'Table ' . $table['table_number']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="content-wrapper">
            <!-- Categories Sidebar -->
            <div class="categories-sidebar" id="categories-sidebar">
                <?php foreach ($categories as $category): ?>
                <button class="category-btn" onclick="loadCategory(<?php echo $category['id']; ?>, this)">
                    <?php echo htmlspecialchars($category['name']); ?>
                    <small><?php echo $category['item_count']; ?> items</small>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Menu Items Panel -->
            <div class="menu-panel">
                <div id="menu-items" class="menu-items-grid">
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #6c757d;">
                        Select a category to view items
                    </div>
                </div>
            </div>

            <!-- Order Panel -->
            <div class="order-panel">
                <div class="order-header">
                    <h3>Current Order</h3>
                    <div class="table-info" id="selected-table-info">No table selected</div>
                </div>

                <div id="order-items" class="order-items">
                    <div class="empty-order">
                        No items in order<br>
                        <small>Click on menu items to add</small>
                    </div>
                </div>

                <div class="order-footer">
                    <div class="order-total">
                        <span>Total:</span>
                        <span id="order-total">$0.00</span>
                    </div>
                    <button class="submit-order" id="submit-btn" onclick="submitOrder()" disabled>Send to Kitchen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Order Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <h3>Confirm Order</h3>
            <p>Send order to kitchen?</p>
            <p><strong>Table:</strong> <span id="confirm-table"></span></p>
            <p><strong>Total:</strong> $<span id="confirm-total"></span></p>
            <div class="modal-buttons">
                <button class="confirm-btn" onclick="confirmSubmit()">Yes, Send</button>
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
    // Global variables
    let currentOrder = [];
    let currentCategoryId = null;
    let categories = <?php echo json_encode($categories); ?>;
    let tables = <?php echo json_encode($tables); ?>;
    let pendingRequests = 0;
    let offlineQueue = [];

    // Connection monitoring
    function updateConnectionStatus() {
        const statusDot = document.getElementById('connection-status');
        if (navigator.onLine) {
            statusDot.className = 'connection-status status-online';
        } else {
            statusDot.className = 'connection-status status-offline';
            showNotification('Offline mode - Orders will be queued', 'warning');
        }
    }

    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);
    window.onload = function() {
        updateConnectionStatus();
        <?php if (!empty($categories)): ?>
        const firstBtn = document.querySelector('.category-btn');
        if (firstBtn) {
            firstBtn.click();
        }
        <?php endif; ?>
        updateOrderDisplay();
        
        document.getElementById('table-select').addEventListener('change', function() {
            updateOrderDisplay();
        });
    };

    // Show/hide loading
    function showLoading() {
        pendingRequests++;
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    function hideLoading() {
        pendingRequests--;
        if (pendingRequests <= 0) {
            pendingRequests = 0;
            document.getElementById('loading-overlay').style.display = 'none';
        }
    }

    // AJAX function to load menu items
    function loadCategory(categoryId, btnElement) {
        // Update active button
        document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        
        currentCategoryId = categoryId;
        
        // Show loading in menu panel
        document.getElementById('menu-items').innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;">Loading...</div>';
        
        // AJAX request
        showLoading();
        fetch(`ajax_get_items.php?category_id=${categoryId}&t=${new Date().getTime()}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(items => {
                displayMenuItems(items);
                hideLoading();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('menu-items').innerHTML = '<div style="color: red; padding: 20px;">Failed to load items. <button onclick="loadCategory(' + categoryId + ', btnElement)">Retry</button></div>';
                hideLoading();
            });
    }

    function displayMenuItems(items) {
        const container = document.getElementById('menu-items');
        
        if (!items || items.length === 0) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;">No items in this category</div>';
            return;
        }
        
        const category = categories.find(c => c.id == currentCategoryId);
        const kitchenName = category ? (category.kitchen_name || 'Main Kitchen') : 'Main Kitchen';
        const kitchenId = category ? (category.kitchen_id || 1) : 1;
        
        container.innerHTML = items.map(item => `
            <div class="menu-item-card" onclick="addToOrder(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price}, ${kitchenId}, '${kitchenName}')">
                <h3>${item.name}</h3>
                <div class="price">$${parseFloat(item.price).toFixed(2)}</div>
                <span class="kitchen-tag">${kitchenName}</span>
            </div>
        `).join('');
    }

    function addToOrder(itemId, itemName, itemPrice, kitchenId, kitchenName) {
        const existingItem = currentOrder.find(item => item.id === itemId);
        
        if (existingItem) {
            existingItem.quantity += 1;
            existingItem.subtotal = existingItem.quantity * existingItem.price;
        } else {
            currentOrder.push({
                id: itemId,
                name: itemName,
                price: itemPrice,
                kitchen_id: kitchenId,
                kitchen_name: kitchenName,
                quantity: 1,
                notes: '',
                subtotal: itemPrice
            });
        }
        
        updateOrderDisplay();
        showNotification(`${itemName} added to order`, 'success');
    }

    function updateQuantity(orderIndex, change) {
        const item = currentOrder[orderIndex];
        const newQuantity = item.quantity + change;
        
        if (newQuantity < 1) return;
        
        item.quantity = newQuantity;
        item.subtotal = item.quantity * item.price;
        
        updateOrderDisplay();
    }

    function updateQuantityInput(orderIndex, value) {
        const item = currentOrder[orderIndex];
        const newQuantity = parseInt(value) || 1;
        
        if (newQuantity < 1) return;
        
        item.quantity = newQuantity;
        item.subtotal = item.quantity * item.price;
        
        updateOrderDisplay();
    }

    function updateNotes(orderIndex, notes) {
        currentOrder[orderIndex].notes = notes;
    }

    function removeItem(orderIndex) {
        const item = currentOrder[orderIndex];
        if (confirm(`Remove ${item.name} from order?`)) {
            currentOrder.splice(orderIndex, 1);
            updateOrderDisplay();
            showNotification('Item removed', 'warning');
        }
    }

    function updateOrderDisplay() {
        const container = document.getElementById('order-items');
        const submitBtn = document.getElementById('submit-btn');
        const tableSelect = document.getElementById('table-select');
        const tableInfo = document.getElementById('selected-table-info');
        let total = 0;

        // Update table info
        if (tableSelect.value) {
            const selectedOption = tableSelect.options[tableSelect.selectedIndex];
            tableInfo.textContent = selectedOption.textContent;
        } else {
            tableInfo.textContent = 'No table selected';
        }

        if (currentOrder.length === 0) {
            container.innerHTML = '<div class="empty-order">No items in order<br><small>Click on menu items to add</small></div>';
            submitBtn.disabled = true;
        } else {
            container.innerHTML = currentOrder.map((item, index) => {
                total += item.subtotal;
                return `
                <div class="order-item">
                    <button class="remove-item" onclick="removeItem(${index})">×</button>
                    <div class="order-item-header">
                        <span class="item-name">${item.name}</span>
                        <span class="item-price">$${item.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="item-controls">
                        <div class="quantity-control">
                            <button class="qty-btn" onclick="updateQuantity(${index}, -1)" ${item.quantity <= 1 ? 'disabled' : ''}>−</button>
                            <input type="number" class="qty-input" value="${item.quantity}" min="1" 
                                   onchange="updateQuantityInput(${index}, this.value)" 
                                   onkeyup="updateQuantityInput(${index}, this.value)">
                            <button class="qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                        </div>
                        <span style="font-size:0.8rem; color:#6c757d;">${item.kitchen_name}</span>
                    </div>
                    <textarea class="item-notes" placeholder="Special instructions..." 
                              onchange="updateNotes(${index}, this.value)">${item.notes || ''}</textarea>
                </div>
            `}).join('');
            
            submitBtn.disabled = !tableSelect.value;
        }

        document.getElementById('order-total').textContent = `$${total.toFixed(2)}`;
    }

    function submitOrder() {
        const tableSelect = document.getElementById('table-select');
        
        if (!tableSelect.value) {
            showNotification('Please select a table', 'warning');
            return;
        }
        
        if (currentOrder.length === 0) {
            showNotification('No items to submit', 'warning');
            return;
        }

        // Show confirmation modal
        const selectedOption = tableSelect.options[tableSelect.selectedIndex];
        document.getElementById('confirm-table').textContent = selectedOption.textContent;
        document.getElementById('confirm-total').textContent = document.getElementById('order-total').textContent.replace('$', '');
        document.getElementById('confirm-modal').style.display = 'flex';
    }

    function confirmSubmit() {
        closeModal();
        
        const tableSelect = document.getElementById('table-select');
        const selectedTableId = tableSelect.value;
        const selectedTable = tables.find(t => t.id == selectedTableId);

        const orderData = {
            table_id: selectedTableId,
            table_number: selectedTable.table_number,
            items: currentOrder.map(item => ({
                id: item.id,
                quantity: item.quantity,
                notes: item.notes || '',
                kitchen_id: item.kitchen_id,
                price: item.price
            }))
        };

        // Check if online
        if (!navigator.onLine) {
            // Queue for later
            offlineQueue.push(orderData);
            showNotification('Order queued (offline mode)', 'warning');
            currentOrder = [];
            document.getElementById('table-select').value = '';
            updateOrderDisplay();
            return;
        }

        showLoading();
        
        fetch('ajax_submit_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('Order sent to kitchen!', 'success');
                // Clear the order
                currentOrder = [];
                document.getElementById('table-select').value = '';
                updateOrderDisplay();
                
                // Process any queued orders
                processOfflineQueue();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to submit order', 'error');
        });
    }

    function processOfflineQueue() {
        if (offlineQueue.length === 0 || !navigator.onLine) return;
        
        showNotification(`Processing ${offlineQueue.length} queued orders...`, 'info');
        
        // Process each queued order
        offlineQueue.forEach((orderData, index) => {
            fetch('ajax_submit_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    offlineQueue.splice(index, 1);
                    showNotification('Queued order processed', 'success');
                }
            })
            .catch(error => console.error('Error processing queue:', error));
        });
    }

    function closeModal() {
        document.getElementById('confirm-modal').style.display = 'none';
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('confirm-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    </script>
</body>
</html>