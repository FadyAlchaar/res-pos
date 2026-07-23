<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get categories with their kitchen info
    $query = "SELECT c.*, k.name as kitchen_name, k.printer_ip, 
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
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Orders</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .debug-panel { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .category { background: #e3f2fd; padding: 10px; margin: 5px 0; cursor: pointer; }
        .menu-item { background: #f1f8e9; padding: 10px; margin: 5px 0 5px 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffebee; padding: 10px; }
        .success { color: green; background: #e8f5e8; padding: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Debug Mode - Order System Test</h1>
    
    <div class="debug-panel">
        <h2>Categories in Database:</h2>
        <?php if (empty($categories)): ?>
            <div class="error">No categories found! Please run the population script.</div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
                <div class="category" onclick="testCategory(<?php echo $cat['id']; ?>)">
                    <strong><?php echo $cat['name']; ?></strong> 
                    (Kitchen: <?php echo $cat['kitchen_name'] ?? 'Not assigned'; ?>) - 
                    Items: <?php echo $cat['item_count']; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="debug-panel">
        <h2>Test API Response:</h2>
        <div id="api-test">Click a category above to test</div>
    </div>
    
    <div class="debug-panel">
        <h2>Console Output:</h2>
        <pre id="console"></pre>
    </div>
    
    <script>
    function testCategory(categoryId) {
        const apiDiv = document.getElementById('api-test');
        const consoleDiv = document.getElementById('console');
        
        apiDiv.innerHTML = 'Loading...';
        
        // Test the API endpoint
        fetch(`get_menu_items.php?category_id=${categoryId}&t=${new Date().getTime()}`)
            .then(response => {
                consoleDiv.innerHTML += `Response Status: ${response.status} ${response.statusText}\n`;
                consoleDiv.innerHTML += `Headers: ${JSON.stringify([...response.headers])}\n`;
                return response.text(); // Get as text first to see raw response
            })
            .then(text => {
                consoleDiv.innerHTML += `Raw Response: ${text}\n`;
                
                try {
                    const data = JSON.parse(text);
                    if (data.error) {
                        apiDiv.innerHTML = `<div class="error">API Error: ${data.error}</div>`;
                    } else {
                        apiDiv.innerHTML = `<div class="success">Found ${data.length} items:</div>`;
                        data.forEach(item => {
                            apiDiv.innerHTML += `<div class="menu-item">${item.name} - $${item.price}</div>`;
                        });
                    }
                } catch (e) {
                    apiDiv.innerHTML = `<div class="error">Invalid JSON response: ${text}</div>`;
                }
            })
            .catch(error => {
                apiDiv.innerHTML = `<div class="error">Fetch Error: ${error}</div>`;
                consoleDiv.innerHTML += `Fetch Error: ${error}\n`;
            });
    }
    </script>
</body>
</html>