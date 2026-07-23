<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    die('Invalid order ID');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $order_query = "SELECT o.*, u.full_name as waiter_name
                    FROM orders o
                    LEFT JOIN users u ON o.waiter_id = u.id
                    WHERE o.id = :id";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch();
    
    if (!$order) {
        die('Order not found');
    }
    
    $items_query = "SELECT oi.*, mi.name as item_name
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                    WHERE oi.order_id = :order_id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([':order_id' => $order_id]);
    $items = $items_stmt->fetchAll();
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - Order <?php echo $order['order_number']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .receipt {
            text-align: center;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000;
        }
        .restaurant-name {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .order-details {
            margin: 20px 0;
            text-align: left;
        }
        .items {
            margin: 20px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .total {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 0.8rem;
            text-align: center;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        button {
            margin: 20px auto;
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: block;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="restaurant-name">🍽️ Restaurant POS</div>
            <div>Kitchen Receipt</div>
        </div>
        
        <div class="order-details">
            <div>Order #: <?php echo $order['order_number']; ?></div>
            <div>Table: <?php echo $order['table_number']; ?></div>
            <div>Date: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
            <div>Waiter: <?php echo $order['waiter_name']; ?></div>
        </div>
        
        <div class="items">
            <div style="font-weight: bold; border-bottom: 1px dashed #000;">ITEMS</div>
            <?php foreach ($items as $item): ?>
            <div class="item">
                <span><?php echo $item['quantity']; ?>x <?php echo $item['item_name']; ?></span>
                <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
            </div>
            <?php if ($item['notes']): ?>
            <div style="font-size: 0.7rem; margin-left: 20px; color: #666;">📝 <?php echo $item['notes']; ?></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="total">
            <span>TOTAL:</span>
            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        
        <div class="footer">
            <div>Thank you for your order!</div>
            <div>Please show this receipt</div>
        </div>
    </div>
    
    <button onclick="window.print()" class="no-print">🖨️ Print Receipt</button>
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>