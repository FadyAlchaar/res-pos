<?php
// Simple database population script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';

echo "<h1>🍽️ Restaurant Database Populator</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Connected to database successfully</p>";
    
    // Clear existing tables
    echo "<p>Clearing existing data...</p>";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE print_jobs");
    $db->exec("TRUNCATE TABLE order_items");
    $db->exec("TRUNCATE TABLE orders");
    $db->exec("TRUNCATE TABLE menu_items");
    $db->exec("TRUNCATE TABLE categories");
    $db->exec("TRUNCATE TABLE kitchens");
    $db->exec("TRUNCATE TABLE users");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insert Kitchens
    echo "<p>📋 Adding kitchens...</p>";
    $kitchens = [
        ['Main Kitchen', '192.168.1.100', 9100, 'Main cooking area'],
        ['Grill Station', '192.168.1.101', 9100, 'Steaks and grilled items'],
        ['Pizza Station', '192.168.1.102', 9100, 'Pizzas'],
        ['Cold Kitchen', '192.168.1.103', 9100, 'Salads and appetizers'],
        ['Beverage Station', '192.168.1.104', 9100, 'Drinks']
    ];
    
    $kitchen_ids = [];
    $stmt = $db->prepare("INSERT INTO kitchens (name, printer_ip, printer_port, notes, is_active) VALUES (?, ?, ?, ?, 1)");
    
    foreach ($kitchens as $k) {
        $stmt->execute([$k[0], $k[1], $k[2], $k[3]]);
        $kitchen_ids[$k[0]] = $db->lastInsertId();
        echo "Added kitchen: {$k[0]}<br>";
    }
    
    // Insert Categories
    echo "<p>📋 Adding categories...</p>";
    $categories = [
        ['Appetizers', $kitchen_ids['Cold Kitchen'], 1],
        ['Soups & Salads', $kitchen_ids['Cold Kitchen'], 2],
        ['Main Courses', $kitchen_ids['Main Kitchen'], 3],
        ['Grilled Items', $kitchen_ids['Grill Station'], 4],
        ['Pizzas', $kitchen_ids['Pizza Station'], 5],
        ['Beverages', $kitchen_ids['Beverage Station'], 6],
        ['Desserts', $kitchen_ids['Main Kitchen'], 7]
    ];
    
    $category_ids = [];
    $stmt = $db->prepare("INSERT INTO categories (name, kitchen_id, sort_order, description, is_active) VALUES (?, ?, ?, ?, 1)");
    
    foreach ($categories as $c) {
        $stmt->execute([$c[0], $c[1], $c[2], 'Delicious ' . $c[0]]);
        $category_ids[$c[0]] = $db->lastInsertId();
        echo "Added category: {$c[0]}<br>";
    }
    
    // Insert Menu Items
    echo "<p>📋 Adding menu items...</p>";
    
    $menu_items = [
        // Appetizers
        ['Garlic Bread', $category_ids['Appetizers'], 6.99, 'Toasted bread with garlic butter', 10],
        ['Bruschetta', $category_ids['Appetizers'], 8.99, 'Topped with tomatoes and basil', 12],
        ['Calamari', $category_ids['Appetizers'], 12.99, 'Fried calamari with sauce', 15],
        ['Mozzarella Sticks', $category_ids['Appetizers'], 8.99, 'Breaded and fried', 10],
        
        // Soups & Salads
        ['Caesar Salad', $category_ids['Soups & Salads'], 8.99, 'With croutons and parmesan', 8],
        ['Greek Salad', $category_ids['Soups & Salads'], 9.99, 'Feta cheese and olives', 8],
        ['French Onion Soup', $category_ids['Soups & Salads'], 7.99, 'With melted cheese', 10],
        
        // Main Courses
        ['Chicken Parmesan', $category_ids['Main Courses'], 16.99, 'Breaded chicken with marinara', 20],
        ['Beef Stroganoff', $category_ids['Main Courses'], 18.99, 'Tender beef in mushroom sauce', 20],
        ['Fish & Chips', $category_ids['Main Courses'], 15.99, 'Beer-battered cod', 15],
        
        // Grilled Items
        ['Ribeye Steak', $category_ids['Grilled Items'], 29.99, '12oz prime ribeye', 20],
        ['Grilled Salmon', $category_ids['Grilled Items'], 19.99, 'With lemon butter sauce', 18],
        ['BBQ Chicken', $category_ids['Grilled Items'], 16.99, 'Half chicken with BBQ sauce', 20],
        
        // Pizzas
        ['Margherita Pizza', $category_ids['Pizzas'], 14.99, 'Tomato, mozzarella, basil', 15],
        ['Pepperoni Pizza', $category_ids['Pizzas'], 16.99, 'Classic pepperoni', 15],
        ['Vegetarian Pizza', $category_ids['Pizzas'], 15.99, 'Assorted vegetables', 15],
        
        // Beverages
        ['Soft Drinks', $category_ids['Beverages'], 2.99, 'Coke, Sprite, Fanta', 2],
        ['Fresh Juice', $category_ids['Beverages'], 3.99, 'Orange, apple, carrot', 3],
        ['Coffee', $category_ids['Beverages'], 2.49, 'Fresh brewed', 3],
        ['Tea', $category_ids['Beverages'], 2.49, 'Assorted flavors', 3],
        
        // Desserts
        ['Cheesecake', $category_ids['Desserts'], 6.99, 'New York style', 5],
        ['Chocolate Cake', $category_ids['Desserts'], 6.99, 'Rich chocolate layer cake', 5],
        ['Ice Cream', $category_ids['Desserts'], 4.99, 'Vanilla, chocolate, strawberry', 3]
    ];
    
    $stmt = $db->prepare("INSERT INTO menu_items (name, category_id, price, description, preparation_time, is_available) VALUES (?, ?, ?, ?, ?, 1)");
    
    foreach ($menu_items as $item) {
        $stmt->execute([$item[0], $item[1], $item[2], $item[3], $item[4]]);
        echo "Added: {$item[0]} - \${$item[2]}<br>";
    }
    
    // Insert Users
    echo "<p>📋 Adding users...</p>";
    
    // Password is 'password123'
    $hashed = password_hash('admin', PASSWORD_DEFAULT);
    
    $users = [
        ['admin', $hashed, 'Admin User', 'admin'],
        ['test1', $hashed, 'John Waiter', 'waiter'],
        ['test2', $hashed, 'Sarah Waiter', 'waiter']
    ];
    
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, is_active) VALUES (?, ?, ?, ?, 1)");
    
    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], $u[2], $u[3]]);
        echo "Added user: {$u[2]} ({$u[3]})<br>";
    }
    
    echo "<h2 style='color: green; margin-top: 20px;'>✅ Database populated successfully!</h2>";
    
    // Show counts
    $counts = $db->query("SELECT COUNT(*) as count FROM kitchens")->fetch();
    echo "<p>Kitchens: {$counts['count']}</p>";
    
    $counts = $db->query("SELECT COUNT(*) as count FROM categories")->fetch();
    echo "<p>Categories: {$counts['count']}</p>";
    
    $counts = $db->query("SELECT COUNT(*) as count FROM menu_items")->fetch();
    echo "<p>Menu Items: {$counts['count']}</p>";
    
    $counts = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "<p>Users: {$counts['count']}</p>";
    
    echo "<p style='margin-top: 20px;'><strong>Login credentials:</strong> username: admin / password: password123</p>";
    echo "<p><a href='../login.php' style='background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>