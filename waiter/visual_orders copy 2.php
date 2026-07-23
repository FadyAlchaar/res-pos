<?php
require_once '../config/config.php';
require_once '../config/language.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get categories with kitchen info and icons
    $query = "SELECT c.*, k.id as kitchen_id, k.name as kitchen_name,
                    (SELECT COUNT(*) FROM menu_items mi WHERE mi.category_id = c.id AND mi.is_available = 1) as item_count
            FROM categories c
            LEFT JOIN kitchens k ON c.kitchen_id = k.id
            WHERE c.is_active = 1
            ORDER BY c.sort_order";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Get active tables
    $query = "SELECT * FROM restaurant_tables 
              WHERE is_active = 1 
              ORDER BY CAST(table_number AS UNSIGNED), sort_order";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
    // Check for active sessions
    $active_sessions = [];
    $session_query = "SELECT table_id FROM table_sessions WHERE status = 'open'";
    $session_stmt = $db->query($session_query);
    while ($row = $session_stmt->fetch()) {
        $active_sessions[] = $row['table_id'];
    }
    
} catch (Exception $e) {
    $error = "Failed to load menu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo t('waiter_terminal'); ?> - Visual Mode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 80px;
        }
        
        .wizard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 15px 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .logo h4 {
            font-size: 1.3rem;
            color: #2c3e50;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .waiter-badge {
            background: #3498db;
            color: white;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 0.8rem;
        }
        
        .lang-switch {
            display: flex;
            gap: 5px;
        }
        
        .lang-btn {
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.7rem;
            background: #e9ecef;
            color: #2c3e50;
        }
        
        .lang-btn.active {
            background: #3498db;
            color: white;
        }
        
        .logout {
            color: #e74c3c;
            text-decoration: none;
            font-size: 0.8rem;
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.9);
            border-radius: 50px;
            padding: 10px 20px;
            backdrop-filter: blur(10px);
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
            cursor: pointer;
        }
        
        .step-number {
            width: 35px;
            height: 35px;
            background: #e9ecef;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background: #3498db;
            color: white;
            box-shadow: 0 0 0 5px rgba(52,152,219,0.2);
        }
        
        .step.completed .step-number {
            background: #27ae60;
            color: white;
        }
        
        .step-label {
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        .step.active .step-label {
            color: #3498db;
            font-weight: bold;
        }
        
        .content-card {
            background: white;
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            flex: 1;
        }
        
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .table-card-select {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            position: relative;
        }
        
        .table-card-select:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .table-card-select.selected {
            border-color: #27ae60;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(39,174,96,0.2);
        }
        
        .table-number-large {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .table-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .badge-open {
            background: #27ae60;
            color: white;
        }
        
        .badge-closed {
            background: #95a5a6;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
        }
        
        .btn-sm {
            display: block;
            width: 100%;
            padding: 8px 6px;
            font-size: 0.75rem;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 500;
        }        

        .btn-sm:hover {
            transform: scale(1.02);
        }
        
        .btn-open {
            background: #27ae60;
            color: white;
        }
        
        .btn-close {
            background: #e74c3c;
            color: white;
        }

        .btn-order {
            background: #3498db;
            color: white;
        }

        .btn-info {
            background: #6c757d;
            color: white;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .category-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border-radius: 20px;
            padding: 25px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .category-name {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .category-count {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .items-grid-visual {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .item-card-visual {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid transparent;
        }
        
        .item-card-visual:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .item-image {
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        .item-image.placeholder-1 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .item-image.placeholder-2 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .item-image.placeholder-3 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .item-image.placeholder-4 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .item-details {
            padding: 15px;
            text-align: center;
        }
        
        .item-name-visual {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .item-price-visual {
            font-size: 1.1rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .item-kitchen-visual {
            font-size: 0.65rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .quantity-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .quantity-modal-content {
            background: white;
            border-radius: 30px;
            padding: 30px;
            width: 90%;
            max-width: 350px;
            text-align: center;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .qty-modal-btn {
            width: 50px;
            height: 50px;
            border: none;
            background: #3498db;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .qty-modal-value {
            font-size: 2rem;
            font-weight: bold;
            min-width: 60px;
        }
        
        .notes-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 15px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-add { background: #27ae60; color: white; }
        .btn-cancel { background: #e74c3c; color: white; }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .order-items-summary {
            max-height: 250px;
            overflow-y: auto;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 2px solid #dee2e6;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        
        .nav-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .nav-btn-primary {
            background: #27ae60;
            color: white;
        }
        
        .nav-btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 50px;
            color: white;
            z-index: 1100;
            font-size: 0.85rem;
            animation: fadeUp 0.3s;
        }
        
        .toast-success { background: #27ae60; }
        .toast-error { background: #e74c3c; }
        .toast-warning { background: #f39c12; }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        .floating-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
            z-index: 1000;
        }
        
        .floating-btn {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: none;
        }
        
        .floating-btn.send {
            background: linear-gradient(135deg, #27ae60 0%, #219a52 100%);
            color: white;
        }
        
        .floating-btn.cancel {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            display: none;
        }
        
        .floating-btn:hover {
            transform: scale(1.05);
        }
        
        .floating-btn:active {
            transform: scale(0.95);
        }
        
        .floating-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        body[dir="rtl"] .floating-actions {
            right: auto;
            left: 20px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        .loading-spinner {
            background: white;
            padding: 25px 35px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .wizard-container {
                padding: 10px;
                padding-bottom: 80px;
            }
            
            .items-grid-visual {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .category-card {
                padding: 15px 10px;
            }
            
            .category-icon {
                font-size: 2rem;
            }
            
            .floating-actions {
                bottom: 15px;
                right: 15px;
            }
            
            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .table-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .table-card-select {
                padding: 12px 8px;
            }
            
            .table-number-large {
                font-size: 1.1rem;
            }
        }
        
        .search-container {
            margin-bottom: 20px;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }

        .item-search-input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 1rem;
            background: white;
            transition: all 0.2s;
        }

        .item-search-input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        .item-search-input::placeholder {
            color: #adb5bd;
        }

        .search-clear-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #adb5bd;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .search-clear-btn:hover {
            background: #e9ecef;
            color: #e74c3c;
        }

        .search-clear-btn:active {
            transform: translateY(-50%) scale(0.95);
        }

        .qty-control-mini {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f8f9fa;
            border-radius: 30px;
            padding: 2px 4px;
            border: 1px solid #e9ecef;
        }
        .qty-btn-mini {
            width: 24px;
            height: 24px;
            border: none;
            background: white;
            color: #2c3e50;
            font-weight: bold;
            font-size: 14px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn-mini:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }
        .qty-btn-mini:active {
            transform: scale(0.95);
        }
        .qty-value-mini {
            min-width: 24px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            color: #2c3e50;
        }
        .remove-item-mini {
            width: 28px;
            height: 28px;
            border: none;
            background: #fee2e2;
            color: #e74c3c;
            font-size: 14px;
            font-weight: bold;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .remove-item-mini:hover {
            background: #e74c3c;
            color: white;
            transform: scale(1.05);
        }
        .remove-item-mini:active {
            transform: scale(0.95);
        }
        .item-notes-mini {
            margin-top: 4px;
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            font-size: 11px;
            background: #f8f9fa;
            color: #6c757d;
        }
        .item-notes-mini:focus {
            outline: none;
            border-color: #3498db;
            background: white;
        }

        .cancelled-item-row {
            text-decoration: line-through;
            opacity: 0.6;
            background-color: #f8f9fa;
        }
        .cancelled-badge {
            color: #6c757d;
            font-size: 0.7rem;
            margin-left: 5px;
        }

        .btn-parking-send {
            background: #5dade2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-parking-send:hover {
            background: #3498db;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="header">
            <div class="logo">
                <h4><?php echo t('waiter_terminal'); ?></h4>
            </div>
            <div class="header-right">
                <div class="lang-switch">
                    <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                    <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
                </div>
                <span class="waiter-badge">👨‍🍳 <?php echo $_SESSION['full_name']; ?></span>
                <a href="../logout.php" class="logout">🚪</a>
            </div>
        </div>

        <div class="progress-steps">
            <div class="step" data-step="1" id="step1-indicator">
                <div class="step-number">1</div>
                <div class="step-label"><?php echo t('select_table'); ?></div>
            </div>
            <div class="step" data-step="2" id="step2-indicator">
                <div class="step-number">2</div>
                <div class="step-label"><?php echo t('select_category'); ?></div>
            </div>
            <div class="step" data-step="3" id="step3-indicator">
                <div class="step-number">3</div>
                <div class="step-label"><?php echo t('select_items'); ?></div>
            </div>
            <div class="step" data-step="4" id="step4-indicator">
                <div class="step-number">4</div>
                <div class="step-label"><?php echo t('review_order'); ?></div>
            </div>
        </div>

        <div id="step1" class="step-content active">
            <div class="content-card">
                <h2><?php echo t('select_table'); ?></h2>
                <div class="table-grid" id="table-grid">
                    <?php foreach ($tables as $table): 
                        $hasSession = in_array($table['id'], $active_sessions);
                        $sessionStatus = $hasSession ? 'open' : 'closed';
                    ?>
                    <div class="table-card-select" 
                        data-table-id="<?php echo $table['id']; ?>" 
                        data-table-name="<?php echo htmlspecialchars($table['table_name'] ?: 'Table ' . $table['table_number']); ?>"
                        data-session-status="<?php echo $sessionStatus; ?>"
                        data-capacity="<?php echo $table['capacity']; ?>">
                        <div class="table-number-large"><?php echo $table['table_name'] ?: 'Table ' . $table['table_number']; ?></div>
                        <?php if ($hasSession): ?>
                            <div class="table-badge badge-open">🟢 <?php echo t('session_open'); ?></div>
                        <?php endif; ?>
                        <div class="action-buttons">
                            <?php if ($hasSession): ?>
                                <button class="btn-sm btn-order" onclick="event.stopPropagation(); openTableForOrder(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_name'] ?: 'Table ' . $table['table_number']); ?>')"><?php echo t('add_order'); ?></button>
                                <button class="btn-sm btn-close" onclick="event.stopPropagation(); closeTableSession(<?php echo $table['id']; ?>)"><?php echo t('close_bill'); ?></button>
                                <button class="btn-sm btn-info" onclick="event.stopPropagation(); showSessionOrders(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_name'] ?: 'Table ' . $table['table_number']); ?>')"><?php echo t('manage_orders'); ?></button>
                            <?php else: ?>
                                <button class="btn-sm btn-open" onclick="event.stopPropagation(); openTableSession(<?php echo $table['id']; ?>, <?php echo $table['capacity']; ?>)">🟢 <?php echo t('open_table'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="step2" class="step-content">
            <div class="content-card">
                <h2>📁 <?php echo t('select_category'); ?></h2>
                <div class="category-grid" id="category-grid">
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-card" 
                         data-category-id="<?php echo $cat['id']; ?>" 
                         data-category-name="<?php echo htmlspecialchars($cat['name']); ?>" 
                         data-kitchen-id="<?php echo $cat['kitchen_id']; ?>" 
                         data-kitchen-name="<?php echo htmlspecialchars($cat['kitchen_name']); ?>">
                        <div class="category-icon"><?php echo $cat['icon'] ?: '🍽️'; ?></div>
                        <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                        <div class="category-count"><?php echo $cat['item_count']; ?> <?php echo t('items'); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="nav-buttons">
                    <button class="nav-btn nav-btn-secondary" id="back-step2">← <?php echo t('back'); ?></button>
                </div>
            </div>
        </div>

        <div id="step3" class="step-content">
            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <h2>🍕 <?php echo t('select_items'); ?></h2>
                    <span id="selected-category-name" style="background:#e9ecef; padding:5px 15px; border-radius:20px; font-size:0.85rem;"></span>
                </div>
                
                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" id="item-search" class="item-search-input" placeholder="🔍 <?php echo t('search_items'); ?>..." onkeyup="filterItems()">
                        <button class="search-clear-btn" id="search-clear-btn" onclick="clearSearch()" style="display: none;">✕</button>
                    </div>
                </div>                
                <div id="items-grid-container" class="items-grid-visual">
                    <div style="grid-column:1/-1; text-align:center; padding:40px;"><?php echo t('select_category_first'); ?></div>
                </div>
                
                <div class="order-summary" id="order-summary">
                    <h3>🛒 <?php echo t('current_order'); ?></h3>
                    <div id="order-items-summary" class="order-items-summary">
                        <p style="color:#6c757d; text-align:center;"><?php echo t('no_items'); ?></p>
                    </div>
                    <div class="summary-total">
                        <span><?php echo t('total'); ?>:</span>
                        <span id="summary-total-amount">$0.00</span>
                    </div>
                </div>
                
                <div class="nav-buttons">
                    <button class="nav-btn nav-btn-secondary" id="back-step3">← <?php echo t('back'); ?></button>
                </div>
            </div>
        </div>
        
        <div id="step4" class="step-content">
            <div class="content-card">
                <h2>📋 <?php echo t('review_order'); ?></h2>
                <div id="final-order-summary" style="margin: 20px 0;">
                    <div style="background:#f8f9fa; border-radius:20px; padding:20px;">
                        <p style="color:#6c757d; text-align:center;"><?php echo t('no_order_to_review'); ?></p>
                    </div>
                </div>
                <div class="nav-buttons">
                    <button class="nav-btn nav-btn-secondary" id="back-step4">← <?php echo t('back'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <div id="quantity-modal" class="quantity-modal">
        <div class="quantity-modal-content">
            <h3 id="modal-item-name">Item Name</h3>
            <div class="quantity-controls">
                <button class="qty-modal-btn" id="modal-qty-minus">-</button>
                <span id="modal-qty-value" class="qty-modal-value">1</span>
                <button class="qty-modal-btn" id="modal-qty-plus">+</button>
            </div>
            <textarea id="modal-notes" class="notes-input" placeholder="<?php echo t('special_instructions'); ?>" rows="2"></textarea>
            <div class="modal-buttons">
                <button class="btn-add" id="modal-add">➕ <?php echo t('add_to_order'); ?></button>
                <button class="btn-cancel" id="modal-cancel">✖ <?php echo t('cancel'); ?></button>
            </div>
        </div>
    </div>

    <!-- Session Orders Modal -->
    <div id="session-orders-modal" class="quantity-modal">
        <div class="quantity-modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <h3 id="session-orders-title"><?php echo t('manage_orders'); ?></h3>
            <div id="session-orders-list" style="text-align: left; margin-top: 15px;">
                <?php echo t('loading'); ?>
            </div>
            <div class="modal-buttons" style="margin-top: 20px; display: flex; justify-content: space-between;">
                <button class="btn-cancel" onclick="closeSessionOrdersModal()"><?php echo t('close'); ?></button>
                <button class="btn-sm btn-info" onclick="openParkingModal()"><?php echo t('parking_request'); ?>
                </div>
            </div>
        </div>
    </div>
    
        <!-- Parking Request Modal -->
    <div id="parking-modal" class="quantity-modal">
        <div class="quantity-modal-content" style="max-width: 400px;">
            <h3 id="parking-modal-title"><?php echo t('parking_request'); ?></h3>
            <div class="form-group">
                <label><?php echo t('parking_lot_numbers'); ?></label>
                <input type="text" id="parking-lot-numbers" class="form-control" placeholder="e.g., 14, 15, 16">
                <!-- <small><?php echo t('parking_numbers_hint'); ?></small> -->
            </div>
            <div class="modal-buttons" style="margin-top: 20px;">
                <button class="btn-parking-send" onclick="sendParkingRequest()"><?php echo t('send'); ?></button>
                <button class="btn-cancel" onclick="closeParkingModal()"><?php echo t('cancel'); ?></button>
            </div>
        </div>
    </div>


    <div class="floating-actions">
        <button class="floating-btn cancel" id="floating-cancel-btn" title="<?php echo t('cancel_order'); ?>">
            🗑️
            <span class="floating-badge" id="cancel-badge" style="display:none;">0</span>
        </button>
        <button class="floating-btn send" id="floating-send-btn" title="<?php echo t('send_to_kitchen'); ?>">
            🚀
            <span class="floating-badge" id="order-badge" style="display:none;">0</span>
        </button>
    </div>

    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">⏳ <?php echo t('sending_order'); ?>...</div>
    </div>

    <script>
    // ============================================
    // GLOBAL VARIABLES
    // ============================================
    let currentStep = 1;
    let selectedTableId = null;
    let selectedTableName = null;
    let selectedCategoryId = null;
    let currentOrder = [];
    let currentItem = null;
    let categories = <?php echo json_encode($categories); ?>;
    let tables = <?php echo json_encode($tables); ?>;
    let allItems = [];
    let currentKitchenId = null;
    let currentKitchenName = null;
    var currencySymbol = '<?php echo t('currency'); ?>';
    var isRtl = <?php echo json_encode(get_dir() === 'rtl'); ?>;

    function formatPrice(amount) {
        var formatted = amount.toFixed(2);
        if (isRtl) {
            return formatted + ' ' + currencySymbol;
        } else {
            return currencySymbol + formatted;
        }
    }

    console.log('Categories loaded:', categories);

    // ============================================
    // SESSION FUNCTIONS
    // ============================================

    function openTableSession(tableId, capacity) {
        let customerCount = prompt('How many customers?', '2');
        if (!customerCount) return;
        
        showLoading();
        fetch('api/open_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                table_id: tableId, 
                customer_count: parseInt(customerCount) 
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('✅ Table opened! Session: ' + data.session_number, 'success');
                location.reload();
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('❌ Failed to open table', 'error');
        });
    }

    function closeTableSession(tableId) {
        var printBill = confirm('<?php echo t('print_bill_confirm'); ?>');
        var currentLang = '<?php echo $lang; ?>';
        showLoading();
        fetch('api/close_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                table_id: tableId, 
                lang: currentLang,
                print_bill: printBill 
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('💰 ' + data.message, 'success');
                location.reload();
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('<?php echo t('error'); ?>', 'error');
        });
    }

    function doClose(tableId, shouldPrint) {
        var currentLang = '<?php echo $lang; ?>';
        showLoading();
        fetch('api/close_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_id: tableId, lang: currentLang, print: shouldPrint })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('💰 Table closed. Total: ' + currencySymbol + parseFloat(data.total).toFixed(2), 'success');
                location.reload();
            } else {
                showToast('❌ Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('❌ Failed to close table', 'error');
        });
    }

    function openTableForOrder(tableId, tableName) {
        selectedTableId = tableId;
        selectedTableName = tableName;
        updateFloatingButtons();
        showStep(2);
        showToast('Table ' + tableName + ' opened for ordering', 'success');
    }

    // ============================================
    // STEP NAVIGATION
    // ============================================
    function showStep(step) {
        document.querySelectorAll('.step-content').forEach(function(el) {
            el.classList.remove('active');
        });
        document.getElementById('step' + step).classList.add('active');
        
        document.querySelectorAll('.step').forEach(function(el, index) {
            if (index + 1 < step) {
                el.classList.add('completed');
            } else {
                el.classList.remove('completed');
            }
            if (index + 1 === step) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
        
        currentStep = step;
        
        if (step === 4) {
            renderFinalOrder();
        }
    }

    document.querySelectorAll('.step').forEach(function(step) {
        step.addEventListener('click', function() {
            var targetStep = parseInt(this.dataset.step);
            if (targetStep <= currentStep) {
                showStep(targetStep);
            }
        });
    });

    // ============================================
    // TABLE SELECTION (for existing sessions)
    // ============================================

    // Make entire table card clickable
    document.querySelectorAll('.table-card-select').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn-sm')) return;
            
            var tableId = parseInt(this.dataset.tableId);
            var tableName = this.dataset.tableName;
            var sessionStatus = this.dataset.sessionStatus;
            var capacity = parseInt(this.dataset.capacity);
            
            if (sessionStatus === 'open') {
                openTableForOrder(tableId, tableName);
            } else {
                openTableSession(tableId, capacity);
            }
        });
    });

    // ============================================
    // CATEGORY SELECTION
    // ============================================
    document.getElementById('category-grid').addEventListener('click', function(e) {
        var card = e.target.closest('.category-card');
        if (!card) return;
        
        document.querySelectorAll('.category-card').forEach(function(c) {
            c.classList.remove('selected');
        });
        card.classList.add('selected');
        selectedCategoryId = card.dataset.categoryId;
        var categoryName = card.dataset.categoryName;
        var kitchenId = parseInt(card.dataset.kitchenId);
        var kitchenName = card.dataset.kitchenName;
        
        document.getElementById('selected-category-name').textContent = categoryName;
        
        console.log('Selected category:', {id: selectedCategoryId, name: categoryName, kitchen_id: kitchenId, kitchen_name: kitchenName});
        
        loadItems(selectedCategoryId, kitchenId, kitchenName);
        showStep(3);
    });

    // ============================================
    // LOAD ITEMS
    // ============================================
    function loadItems(categoryId, kitchenId, kitchenName) {
        currentKitchenId = kitchenId;
        currentKitchenName = kitchenName;
        
        console.log('Loading items for category:', categoryId, 'kitchen:', kitchenId, kitchenName);
        
        var searchInput = document.getElementById('item-search');
        var clearBtn = document.getElementById('search-clear-btn');
        if (searchInput) {
            searchInput.value = '';
            clearBtn.style.display = 'none';
        }
        
        fetch('ajax_get_items.php?category_id=' + categoryId + '&t=' + Date.now())
            .then(function(r) {
                return r.json();
            })
            .then(function(items) {
                allItems = items;
                displayItems(allItems);
            });
    }

    function displayItems(items) {
        var container = document.getElementById('items-grid-container');
        
        if (!items.length) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; padding:40px;">📭 <?php echo t('no_items'); ?></p>';
            return;
        }
        
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var placeholderClass = 'placeholder-' + ((i % 4) + 1);
            html += '<div class="item-card-visual" data-item-id="' + item.id + '" data-item-name="' + item.name.replace(/'/g, "\\'") + '" data-item-price="' + item.price + '" data-item-kitchen="' + currentKitchenId + '" data-item-kitchen-name="' + currentKitchenName + '">';
            html += '<div class="item-image ' + placeholderClass + '">🍽️</div>';
            html += '<div class="item-details">';
            html += '<div class="item-name-visual">' + item.name + '</div>';
            html += '<div class="item-price-visual">' + formatPrice(parseFloat(item.price)) + '</div>';
            html += '<div class="item-kitchen-visual">' + currentKitchenName + '</div>';
            html += '</div></div>';
        }
        container.innerHTML = html;
        
        document.querySelectorAll('.item-card-visual').forEach(function(card) {
            card.addEventListener('click', function() {
                currentItem = {
                    id: parseInt(this.dataset.itemId),
                    name: this.dataset.itemName,
                    price: parseFloat(this.dataset.itemPrice),
                    kitchen_id: parseInt(this.dataset.itemKitchen),
                    kitchen_name: this.dataset.itemKitchenName
                };
                console.log('Selected item:', currentItem);
                document.getElementById('modal-item-name').textContent = currentItem.name;
                document.getElementById('modal-qty-value').textContent = '1';
                document.getElementById('modal-notes').value = '';
                document.getElementById('quantity-modal').style.display = 'flex';
            });
        });
    }

    // ============================================
    // SEARCH FUNCTIONALITY
    // ============================================
    function filterItems() {
        var searchTerm = document.getElementById('item-search').value.toLowerCase().trim();
        var clearBtn = document.getElementById('search-clear-btn');
        
        if (searchTerm !== '') {
            clearBtn.style.display = 'flex';
        } else {
            clearBtn.style.display = 'none';
        }
        
        if (searchTerm === '') {
            displayItems(allItems);
            return;
        }
        
        var filteredItems = allItems.filter(function(item) {
            return item.name.toLowerCase().includes(searchTerm);
        });
        
        displayItems(filteredItems);
        
        var container = document.getElementById('items-grid-container');
        if (filteredItems.length === 0) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; padding:40px; color:#e74c3c;">🔍 <?php echo t('no_items_found'); ?></p>';
        }
    }

    function clearSearch() {
        var searchInput = document.getElementById('item-search');
        var clearBtn = document.getElementById('search-clear-btn');
        
        searchInput.value = '';
        clearBtn.style.display = 'none';
        displayItems(allItems);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var searchInput = document.getElementById('item-search');
            if (searchInput && document.activeElement === searchInput) {
                clearSearch();
            }
        }
    });

    // ============================================
    // QUANTITY MODAL
    // ============================================
    document.getElementById('modal-qty-minus').addEventListener('click', function() {
        var qty = parseInt(document.getElementById('modal-qty-value').textContent);
        if (qty > 1) {
            document.getElementById('modal-qty-value').textContent = qty - 1;
        }
    });

    document.getElementById('modal-qty-plus').addEventListener('click', function() {
        var qty = parseInt(document.getElementById('modal-qty-value').textContent);
        document.getElementById('modal-qty-value').textContent = qty + 1;
    });

    document.getElementById('modal-add').addEventListener('click', function() {
        var qty = parseInt(document.getElementById('modal-qty-value').textContent);
        var notes = document.getElementById('modal-notes').value;
        
        var existingIndex = -1;
        for (var i = 0; i < currentOrder.length; i++) {
            if (currentOrder[i].id === currentItem.id) {
                existingIndex = i;
                break;
            }
        }
        
        if (existingIndex !== -1) {
            currentOrder[existingIndex].qty += qty;
            currentOrder[existingIndex].subtotal = currentOrder[existingIndex].qty * currentOrder[existingIndex].price;
            if (notes) currentOrder[existingIndex].notes = notes;
        } else {
            currentOrder.push({
                id: currentItem.id,
                name: currentItem.name,
                price: currentItem.price,
                kitchen_id: currentItem.kitchen_id,
                kitchen_name: currentItem.kitchen_name,
                qty: qty,
                notes: notes,
                subtotal: currentItem.price * qty
            });
        }
        
        updateOrderSummary();
        document.getElementById('quantity-modal').style.display = 'none';
        showToast('✅ ' + currentItem.name + ' <?php echo t('added'); ?>', 'success');
    });

    document.getElementById('modal-cancel').addEventListener('click', function() {
        document.getElementById('quantity-modal').style.display = 'none';
    });

    // ============================================
    // ORDER SUMMARY
    // ============================================
    function updateOrderSummary() {
        var container = document.getElementById('order-items-summary');
        var total = 0;
        
        if (currentOrder.length === 0) {
            container.innerHTML = '<p style="color:#6c757d; text-align:center;"><?php echo t('no_items'); ?></p>';
            document.getElementById('summary-total-amount').textContent = formatPrice(0);
            updateFloatingButtons();
            return;
        }
        
        var html = '';
        for (var i = 0; i < currentOrder.length; i++) {
            var item = currentOrder[i];
            html += '<div class="summary-item" data-item-index="' + i + '">';
            html += '<div style="flex:1;">';
            html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
            html += '<div>';
            html += '<strong>' + item.qty + 'x ' + item.name + '</strong>';
            html += '<br><small>🏠 ' + item.kitchen_name + '</small>';
            html += '</div>';
            html += '<div style="display:flex; gap:8px; align-items:center;">';
            html += '<div class="qty-control-mini">';
            html += '<button class="qty-btn-mini" onclick="updateItemQty(' + i + ', -1)" ' + (item.qty <= 1 ? 'disabled' : '') + '>−</button>';
            html += '<span class="qty-value-mini">' + item.qty + '</span>';
            html += '<button class="qty-btn-mini" onclick="updateItemQty(' + i + ', 1)">+</button>';
            html += '</div>';
            html += '<button class="remove-item-mini" onclick="removeOrderItem(' + i + ')">✕</button>';
            html += '</div>';
            html += '</div>';
            html += '<input type="text" class="item-notes-mini" placeholder="📝 <?php echo t('special_instructions'); ?>" value="' + (item.notes || '') + '" onchange="updateItemNotes(' + i + ', this.value)">';
            html += '</div>';
            html += '<div style="color:#27ae60; font-weight:bold;">' + formatPrice(item.subtotal) + '</div>';
            html += '</div>';
            total += item.subtotal;
        }
        
        container.innerHTML = html;
        document.getElementById('summary-total-amount').textContent = formatPrice(total);
        updateFloatingButtons();
    }

    function updateItemQty(index, delta) {
        var item = currentOrder[index];
        var newQty = item.qty + delta;
        if (newQty < 1) return;
        
        item.qty = newQty;
        item.subtotal = item.qty * item.price;
        updateOrderSummary();
        showToast(item.name + ' quantity updated to ' + newQty, 'success');
    }

    function removeOrderItem(index) {
        var item = currentOrder[index];
        if (confirm('Remove ' + item.name + ' from order?')) {
            currentOrder.splice(index, 1);
            updateOrderSummary();
            showToast(item.name + ' removed', 'warning');
        }
    }

    function updateItemNotes(index, notes) {
        currentOrder[index].notes = notes;
    }

    function updateFloatingButtons() {
        var sendBtn = document.getElementById('floating-send-btn');
        var cancelBtn = document.getElementById('floating-cancel-btn');
        var orderBadge = document.getElementById('order-badge');
        var cancelBadge = document.getElementById('cancel-badge');
        var itemCount = 0;
        for (var i = 0; i < currentOrder.length; i++) {
            itemCount += currentOrder[i].qty;
        }
        
        if (currentOrder.length > 0 && selectedTableId) {
            sendBtn.style.display = 'flex';
            cancelBtn.style.display = 'flex';
            if (itemCount > 0) {
                orderBadge.style.display = 'flex';
                orderBadge.textContent = itemCount;
                cancelBadge.style.display = 'flex';
                cancelBadge.textContent = currentOrder.length;
            }
        } else {
            sendBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            orderBadge.style.display = 'none';
            cancelBadge.style.display = 'none';
        }
    }

    // ============================================
    // REVIEW & SEND ORDER
    // ============================================
    function renderFinalOrder() {
        var container = document.getElementById('final-order-summary');
        if (currentOrder.length === 0) {
            container.innerHTML = '<div style="background:#f8f9fa; border-radius:20px; padding:20px;"><p style="color:#6c757d; text-align:center;"><?php echo t('no_order_to_review'); ?></p></div>';
            return;
        }
        
        var itemsHtml = '';
        var total = 0;
        
        for (var i = 0; i < currentOrder.length; i++) {
            var item = currentOrder[i];
            itemsHtml += '<div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #e9ecef;">';
            itemsHtml += '<div><strong>' + item.qty + 'x ' + item.name + '</strong>';
            if (item.notes) {
                itemsHtml += '<br><small style="color:#f39c12;">📝 ' + item.notes + '</small>';
            }
            itemsHtml += '<br><small>🏠 ' + item.kitchen_name + '</small>';
            itemsHtml += '</div>';
            itemsHtml += '<div>' + formatPrice(item.subtotal) + '</div>';
            itemsHtml += '</div>';
            total += item.subtotal;
        }
        
        container.innerHTML = '<div style="background:#f8f9fa; border-radius:20px; padding:20px;">' +
            '<p><strong><?php echo t('table'); ?>:</strong> ' + selectedTableName + '</p>' +
            itemsHtml +
            '<div style="margin-top:15px; padding-top:10px; border-top:2px solid #dee2e6; display:flex; justify-content:space-between; font-weight:bold;">' +
            '<span><?php echo t('total'); ?>:</span>' +
            '<span>' + formatPrice(total) + '</span>' +
            '</div></div>';
    }

    function cancelOrder() {
        if (currentOrder.length === 0) return;
        
        if (confirm('<?php echo t('confirm_cancel_order'); ?>')) {
            currentOrder = [];
            updateOrderSummary();
            renderFinalOrder();
            showToast('🗑️ <?php echo t('order_cancelled'); ?>', 'warning');
            updateFloatingButtons();
            
            if (currentStep === 4) {
                showStep(3);
            }
        }
    }

    function sendOrder() {
        if (currentOrder.length === 0) {
            showToast('No items to send', 'error');
            return;
        }
        
        if (!selectedTableId) {
            showToast('Please select a table first', 'error');
            showStep(1);
            return;
        }
        
        var total = 0;
        var itemCount = 0;
        for (var i = 0; i < currentOrder.length; i++) {
            total += currentOrder[i].subtotal;
            itemCount += currentOrder[i].qty;
        }
        
        if (confirm('Send order to kitchen?\n\nTable: ' + selectedTableName + '\n' + itemCount + ' items\n' + formatPrice(total))) {
            var orderData = {
                table_id: selectedTableId,
                table_number: selectedTableName,
                items: []
            };
            
            for (var i = 0; i < currentOrder.length; i++) {
                orderData.items.push({
                    id: currentOrder[i].id,
                    name: currentOrder[i].name,
                    quantity: currentOrder[i].qty,
                    notes: currentOrder[i].notes,
                    kitchen_id: currentOrder[i].kitchen_id,
                    price: currentOrder[i].price
                });
            }
            
            console.log('Sending order:', orderData);
            showLoading();
            
            fetch('ajax_submit_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
            .then(function(response) {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(function(data) {
                hideLoading();
                console.log('Order response:', data);
                if (data.success) {
                    showToast('✅ Order sent to kitchen!', 'success');
                    resetOrder();
                } else {
                    showToast('❌ ' + data.message, 'error');
                }
            })
            .catch(function(error) {
                hideLoading();
                console.error('Error:', error);
                showToast('❌ Failed to send order', 'error');
            });
        }
    }

    function resetOrder() {
        currentOrder = [];
        selectedTableId = null;
        selectedTableName = null;
        selectedCategoryId = null;
        updateOrderSummary();
        renderFinalOrder();
        updateFloatingButtons();
        
        document.querySelectorAll('.table-card-select').forEach(function(c) {
            c.classList.remove('selected');
        });
        document.querySelectorAll('.category-card').forEach(function(c) {
            c.classList.remove('selected');
        });
        
        showStep(1);
    }

    // ============================================
    // UI HELPERS
    // ============================================
    function showLoading() {
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loading-overlay').style.display = 'none';
    }

    function showToast(msg, type) {
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.remove();
        }, 2500);
    }

    // ============================================
    // NAVIGATION BUTTONS
    // ============================================
    document.getElementById('back-step2').addEventListener('click', function() { showStep(1); });
    document.getElementById('back-step3').addEventListener('click', function() { showStep(2); });
    document.getElementById('back-step4').addEventListener('click', function() { showStep(3); });

    document.getElementById('floating-send-btn').addEventListener('click', sendOrder);
    document.getElementById('floating-cancel-btn').addEventListener('click', cancelOrder);

    document.getElementById('quantity-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });

    // ============================================
// SESSION ORDERS (Cancel Items)
// ============================================

    let currentSessionId = null;

    function showSessionOrders(tableId, tableName) {
        // First, find the session ID for this table
        fetch('api/get_session_by_table.php?table_id=' + tableId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.session_id) {
                    currentSessionId = data.session_id;
                    document.getElementById('session-orders-title').innerHTML = '📋 ' + tableName + ' - <?php echo t('orders'); ?>';
                    loadSessionOrdersForModal(currentSessionId);
                    document.getElementById('session-orders-modal').style.display = 'flex';
                } else {
                    showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('<?php echo t('error'); ?>', 'error');
            });
    }

    function loadSessionOrdersForModal(sessionId) {
        const container = document.getElementById('session-orders-list');
        container.innerHTML = '<?php echo t('loading'); ?>';
        
        fetch('api/get_session_orders.php?session_id=' + sessionId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = '<div style="color: red;">' + data.error + '</div>';
                    return;
                }
                if (!data.orders || data.orders.length === 0) {
                    container.innerHTML = '<div><?php echo t('no_orders'); ?></div>';
                    return;
                }
                
                let html = '';
                data.orders.forEach(order => {
                    html += '<div style="border-bottom: 1px solid #e9ecef; margin-bottom: 10px; padding-bottom: 10px;">';
                    html += '<div><strong><?php echo t('order_number'); ?>: ' + order.order_number + '</strong> <small>' + new Date(order.created_at).toLocaleTimeString() + '</small></div>';
                    html += '<table style="width: 100%; margin-top: 5px;">';
                    (order.items || []).forEach(item => {
                        const isCancelled = item.cancelled == 1;
                        const rowClass = isCancelled ? 'cancelled-item-row' : '';
                        html += '<tr class="' + rowClass + '">';
                        html += '<td style="padding: 4px 0;">' + item.quantity + 'x ' + (item.item_name || '?') + '</td>';
                        html += '<td style="text-align: right;">';
                        if (!isCancelled) {
                            html += '<button class="btn-sm btn-danger" onclick="cancelOrderItem(' + item.id + ')"><?php echo t('cancel_item'); ?></button>';
                        } else {
                            html += '<span class="cancelled-badge"><?php echo t('cancelled'); ?></span>';
                        }
                        html += '</td></tr>';
                    });
                    html += '</table></div>';
                });
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = '<div style="color: red;"><?php echo t('error_loading'); ?></div>';
            });
    }

    function cancelOrderItem(orderItemId) {
        if (!confirm('<?php echo t('confirm_cancel_item'); ?>')) return;
        showLoading();
        fetch('api/cancel_order_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_item_id: orderItemId, lang: '<?php echo $lang; ?>' })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                // Refresh the session orders list
                if (currentSessionId) loadSessionOrdersForModal(currentSessionId);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('<?php echo t('error'); ?>', 'error');
        });
    }

    function closeSessionOrdersModal() {
        document.getElementById('session-orders-modal').style.display = 'none';
    }

    function printControllerTicket() {
        if (!currentSessionId) {
            showToast('<?php echo t('error'); ?>: No session selected', 'error');
            return;
        }
        showLoading();
        fetch('api/print_controller_ticket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: currentSessionId, lang: '<?php echo $lang; ?>' })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('<?php echo t('error'); ?>', 'error');
        });
    }

    let currentParkingSessionId = null;

    function openParkingModal() {
        if (currentSessionId) {
            currentParkingSessionId = currentSessionId;
            document.getElementById('parking-modal').style.display = 'flex';
            document.getElementById('parking-lot-numbers').value = '';
        } else {
            showToast('<?php echo t('error'); ?>: No session selected', 'error');
        }
    }

    function closeParkingModal() {
        document.getElementById('parking-modal').style.display = 'none';
    }

    function sendParkingRequest() {
        const lotNumbers = document.getElementById('parking-lot-numbers').value;
        if (!lotNumbers.trim()) {
            showToast('<?php echo t('error'); ?>: Please enter parking lot numbers', 'error');
            return;
        }

        showLoading();
        fetch('api/send_parking_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: currentParkingSessionId,
                lot_numbers: lotNumbers
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                closeParkingModal();
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showToast('<?php echo t('error'); ?>', 'error');
        });
    }
    </script>
</body>
</html>