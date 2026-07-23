<?php
require_once '../config/config.php';
require_once '../config/language.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all active tables
    $query = "SELECT * FROM restaurant_tables WHERE is_active = 1 ORDER BY sort_order";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
    // Get current orders to mark occupied tables
    $query = "SELECT DISTINCT table_id FROM orders WHERE status IN ('pending', 'preparing', 'ready') AND payment_status = 'unpaid'";
    $stmt = $db->query($query);
    $occupied_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get saved layout positions
    $query = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'table_layout'";
    $stmt = $db->query($query);
    $layout = $stmt->fetch();
    $saved_positions = $layout ? json_decode($layout['setting_value'], true) : [];
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Table Map | Restaurant POS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a2a3a;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: #0f1a24;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2d3e4e;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo h1 {
            font-size: 1.2rem;
            color: #ecf0f1;
        }
        
        .controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
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
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .zoom-controls {
            display: flex;
            gap: 5px;
            background: #2d3e4e;
            padding: 4px;
            border-radius: 30px;
        }
        
        .zoom-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #1a2a3a;
            color: white;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .zoom-btn:hover {
            background: #3498db;
        }
        
        /* Canvas Container */
        .canvas-container {
            height: calc(100vh - 60px);
            overflow: auto;
            background: #1e2f3a;
            position: relative;
            cursor: grab;
        }
        
        .canvas-container:active {
            cursor: grabbing;
        }
        
        #floor-plan {
            position: relative;
            margin: 50px;
            min-height: calc(100% - 100px);
            min-width: calc(100% - 100px);
            transition: transform 0.1s ease;
            transform-origin: 0 0;
        }
        
        /* Table Card */
        .table-card {
            position: absolute;
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: move;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border: 3px solid #bdc3c7;
        }
        
        .table-card:active {
            cursor: grabbing;
        }
        
        .table-card.dragging {
            opacity: 0.6;
            cursor: grabbing;
        }
        
        .table-card.occupied {
            background: #fff3e0;
            border-color: #e67e22;
        }
        
        .table-card.available {
            background: #e8f5e9;
            border-color: #27ae60;
        }
        
        .table-card.reserved {
            background: #e3f2fd;
            border-color: #3498db;
        }
        
        .table-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .table-status {
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 20px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .status-occupied {
            background: #e67e22;
            color: white;
        }
        
        .status-available {
            background: #27ae60;
            color: white;
        }
        
        .status-reserved {
            background: #3498db;
            color: white;
        }
        
        .table-capacity {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-top: 4px;
        }
        
        /* Controls Panel */
        .floating-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 100;
        }
        
        .panel-title {
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .layout-controls {
            display: flex;
            gap: 8px;
        }
        
        .grid-line {
            position: absolute;
            background: rgba(52, 152, 219, 0.2);
            pointer-events: none;
        }
        
        .grid-line.horizontal {
            height: 1px;
            width: 100%;
        }
        
        .grid-line.vertical {
            width: 1px;
            height: 100%;
        }
        
        /* Tooltip */
        .tooltip {
            position: fixed;
            background: #2c3e50;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            pointer-events: none;
            z-index: 1000;
            white-space: nowrap;
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
        
        .modal-content {
            background: white;
            padding: 24px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-content h3 {
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 80px;
            right: 20px;
            padding: 12px 20px;
            background: #27ae60;
            color: white;
            border-radius: 30px;
            font-size: 0.85rem;
            animation: slideIn 0.3s;
            z-index: 150;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span style="font-size: 1.5rem;">🗺️</span>
            <h1>Restaurant Table Map</h1>
        </div>
        <div class="controls">
            <div class="zoom-controls">
                <button class="zoom-btn" onclick="zoomIn()">+</button>
                <button class="zoom-btn" onclick="zoomOut()">-</button>
                <button class="zoom-btn" onclick="resetZoom()">⟳</button>
            </div>
            <button class="btn btn-success" onclick="saveLayout()">💾 Save Layout</button>
            <button class="btn btn-warning" onclick="resetLayout()">🔄 Reset Layout</button>
            <button class="btn btn-primary" onclick="showGrid()">📐 Show Grid</button>
            <button class="btn btn-danger" onclick="clearAll()">🗑️ Clear All</button>
        </div>
    </div>

    <div class="canvas-container" id="canvas-container">
        <div id="floor-plan" style="position: relative;">
            <!-- Tables will be rendered here -->
        </div>
    </div>

    <div class="floating-panel">
        <div class="panel-title">📐 Layout Tools</div>
        <div class="layout-controls">
            <button class="btn btn-primary" onclick="autoArrange()">Auto Arrange</button>
            <button class="btn btn-primary" onclick="alignLeft()">Align Left</button>
            <button class="btn btn-primary" onclick="alignCenter()">Align Center</button>
            <button class="btn btn-primary" onclick="alignRight()">Align Right</button>
        </div>
    </div>

    <!-- Table Edit Modal -->
    <div id="table-modal" class="modal">
        <div class="modal-content">
            <h3>Edit Table</h3>
            <form id="table-form">
                <input type="hidden" id="edit-table-id">
                <div class="form-group">
                    <label>Table Name</label>
                    <input type="text" id="edit-table-name">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="edit-table-capacity" min="1" max="20">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="edit-table-status">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let tables = <?php echo json_encode($tables); ?>;
        let occupiedTables = <?php echo json_encode($occupied_tables); ?>;
        let savedPositions = <?php echo json_encode($saved_positions); ?>;
        let zoom = 1;
        let panX = 0, panY = 0;
        let isPanning = false;
        let startX, startY;
        let currentTable = null;
        let offsetX, offsetY;
        let showGridLines = false;
        
        const container = document.getElementById('canvas-container');
        const floorPlan = document.getElementById('floor-plan');
        
        // Initialize
        function init() {
            renderTables();
            applySavedPositions();
            enablePanning();
            enableDragging();
        }
        
        // Render tables
        function renderTables() {
            floorPlan.innerHTML = '';
            tables.forEach(table => {
                const isOccupied = occupiedTables.includes(table.id);
                const status = isOccupied ? 'occupied' : (table.status || 'available');
                const pos = savedPositions[table.id] || { x: 100, y: 100 };
                
                const tableDiv = document.createElement('div');
                tableDiv.className = `table-card ${status}`;
                tableDiv.id = `table-${table.id}`;
                tableDiv.style.left = `${pos.x}px`;
                tableDiv.style.top = `${pos.y}px`;
                tableDiv.setAttribute('data-id', table.id);
                tableDiv.setAttribute('data-name', table.table_name);
                tableDiv.setAttribute('data-capacity', table.capacity);
                tableDiv.setAttribute('data-status', status);
                tableDiv.innerHTML = `
                    <div class="table-number">${table.table_name || 'Table ' + table.table_number}</div>
                    <div class="table-status status-${status}">${status.toUpperCase()}</div>
                    <div class="table-capacity">👥 ${table.capacity} seats</div>
                `;
                
                // Double click to edit
                tableDiv.ondblclick = (e) => {
                    e.stopPropagation();
                    editTable(table.id, table.table_name, table.capacity, status);
                };
                
                floorPlan.appendChild(tableDiv);
            });
        }
        
        // Apply saved positions
        function applySavedPositions() {
            Object.keys(savedPositions).forEach(id => {
                const tableDiv = document.getElementById(`table-${id}`);
                if (tableDiv) {
                    tableDiv.style.left = `${savedPositions[id].x}px`;
                    tableDiv.style.top = `${savedPositions[id].y}px`;
                }
            });
        }
        
        // Enable panning
        function enablePanning() {
            container.addEventListener('mousedown', (e) => {
                if (e.target === container || e.target === floorPlan) {
                    isPanning = true;
                    startX = e.clientX - panX;
                    startY = e.clientY - panY;
                    container.style.cursor = 'grabbing';
                }
            });
            
            window.addEventListener('mousemove', (e) => {
                if (isPanning) {
                    panX = e.clientX - startX;
                    panY = e.clientY - startY;
                    floorPlan.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
                }
            });
            
            window.addEventListener('mouseup', () => {
                isPanning = false;
                container.style.cursor = 'grab';
            });
        }
        
        // Enable dragging for tables
        function enableDragging() {
            let draggedTable = null;
            let dragStartX, dragStartY;
            
            floorPlan.addEventListener('mousedown', (e) => {
                const table = e.target.closest('.table-card');
                if (table) {
                    draggedTable = table;
                    dragStartX = e.clientX;
                    dragStartY = e.clientY;
                    const rect = table.getBoundingClientRect();
                    offsetX = e.clientX - rect.left;
                    offsetY = e.clientY - rect.top;
                    table.classList.add('dragging');
                    e.preventDefault();
                }
            });
            
            window.addEventListener('mousemove', (e) => {
                if (draggedTable) {
                    const newX = e.clientX - offsetX;
                    const newY = e.clientY - offsetY;
                    draggedTable.style.left = `${newX}px`;
                    draggedTable.style.top = `${newY}px`;
                }
            });
            
            window.addEventListener('mouseup', (e) => {
                if (draggedTable) {
                    draggedTable.classList.remove('dragging');
                    const tableId = draggedTable.getAttribute('data-id');
                    savedPositions[tableId] = {
                        x: parseInt(draggedTable.style.left),
                        y: parseInt(draggedTable.style.top)
                    };
                    draggedTable = null;
                }
            });
        }
        
        // Zoom functions
        function zoomIn() {
            zoom = Math.min(zoom + 0.1, 2);
            floorPlan.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
        }
        
        function zoomOut() {
            zoom = Math.max(zoom - 0.1, 0.5);
            floorPlan.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
        }
        
        function resetZoom() {
            zoom = 1;
            panX = 0;
            panY = 0;
            floorPlan.style.transform = `translate(0px, 0px) scale(1)`;
        }
        
        // Save layout to database
        function saveLayout() {
            const layout = {};
            document.querySelectorAll('.table-card').forEach(table => {
                const id = table.getAttribute('data-id');
                layout[id] = {
                    x: parseInt(table.style.left),
                    y: parseInt(table.style.top)
                };
            });
            
            fetch('save_table_layout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ layout: layout })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Layout saved successfully!', 'success');
                    savedPositions = layout;
                }
            });
        }
        
        // Reset layout (auto arrange)
        function resetLayout() {
            if (confirm('Reset all table positions?')) {
                autoArrange();
                saveLayout();
            }
        }
        
        // Auto arrange tables in grid
        function autoArrange() {
            const startX = 100;
            const startY = 100;
            const spacingX = 140;
            const spacingY = 140;
            const cols = Math.ceil(Math.sqrt(tables.length));
            
            tables.forEach((table, index) => {
                const row = Math.floor(index / cols);
                const col = index % cols;
                const x = startX + (col * spacingX);
                const y = startY + (row * spacingY);
                
                const tableDiv = document.getElementById(`table-${table.id}`);
                if (tableDiv) {
                    tableDiv.style.left = `${x}px`;
                    tableDiv.style.top = `${y}px`;
                    savedPositions[table.id] = { x, y };
                }
            });
        }
        
        // Alignment functions
        function alignLeft() {
            const tables = document.querySelectorAll('.table-card');
            let minX = Infinity;
            tables.forEach(t => {
                const x = parseInt(t.style.left);
                if (x < minX) minX = x;
            });
            tables.forEach(t => {
                t.style.left = `${minX}px`;
                const id = t.getAttribute('data-id');
                savedPositions[id].x = minX;
            });
        }
        
        function alignCenter() {
            const tables = document.querySelectorAll('.table-card');
            const centerX = 500;
            tables.forEach(t => {
                t.style.left = `${centerX}px`;
                const id = t.getAttribute('data-id');
                savedPositions[id].x = centerX;
            });
        }
        
        function alignRight() {
            const tables = document.querySelectorAll('.table-card');
            let maxX = -Infinity;
            tables.forEach(t => {
                const x = parseInt(t.style.left);
                if (x > maxX) maxX = x;
            });
            tables.forEach(t => {
                t.style.left = `${maxX}px`;
                const id = t.getAttribute('data-id');
                savedPositions[id].x = maxX;
            });
        }
        
        // Show grid overlay
        function showGrid() {
            showGridLines = !showGridLines;
            if (showGridLines) {
                drawGrid();
            } else {
                removeGrid();
            }
        }
        
        function drawGrid() {
            removeGrid();
            const step = 50;
            const width = floorPlan.clientWidth;
            const height = floorPlan.clientHeight;
            
            for (let x = 0; x < width; x += step) {
                const line = document.createElement('div');
                line.className = 'grid-line vertical';
                line.style.left = `${x}px`;
                line.style.top = '0';
                line.style.height = `${height}px`;
                floorPlan.appendChild(line);
            }
            
            for (let y = 0; y < height; y += step) {
                const line = document.createElement('div');
                line.className = 'grid-line horizontal';
                line.style.top = `${y}px`;
                line.style.left = '0';
                line.style.width = `${width}px`;
                floorPlan.appendChild(line);
            }
        }
        
        function removeGrid() {
            document.querySelectorAll('.grid-line').forEach(line => line.remove());
        }
        
        // Edit table
        function editTable(id, name, capacity, status) {
            document.getElementById('edit-table-id').value = id;
            document.getElementById('edit-table-name').value = name;
            document.getElementById('edit-table-capacity').value = capacity;
            document.getElementById('edit-table-status').value = status;
            document.getElementById('table-modal').style.display = 'flex';
        }
        
        // Save table edit
        document.getElementById('table-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('edit-table-id').value;
            const name = document.getElementById('edit-table-name').value;
            const capacity = document.getElementById('edit-table-capacity').value;
            const status = document.getElementById('edit-table-status').value;
            
            fetch('update_table_info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, capacity, status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Table updated successfully!', 'success');
                    closeModal();
                    location.reload();
                }
            });
        });
        
        function closeModal() {
            document.getElementById('table-modal').style.display = 'none';
        }
        
        function clearAll() {
            if (confirm('Remove all tables from map? This will reset positions.')) {
                savedPositions = {};
                autoArrange();
                saveLayout();
            }
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Initial load
        init();
    </script>
</body>
</html>