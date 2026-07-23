<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('categories'); ?></h1>
    <p class="page-description"><?php echo t('categories_description'); ?></p>
</div>

<div class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('category_list'); ?></h3>
            <button class="btn btn-primary" onclick="openCategoryModal()">+ <?php echo t('add_category'); ?></button>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo t('category_name'); ?></th>
                        <th><?php echo t('kitchen'); ?></th>
                        <!-- <th><?php echo t('sort_order'); ?></th> -->
                        <th><?php echo t('items_count'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="categories-list">
                    <tr><td colspan="6" style="text-align: center;"><?php echo t('loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Category Modal -->
<div id="category-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="category-modal-title"><?php echo t('add_category'); ?></h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="category-form">
                <input type="hidden" id="category-id" name="id">
                <div class="form-group">
                    <label><?php echo t('category_name'); ?> *</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('kitchen'); ?> *</label>
                    <select id="category-kitchen" name="kitchen_id" required>
                        <option value=""><?php echo t('select_kitchen'); ?></option>
                    </select>
                </div>
                <!-- ICON FIELD - INSERT HERE -->
                <div class="form-group">
                    <label><?php echo t('category_icon'); ?></label>
                    <select id="category-icon" name="icon">
                        <option value="🍽️">🍽️ General</option>
                        <option value="🍢">🍢 Appetizers</option>
                        <option value="🥣">🥣 Soups</option>
                        <option value="🍝">🍝 Pasta</option>
                        <option value="🐟">🐟 Seafood</option>
                        <option value="🥬">🥬 Vegetarian</option>
                        <option value="🥩">🥩 Steaks</option>
                        <option value="🍔">🍔 Burgers</option>
                        <option value="🍗">🍗 Chicken</option>
                        <option value="🍕">🍕 Pizza</option>
                        <option value="🥙">🥙 Cold Appetizers</option>
                        <option value="🥗">🥗 Salads</option>
                        <option value="🥪">🥪 Sandwiches</option>
                        <option value="☕">☕ Hot Drinks</option>
                        <option value="🥤">🥤 Cold Drinks</option>
                        <option value="🔥">🔥 Hot Dishes</option>
                        <option value="🍰">🍰 Desserts</option>
                    </select>
                </div>
                <!-- END ICON FIELD -->
                <div class="form-group">
                    <label><?php echo t('sort_order'); ?></label>
                    <input type="number" id="category-sort" name="sort_order" value="0">
                </div>
                <div class="form-group">
                    <label><?php echo t('description'); ?></label>
                    <textarea id="category-description" name="description" rows="3" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="saveCategory()"><?php echo t('save'); ?></button>
            <button class="btn btn-danger" onclick="closeModal()"><?php echo t('cancel'); ?></button>
        </div>
    </div>
</div>

<script>
    let kitchens = [];
    
    function loadKitchensForDropdown() {
        fetch('api/get_kitchens.php')
            .then(response => response.json())
            .then(data => {
                kitchens = data;
                const select = document.getElementById('category-kitchen');
                select.innerHTML = '<option value="">Select Kitchen</option>' + 
                    kitchens.map(k => `<option value="${k.id}">${k.name}</option>`).join('');
            });
    }
    
    function loadCategories() {
        fetch('api/get_categories.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('categories-list');
                if (data.error) {
                    tbody.innerHTML = `<tr><td colspan="6" style="color: red;">${data.error}</td></tr>`;
                    return;
                }
                
                tbody.innerHTML = data.map(cat => `
                    <tr>
                        <td><strong>${cat.name}</strong></td>
                        <td>${cat.kitchen_name || 'Not assigned'}</td>
                        <td>${cat.sort_order || 0}</td>
                        <td>${cat.item_count || 0}</td>
                        <td>
                            <span class="badge ${cat.is_active ? 'badge-success' : 'badge-danger'}">
                                ${cat.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editCategory(${cat.id})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCategory(${cat.id})">Delete</button>
                        </td>
                    </tr>
                `).join('');
            });
    }
    
    function openCategoryModal() {
        document.getElementById('category-modal-title').textContent = 'Add Category';
        document.getElementById('category-form').reset();
        document.getElementById('category-id').value = '';
        document.getElementById('category-modal').style.display = 'flex';
        loadKitchensForDropdown();
    }
    
    function editCategory(id) {
        fetch(`api/get_category.php?id=${id}`)
            .then(response => response.json())
            .then(cat => {
                document.getElementById('category-modal-title').textContent = '<?php echo t('edit_category'); ?>';
                document.getElementById('category-id').value = cat.id;
                document.getElementById('category-name').value = cat.name;
                document.getElementById('category-sort').value = cat.sort_order || 0;
                document.getElementById('category-description').value = cat.description || '';
                document.getElementById('category-icon').value = cat.icon || '🍽️';  // ADD THIS LINE
                document.getElementById('category-modal').style.display = 'flex';
                
                loadKitchensForDropdown();
                setTimeout(() => {
                    document.getElementById('category-kitchen').value = cat.kitchen_id;
                }, 100);
            });
    }    
    function saveCategory() {
        const data = {
            id: document.getElementById('category-id').value,
            name: document.getElementById('category-name').value,
            kitchen_id: document.getElementById('category-kitchen').value,
            sort_order: document.getElementById('category-sort').value,
            description: document.getElementById('category-description').value,
            icon: document.getElementById('category-icon').value  // ADD THIS LINE
        };
        
        fetch('api/save_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                loadCategories();
                showToast('Category saved successfully', 'success');
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        });
    }
    
    function deleteCategory(categoryId) {
        if (!confirm('<?php echo t('confirm_delete_category'); ?>')) return;
        
        fetch('api/delete_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: categoryId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('<?php echo t('category_deleted'); ?>');
                location.reload(); // or loadCategories() if defined
            } else {
                alert('<?php echo t('error'); ?>: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo t('error'); ?>');
        });
    }
    
    function closeModal() {
        document.getElementById('category-modal').style.display = 'none';
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Load on page load
    loadCategories();
</script>

<?php include 'layout/sidebar_footer.php'; ?>