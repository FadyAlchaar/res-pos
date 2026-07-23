<?php
require_once '../config/config.php';
require_once '../config/language.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('/login.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle user deletion
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting yourself
        if ($user_id != $_SESSION['user_id']) {
            $delete = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($delete);
            $stmt->execute([':id' => $user_id]);
            $success = t('user_deleted');
        } else {
            $error = t('cannot_delete_self');
        }
    }
    
    // Get all users
    $query = "SELECT id, username, full_name, role, is_active, created_at, last_login 
              FROM users 
              ORDER BY role, full_name";
    $stmt = $db->query($query);
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('user_management'); ?> - <?php echo t('admin_dashboard'); ?></title>
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
            flex-wrap: wrap;
            gap: 15px;
        }
        .language-switcher {
            display: flex;
            gap: 5px;
        }
        .lang-btn {
            padding: 5px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            color: white;
            transition: all 0.2s;
        }
        .lang-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .lang-btn.active {
            background: white;
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.8rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }
        body[dir="rtl"] .table th {
            text-align: right;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .nav-links {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .nav-links a {
            color: #3498db;
            text-decoration: none;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .role-badge {
            background: #3498db;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .role-waiter { background: #27ae60; }
        .role-admin { background: #e74c3c; }
        .role-kitchen { background: #f39c12; }
        .role-parking { background: #8e44ad; }
    </style>
</head>
<body>
    <div class="header">
        <h2>👥 <?php echo t('user_management'); ?></h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="language-switcher">
                <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">عربي</a>
            </div>
            <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm"><?php echo t('logout'); ?></a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><?php echo t('users'); ?></h2>
                <button class="btn btn-primary" onclick="openAddModal()">+ <?php echo t('add_user'); ?></button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo t('username'); ?></th>
                        <th><?php echo t('full_name'); ?></th>
                        <th><?php echo t('role'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo t($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"><?php echo t('edit'); ?></button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo t('confirm_delete_user'); ?>')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm"><?php echo t('delete'); ?></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="nav-links">
            <a href="ajax_index.php">← <?php echo t('dashboard'); ?></a>
            <a href="settings.php">⚙️ <?php echo t('settings'); ?></a>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle"><?php echo t('add_user'); ?></h3>
            <form method="POST" id="userForm" action="save_user.php">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="form-group">
                    <label><?php echo t('username'); ?> *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label><?php echo t('full_name'); ?> *</label>
                    <input type="text" name="full_name" id="full_name" required>
                </div>
                
                <div class="form-group">
                    <label><?php echo t('password'); ?> <span id="password_optional" style="font-weight: normal; font-size: 0.8rem;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" id="password">
                </div>
                
                <div class="form-group">
                    <label><?php echo t('role'); ?></label>
                    <select name="role" id="role">
                        <option value="waiter"><?php echo t('waiter'); ?></option>
                        <option value="admin"><?php echo t('admin'); ?></option>
                        <option value="kitchen"><?php echo t('kitchen_staff'); ?></option>
                        <option value="parking"><?php echo t('parking_staff'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php echo t('status'); ?></label>
                    <select name="is_active" id="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary"><?php echo t('save'); ?></button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()"><?php echo t('cancel'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('modalTitle').innerHTML = '<?php echo t('add_user'); ?>';
        document.getElementById('user_id').value = '';
        document.getElementById('username').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('password_optional').style.display = 'none';
        document.getElementById('role').value = 'waiter';
        document.getElementById('is_active').value = '1';
        document.getElementById('userModal').style.display = 'flex';
    }
    
    function editUser(user) {
        document.getElementById('modalTitle').innerHTML = '<?php echo t('edit_user'); ?>';
        document.getElementById('user_id').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('full_name').value = user.full_name;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('password_optional').style.display = 'inline';
        document.getElementById('role').value = user.role;
        document.getElementById('is_active').value = user.is_active;
        document.getElementById('userModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target === document.getElementById('userModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>