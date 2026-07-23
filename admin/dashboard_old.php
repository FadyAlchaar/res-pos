<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get statistics
    $stats = [];
    
    // Kitchens count
    $query = "SELECT COUNT(*) as count FROM kitchens WHERE is_active = 1";
    $stmt = $db->query($query);
    $stats['kitchens'] = $stmt->fetch()['count'];
    
    // Categories count
    $query = "SELECT COUNT(*) as count FROM categories WHERE is_active = 1";
    $stmt = $db->query($query);
    $stats['categories'] = $stmt->fetch()['count'];
    
    // Menu items count
    $query = "SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1";
    $stmt = $db->query($query);
    $stats['items'] = $stmt->fetch()['count'];
    
    // Today's orders
    $query = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->query($query);
    $stats['today_orders'] = $stmt->fetch()['count'];
    
    // Printer status
    $query = "SELECT COUNT(*) as count FROM kitchens WHERE status = 'online'";
    $stmt = $db->query($query);
    $stats['printers_online'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $error = "Failed to load dashboard: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant POS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        .management-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .management-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .management-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #219a52;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th {
            background: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-online {
            background: #d4edda;
            color: #155724;
        }
        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }
        .status-testing {
            background: #fff3cd;
            color: #856404;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-content h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            z-index: 2000;
            animation: slideIn 0.3s;
        }
        .notification.success { background: #27ae60; }
        .notification.error { background: #e74c3c; }
        .notification.info { background: #3498db; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            justify-content: center;
            align-items: center;
            z-index: 3000;
        }
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .printer-test {
            display: inline-block;
            margin-left: 10px;
            padding: 3px 8px;
            background: #3498db;
            color: white;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        .printer-test:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📊 Admin Dashboard - Restaurant Management System</h2>
        <div>
            <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a href="../logout.php" style="color: white; margin-left: 20px;">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Active Kitchens</h3>
                <div class="value"><?php echo $stats['kitchens']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Categories</h3>
                <div class="value"><?php echo $stats['categories']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Menu Items</h3>
                <div class="value"><?php echo $stats['items']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <div class="value"><?php echo $stats['today_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Printers Online</h3>
                <div class="value"><?php echo $stats['printers_online']; ?></div>
            </div>
        </div>

        <!-- Management Grid -->
        <div class="management-grid">
            <!-- Kitchens Management -->
            <div class="management-card">
                <h2>🍳 Kitchens & Printers</h2>
                <button class="btn btn-primary" onclick="openModal('kitchen')">+ Add Kitchen</button>
                <div id="kitchens-list" style="margin-top: 20px;">
                    <!-- Kitchens will be loaded here -->
                </div>
            </div>

            <!-- Categories Management -->
            <div class="management-card">
                <h2>📑 Categories</h2>
                <button class="btn btn-primary" onclick="openModal('category')">+ Add Category</button>
                <div id="categories-list" style="margin-top: 20px;">
                    <!-- Categories will be loaded here -->
                </div>
            </div>

            <!-- Menu Items Management -->
            <div class="management-card">
                <h2>🍽️ Menu Items</h2>
                <button class="btn btn-primary" onclick="openModal('item')">+ Add Menu Item</button>
                <div id="items-list" style="margin-top: 20px;">
                    <!-- Menu items will be loaded here -->
                </div>
            </div>

            <!-- Printers Status -->
            <div class="management-card">
                <h2>🖨️ Printer Status</h2>
                <button class="btn btn-success" onclick="testAllPrinters()">Test All Printers</button>
                <div id="printers-status" style="margin-top: 20px;">
                    <!-- Printer status will be loaded here -->
                </div>
            </div>
            <!-- Settings Card -->
            <div class="management-card">
                <h2>⚙️ Restaurant Settings</h2>
                <p style="color: #7f8c8d; margin-bottom: 20px;">Configure your restaurant tables and name</p>
                <div style="text-align: center;">
                    <a href="settings.php" class="btn btn-primary">Configure Tables</a>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <?php
                    // Get current table count
                    $countQuery = "SELECT COUNT(*) as count FROM restaurant_tables WHERE is_active = 1";
                    $countStmt = $db->query($countQuery);
                    $tableCount = $countStmt->fetch()['count'];
                    
                    $settingQuery = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'total_tables'";
                    $settingStmt = $db->query($settingQuery);
                    $totalTables = $settingStmt->fetch()['setting_value'] ?? 60;
                    ?>
                    <p><strong>Current Tables:</strong> <?php echo $tableCount; ?> active</p>
                    <p><strong>Configured Total:</strong> <?php echo $totalTables; ?> tables</p>
                </div>
            </div>
            <!-- User Management Card -->
            <div class="management-card">
                <h2>👥 User Management</h2>
                <p style="color: #7f8c8d; margin-bottom: 20px;">Add, edit, or remove system users</p>
                <div style="text-align: center;">
                    <a href="users.php" class="btn btn-primary">Manage Users</a>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <p><strong>Current Users:</strong> <?php
                        $countQuery = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
                        $countStmt = $db->query($countQuery);
                        echo $countStmt->fetch()['count'];
                    ?></p>
                    <p><small>Roles: Admin, Waiter, Kitchen Staff</small></p>
                </div>
            </div>
            <div class="management-card">
                <h2>🖥️ Kitchen Display</h2>
                <p style="color: #7f8c8d; margin-bottom: 20px;">Real-time order display for kitchen staff</p>
                <div style="text-align: center;">
                    <a href="../kitchen/display.php" target="_blank" class="btn btn-primary">Open Kitchen Display</a>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <p><small>Best viewed on tablet or mounted TV</small></p>
                    <p><small>Auto-refreshes every 5 seconds</small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kitchen Modal -->
    <div id="kitchenModal" class="modal">
        <div class="modal-content">
            <h3 id="kitchenModalTitle">Add New Kitchen</h3>
            <form id="kitchenForm" onsubmit="saveKitchen(event)">
                <input type="hidden" id="kitchen_id" name="id">
                
                <div class="form-group">
                    <label>Kitchen Name *</label>
                    <input type="text" id="kitchen_name" name="name" required>
                </div>

                <div class="form-group">
                    <label>Printer IP Address *</label>
                    <input type="text" id="printer_ip" name="printer_ip" placeholder="192.168.1.100" required>
                </div>

                <div class="form-group">
                    <label>Printer Port</label>
                    <input type="number" id="printer_port" name="printer_port" value="9100">
                </div>

                <div class="form-group">
                    <label>Printer Model</label>
                    <select id="printer_model" name="printer_model">
                        <option value="epson">Epson Thermal</option>
                        <option value="star">Star Micronics</option>
                        <option value="bixolon">Bixolon</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Paper Size</label>
                    <select id="paper_size" name="paper_size">
                        <option value="80mm">80mm</option>
                        <option value="58mm">58mm</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="kitchen_notes" name="notes" rows="3"></textarea>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Save Kitchen</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('kitchen')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h3 id="categoryModalTitle">Add New Category</h3>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="category_id" name="id">
                
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" id="category_name" name="name" required>
                </div>

                <div class="form-group">
                    <label>Kitchen *</label>
                    <select id="category_kitchen_id" name="kitchen_id" required>
                        <option value="">Select Kitchen</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" id="category_sort_order" name="sort_order" value="0">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="category_description" name="description" rows="3"></textarea>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Save Category</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('category')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Menu Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h3 id="itemModalTitle">Add New Menu Item</h3>
            <form id="itemForm" onsubmit="saveItem(event)">
                <input type="hidden" id="item_id" name="id">
                
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" id="item_name" name="name" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select id="item_category_id" name="category_id" required>
                        <option value="">Select Category</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" id="item_price" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="item_description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Preparation Time (minutes)</label>
                    <input type="number" id="preparation_time" name="preparation_time" value="10" min="0">
                </div>

                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" id="item_sort_order" name="sort_order" value="0">
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Save Item</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('item')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading">
        <div class="spinner"></div>
    </div>

    <script>
    // Load all data on page load
    window.onload = function() {
        loadKitchens();
        loadCategories();
        loadMenuItems();
        loadPrinterStatus();
    };

    // ==================== KITCHEN FUNCTIONS ====================
    function loadKitchens() {
        fetch('get_kitchens.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('kitchens-list');
                if (data.error) {
                    container.innerHTML = `<p class="error">${data.error}</p>`;
                    return;
                }
                
                let html = '<table class="table">';
                html += '<tr><th>Name</th><th>Printer IP</th><th>Status</th><th>Actions</th></tr>';
                
                data.forEach(kitchen => {
                    html += `<tr>
                        <td>${kitchen.name}</td>
                        <td>${kitchen.printer_ip || 'Not set'}</td>
                        <td><span class="status-badge status-${kitchen.status || 'offline'}">${kitchen.status || 'offline'}</span></td>
                        <td>
                            <button class="btn btn-primary btn-small" onclick="editKitchen(${kitchen.id})">Edit</button>
                            <button class="btn btn-warning btn-small" onclick="testPrinter(${kitchen.id})">Test</button>
                            <button class="btn btn-danger btn-small" onclick="deleteKitchen(${kitchen.id})">Delete</button>
                        </td>
                    </tr>`;
                });
                
                html += '</table>';
                container.innerHTML = html;
                
                // Update category kitchen dropdown
                updateKitchenDropdown(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load kitchens', 'error');
            });
    }

    function updateKitchenDropdown(kitchens) {
        const select = document.getElementById('category_kitchen_id');
        if (select) {
            select.innerHTML = '<option value="">Select Kitchen</option>';
            kitchens.forEach(kitchen => {
                select.innerHTML += `<option value="${kitchen.id}">${kitchen.name}</option>`;
            });
        }
    }

    function editKitchen(id) {
    console.log('Editing kitchen ID:', id); // Debug log
    
    // Show loading in the modal
    document.getElementById('kitchenModalTitle').textContent = 'Loading...';
    openModal('kitchen');
    
    fetch(`get_kitchen.php?id=${id}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(kitchen => {
            console.log('Kitchen data received:', kitchen); // Debug log
            
            // Populate the form fields
            document.getElementById('kitchen_id').value = kitchen.id || '';
            document.getElementById('kitchen_name').value = kitchen.name || '';
            document.getElementById('printer_ip').value = kitchen.printer_ip || '';
            document.getElementById('printer_port').value = kitchen.printer_port || 9100;
            document.getElementById('printer_model').value = kitchen.printer_model || 'epson';
            document.getElementById('paper_size').value = kitchen.paper_size || '80mm';
            document.getElementById('kitchen_notes').value = kitchen.notes || '';
            
            // Update modal title
            document.getElementById('kitchenModalTitle').textContent = 'Edit Kitchen';
        })
        .catch(error => {
            console.error('Error loading kitchen:', error);
            alert('Failed to load kitchen details. Check console for details.');
            closeModal('kitchen');
        });
}

    function saveKitchen(event) {
        event.preventDefault();
        
        const formData = {
            id: document.getElementById('kitchen_id').value,
            name: document.getElementById('kitchen_name').value,
            printer_ip: document.getElementById('printer_ip').value,
            printer_port: document.getElementById('printer_port').value,
            printer_model: document.getElementById('printer_model').value,
            paper_size: document.getElementById('paper_size').value,
            notes: document.getElementById('kitchen_notes').value
        };

        showLoading();

        fetch('save_kitchen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('Kitchen saved successfully', 'success');
                closeModal('kitchen');
                loadKitchens();
                loadPrinterStatus();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to save kitchen', 'error');
        });
    }

    function deleteKitchen(id) {
        if (!confirm('Are you sure you want to delete this kitchen?')) return;
        
        showLoading();
        fetch(`delete_kitchen.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Kitchen deleted', 'success');
                    loadKitchens();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Failed to delete kitchen', 'error');
            });
    }

    // ==================== CATEGORY FUNCTIONS ====================
    function loadCategories() {
        fetch('get_categories.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('categories-list');
                if (data.error) {
                    container.innerHTML = `<p class="error">${data.error}</p>`;
                    return;
                }
                
                let html = '<table class="table">';
                html += '<tr><th>Name</th><th>Kitchen</th><th>Sort</th><th>Actions</th></tr>';
                
                data.forEach(category => {
                    html += `<tr>
                        <td>${category.name}</td>
                        <td>${category.kitchen_name || 'Not assigned'}</td>
                        <td>${category.sort_order || 0}</td>
                        <td>
                            <button class="btn btn-primary btn-small" onclick="editCategory(${category.id})">Edit</button>
                            <button class="btn btn-danger btn-small" onclick="deleteCategory(${category.id})">Delete</button>
                        </td>
                    </tr>`;
                });
                
                html += '</table>';
                container.innerHTML = html;
                
                // Update item category dropdown
                updateItemCategoryDropdown(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load categories', 'error');
            });
    }

    function updateItemCategoryDropdown(categories) {
        const select = document.getElementById('item_category_id');
        if (select) {
            select.innerHTML = '<option value="">Select Category</option>';
            categories.forEach(category => {
                select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
            });
        }
    }

    function editCategory(id) {
    console.log('Editing category ID:', id); // Debug log
    
    // Show loading in the modal
    document.getElementById('categoryModalTitle').textContent = 'Loading...';
    openModal('category');
    
    fetch(`get_category.php?id=${id}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(category => {
            console.log('Category data received:', category); // Debug log
            
            // Populate the form fields
            document.getElementById('category_id').value = category.id || '';
            document.getElementById('category_name').value = category.name || '';
            document.getElementById('category_kitchen_id').value = category.kitchen_id || '';
            document.getElementById('category_sort_order').value = category.sort_order || 0;
            document.getElementById('category_description').value = category.description || '';
            
            // Update modal title
            document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        })
        .catch(error => {
            console.error('Error loading category:', error);
            alert('Failed to load category details. Check console for details.');
            closeModal('category');
        });
}

    function saveCategory(event) {
        event.preventDefault();
        
        const formData = {
            id: document.getElementById('category_id').value,
            name: document.getElementById('category_name').value,
            kitchen_id: document.getElementById('category_kitchen_id').value,
            sort_order: document.getElementById('category_sort_order').value,
            description: document.getElementById('category_description').value
        };

        showLoading();

        fetch('save_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('Category saved successfully', 'success');
                closeModal('category');
                loadCategories();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to save category', 'error');
        });
    }

    function deleteCategory(id) {
        if (!confirm('Are you sure you want to delete this category?')) return;
        
        showLoading();
        fetch(`delete_category.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Category deleted', 'success');
                    loadCategories();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Failed to delete category', 'error');
            });
    }

    // ==================== MENU ITEM FUNCTIONS ====================
    function loadMenuItems() {
        fetch('get_menu_items_admin.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('items-list');
                if (data.error) {
                    container.innerHTML = `<p class="error">${data.error}</p>`;
                    return;
                }
                
                let html = '<table class="table">';
                html += '<tr><th>Name</th><th>Category</th><th>Price</th><th>Prep Time</th><th>Actions</th></tr>';
                
                data.forEach(item => {
                    html += `<tr>
                        <td>${item.name}</td>
                        <td>${item.category_name || 'N/A'}</td>
                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                        <td>${item.preparation_time || 0} min</td>
                        <td>
                            <button class="btn btn-primary btn-small" onclick="editItem(${item.id})">Edit</button>
                            <button class="btn btn-danger btn-small" onclick="deleteItem(${item.id})">Delete</button>
                        </td>
                    </tr>`;
                });
                
                html += '</table>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load menu items', 'error');
            });
    }

    function editItem(id) {
    console.log('Editing item ID:', id); // Debug log
    
    // Show loading in the modal
    document.getElementById('itemModalTitle').textContent = 'Loading...';
    openModal('item');
    
    fetch(`get_menu_item.php?id=${id}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(item => {
            console.log('Item data received:', item); // Debug log
            
            // Populate the form fields
            document.getElementById('item_id').value = item.id || '';
            document.getElementById('item_name').value = item.name || '';
            document.getElementById('item_category_id').value = item.category_id || '';
            document.getElementById('item_price').value = item.price || '';
            document.getElementById('item_description').value = item.description || '';
            document.getElementById('preparation_time').value = item.preparation_time || 10;
            document.getElementById('item_sort_order').value = item.sort_order || 0;
            
            // Update modal title
            document.getElementById('itemModalTitle').textContent = 'Edit Menu Item';
        })
        .catch(error => {
            console.error('Error loading item:', error);
            alert('Failed to load item details. Check console for details.');
            closeModal('item');
        });
}

    function saveItem(event) {
        event.preventDefault();
        
        const formData = {
            id: document.getElementById('item_id').value,
            name: document.getElementById('item_name').value,
            category_id: document.getElementById('item_category_id').value,
            price: document.getElementById('item_price').value,
            description: document.getElementById('item_description').value,
            preparation_time: document.getElementById('preparation_time').value,
            sort_order: document.getElementById('item_sort_order').value
        };

        showLoading();

        fetch('save_menu_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('Menu item saved successfully', 'success');
                closeModal('item');
                loadMenuItems();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('Failed to save menu item', 'error');
        });
    }

    function deleteItem(id) {
        if (!confirm('Are you sure you want to delete this menu item?')) return;
        
        showLoading();
        fetch(`delete_menu_item.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Menu item deleted', 'success');
                    loadMenuItems();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Failed to delete menu item', 'error');
            });
    }

    // ==================== PRINTER FUNCTIONS ====================
    function loadPrinterStatus() {
        fetch('get_printer_status.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('printers-status');
                if (data.error) {
                    container.innerHTML = `<p class="error">${data.error}</p>`;
                    return;
                }
                
                let html = '<table class="table">';
                html += '<tr><th>Kitchen</th><th>Printer IP</th><th>Status</th><th>Last Checked</th></tr>';
                
                data.forEach(printer => {
                    html += `<tr>
                        <td>${printer.kitchen_name}</td>
                        <td>${printer.printer_ip || 'Not configured'}</td>
                        <td><span class="status-badge status-${printer.status || 'offline'}">${printer.status || 'offline'}</span></td>
                        <td>${printer.last_checked ? new Date(printer.last_checked).toLocaleString() : 'Never'}</td>
                    </tr>`;
                });
                
                html += '</table>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to load printer status', 'error');
            });
    }

    function testPrinter(kitchenId) {
        showLoading();
        fetch(`test_printer.php?kitchen_id=${kitchenId}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(`Printer test ${data.status}: ${data.message}`, data.status === 'online' ? 'success' : 'warning');
                } else {
                    showNotification('Printer test failed: ' + data.message, 'error');
                }
                loadPrinterStatus();
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Failed to test printer', 'error');
            });
    }

    function testAllPrinters() {
        showLoading();
        fetch('test_all_printers.php')
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(`Tested ${data.count} printers. ${data.online} online, ${data.offline} offline`, 'info');
                } else {
                    showNotification('Failed to test printers', 'error');
                }
                loadPrinterStatus();
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Failed to test printers', 'error');
            });
    }

    // ==================== MODAL FUNCTIONS ====================
    function openModal(type) {
    if (type === 'kitchen') {
        // Reset form but keep any existing values from edit
        document.getElementById('kitchenForm').reset();
        document.getElementById('kitchenModal').style.display = 'flex';
    } else if (type === 'category') {
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryModal').style.display = 'flex';
    } else if (type === 'item') {
        document.getElementById('itemForm').reset();
        document.getElementById('itemModal').style.display = 'flex';
    }
}

    function closeModal(type) {
        if (type === 'kitchen') {
            document.getElementById('kitchenModal').style.display = 'none';
        } else if (type === 'category') {
            document.getElementById('categoryModal').style.display = 'none';
        } else if (type === 'item') {
            document.getElementById('itemModal').style.display = 'none';
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };

    // ==================== UTILITY FUNCTIONS ====================
    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loading').style.display = 'none';
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
    </script>
</body>
</html>