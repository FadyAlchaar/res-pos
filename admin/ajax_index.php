<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('dashboard'); ?></h1>
    <p class="page-description"><?php echo t('welcome_back'); ?> <?php echo htmlspecialchars($_SESSION['full_name']); ?>! <?php echo t('here_is_today'); ?></p>
</div>

<div class="page-content">
    <?php
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get counts
        $kitchens = $db->query("SELECT COUNT(*) FROM kitchens WHERE is_active = 1")->fetchColumn();
        $categories = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
        $menu_items = $db->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1")->fetchColumn();
        $today_orders = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $pending_orders = $db->query("SELECT COUNT(*) FROM order_items WHERE status = 'pending'")->fetchColumn();
        $users = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        
        // Get recent orders
        $recent_orders = $db->query("SELECT o.order_number, o.table_number, o.created_at, 
                                     COUNT(oi.id) as item_count 
                                     FROM orders o 
                                     JOIN order_items oi ON o.id = oi.order_id 
                                     WHERE DATE(o.created_at) = CURDATE()
                                     GROUP BY o.id 
                                     ORDER BY o.created_at DESC LIMIT 5");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">🏠</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $kitchens; ?></div>
            <div style="color: #64748b;"><?php echo t('kitchens'); ?></div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">📁</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $categories; ?></div>
            <div style="color: #64748b;"><?php echo t('categories'); ?></div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">🍕</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $menu_items; ?></div>
            <div style="color: #64748b;"><?php echo t('menu_items'); ?></div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">📋</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $today_orders; ?></div>
            <div style="color: #64748b;"><?php echo t('today_orders'); ?></div>
        </div>
        <!-- <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">⏳</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $pending_orders; ?></div>
            <div style="color: #64748b;"><?php echo t('pending_items'); ?></div>
        </div> -->
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem;">👥</div>
            <div style="font-size: 2rem; font-weight: bold;"><?php echo $users; ?></div>
            <div style="color: #64748b;"><?php echo t('active_users'); ?></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('recent_orders'); ?></h3>
            <a href="orders.php" class="btn btn-primary btn-sm"><?php echo t('view_all'); ?></a>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo t('order_number'); ?></th>
                        <th><?php echo t('table'); ?></th>
                        <th><?php echo t('items'); ?></th>
                        <th><?php echo t('time'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch()): ?>
                    <tr>
                        <td><?php echo $order['order_number']; ?></td>
                        <td><?php echo $order['table_number']; ?></td>
                        <td><?php echo $order['item_count']; ?></td>
                        <td><?php echo date('H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layout/sidebar_footer.php'; ?>