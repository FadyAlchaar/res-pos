<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Include language file
require_once dirname(__DIR__, 2) . '/config/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo t('app_title'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            overflow: hidden;
        }
        
        /* Layout */
        .admin-layout {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a2c3e 0%, #0f1a24 100%);
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            flex-shrink: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #2d3e4e;
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header p {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 8px;
        }
        
        .nav-menu {
            flex: 1;
            padding: 0 12px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 4px;
            border-radius: 12px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active {
            background: #3498db;
            color: white;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }
        
        .nav-icon {
            font-size: 1.2rem;
            width: 28px;
            text-align: center;
        }
        
        .nav-label {
            flex: 1;
        }
        
        .nav-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #2d3e4e;
            margin-top: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-role {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(231, 76, 60, 0.2);
            border-radius: 10px;
            color: #e74c3c;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
        }
        
        .language-bar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 30px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .lang-btn {
            padding: 4px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .lang-btn.active {
            background: #3498db;
            color: white;
        }
        
        .page-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2c3e;
            margin-bottom: 4px;
        }
        
        .page-description {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .page-content {
            padding: 30px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2c3e;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 12px;
            font-size: 0.75rem;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        body[dir="rtl"] .data-table th {
            text-align: right;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 0.85rem;
        }
        
        .data-table tr:hover {
            background: #f8fafc;
        }
        
        /* Status Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fed7aa;
            color: #9b2c1d;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
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
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            color: #334155;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 20px;
            border-radius: 10px;
            color: white;
            z-index: 1100;
            animation: slideIn 0.3s;
        }
        
        body[dir="rtl"] .toast {
            right: auto;
            left: 30px;
        }
        
        .toast-success { background: #27ae60; }
        .toast-error { background: #e74c3c; }
        .toast-warning { background: #f39c12; }
        .toast-info { background: #3498db; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        body[dir="rtl"] @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #e2e8f0;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 3px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .nav-label {
                display: none;
            }
            
            .sidebar-header h2 span,
            .sidebar-header p,
            .user-details,
            .logout-btn span {
                display: none;
            }
            
            .sidebar-header h2 {
                justify-content: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .logout-btn {
                justify-content: center;
            }
            
            .nav-item {
                justify-content: center;
            }
            
            .page-header {
                padding: 15px 20px;
            }
            
            .page-content {
                padding: 20px;
            }
            
            .language-bar {
                padding: 6px 20px;
            }

            /* RTL Support - Table Alignment */
            body[dir="rtl"] .data-table {
                text-align: right;
            }

            body[dir="rtl"] .data-table th {
                text-align: right;
            }

            body[dir="rtl"] .data-table td {
                text-align: right;
            }

            /* For tables with numeric columns that should stay left-aligned in RTL */
            body[dir="rtl"] .data-table td.numeric,
            body[dir="rtl"] .data-table th.numeric {
                text-align: left;
            }

            /* Action buttons in RTL */
            body[dir="rtl"] .data-table td .btn,
            body[dir="rtl"] .data-table td .action-buttons {
                display: flex;
                justify-content: flex-start;
                gap: 8px;
            }

            /* Modal RTL adjustments */
            body[dir="rtl"] .modal-header {
                flex-direction: row-reverse;
            }

            body[dir="rtl"] .close-modal {
                margin-left: 0;
                margin-right: auto;
            }

            body[dir="rtl"] .form-group input,
            body[dir="rtl"] .form-group select,
            body[dir="rtl"] .form-group textarea {
                text-align: right;
            }

            /* Pagination in RTL */
            body[dir="rtl"] #pagination-buttons {
                flex-direction: row-reverse;
            }

            /* Card headers in RTL */
            body[dir="rtl"] .card-header {
                flex-direction: row-reverse;
                justify-content: space-between;
            }

            /* Toast Notifications */
            .toast {
                position: fixed;
                bottom: 30px;
                right: 30px;
                padding: 12px 24px;
                border-radius: 8px;
                color: white;
                z-index: 9999;
                animation: slideIn 0.3s;
            }
            .toast-success { background: #27ae60; }
            .toast-error { background: #e74c3c; }
            .toast-warning { background: #f39c12; }
            .toast-info { background: #3498db; }

            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0%); opacity: 1; }
            }

            /* Loading Overlay */
            #loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>
                    <span>🍽️</span>
                    <span><?php echo t('app_title'); ?></span>
                </h2>
                <p><?php echo t('admin_dashboard'); ?></p>
            </div>
            <nav class="nav-menu">
                <a href="ajax_index.php" class="nav-item <?php echo $current_page == 'ajax_index.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label"><?php echo t('dashboard'); ?></span>
                </a>
                <a href="kitchens.php" class="nav-item <?php echo $current_page == 'kitchens.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-label"><?php echo t('kitchens'); ?></span>
                </a>
                <a href="categories.php" class="nav-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📁</span>
                    <span class="nav-label"><?php echo t('categories'); ?></span>
                </a>
                <a href="menu_items.php" class="nav-item <?php echo $current_page == 'menu_items.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🍕</span>
                    <span class="nav-label"><?php echo t('menu_items'); ?></span>
                </a>
                <a href="sessions.php" class="nav-item <?php echo $current_page == 'sessions.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-label"><?php echo t('sessions'); ?></span>
                </a>
                <a href="orders.php" class="nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label"><?php echo t('orders'); ?></span>
                </a>
                <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label"><?php echo t('settings'); ?></span>
                </a>
                <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-label"><?php echo t('user_management'); ?></span>
                </a>
                <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label"><?php echo t('reports'); ?></span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <span>🚪</span>
                    <span><?php echo t('logout'); ?></span>
                </a>
                <a href="#" id="about-btn" class="nav-item">
                    <span class="nav-icon">ℹ️</span>
                    <span class="nav-label"><?php echo t('about'); ?></span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Language Switcher Bar -->
            <div class="language-bar">
                <!-- About Modal -->
            <div id="about-modal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 400px; text-align: center;">
                    <div class="modal-header">
                        <h3><?php echo t('about'); ?></h3>
                        <button class="close-modal" onclick="closeAboutModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="font-size: 3rem; margin-bottom: 10px;">👨‍💻</div>
                        <h4><?php echo t('developer'); ?>: Fady Alchaar</h4>
                        <div style="margin: 12px 0;">
                            📞 <strong><?php echo t('phone'); ?>:</strong> +963 937 764 548<br>
                            ✉️ <strong><?php echo t('email'); ?>:</strong> <a href="mailto:fady.alchaar@outlook.com">fady.alchaar@outlook.com</a><br>
                            💬 <strong><?php echo t('whatsapp'); ?>:</strong> <a href="https://wa.me/+963937764548" target="_blank">+963937764548</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="closeAboutModal()"><?php echo t('close'); ?></button>
                    </div>
                </div>
            </div>
                <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>" style="background: <?php echo $lang == 'en' ? '#3498db' : '#f1f5f9'; ?>; color: <?php echo $lang == 'en' ? 'white' : '#475569'; ?>;">English</a>
                <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>" style="background: <?php echo $lang == 'ar' ? '#3498db' : '#f1f5f9'; ?>; color: <?php echo $lang == 'ar' ? 'white' : '#475569'; ?>;">العربية</a>
            </div>