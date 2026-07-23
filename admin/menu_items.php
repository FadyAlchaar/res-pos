<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('menu_items'); ?></h1>
    <p class="page-description"><?php echo t('menu_items_description'); ?></p>
</div>

<div class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('menu_list'); ?></h3>
            <button class="btn btn-primary" onclick="openItemModal()">+ <?php echo t('add_item'); ?></button>
        </div>
        <div class="card-body">
            <!-- Search box with clear button -->
            <div class="search-container" style="position: relative; margin-bottom: 20px;">
                <input type="text" id="item-search" class="item-search-input" placeholder="🔍 Search items by name or description..." style="width: 100%; padding: 10px 40px 10px 20px; border-radius: 30px; border: 1px solid #e9ecef;">
                <button id="clear-search" class="search-clear-btn" style="display: none;">✕</button>
            </div>
            <div id="categories-tree">
                <?php echo t('loading'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Menu Item Modal -->
<div id="item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="item-modal-title"><?php echo t('add_item'); ?></h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="item-form">
                <input type="hidden" id="item-id" name="id">
                <div class="form-group">
                    <label><?php echo t('item_name'); ?> *</label>
                    <input type="text" id="item-name" name="name" required autofocus>
                </div>
                <div class="form-group">
                    <label><?php echo t('category'); ?> *</label>
                    <select id="item-category" name="category_id" required>
                        <option value=""><?php echo t('select_category'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo t('price'); ?> *</label>
                    <input type="number" id="item-price" name="price" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label><?php echo t('description'); ?></label>
                    <textarea id="item-description" name="description" rows="3" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo t('prep_time'); ?></label>
                    <input type="number" id="item-prep-time" name="preparation_time" value="10">
                    <small><?php echo t('minutes'); ?></small>
                </div>
                <div class="form-group">
                    <label><?php echo t('sort_order'); ?></label>
                    <input type="number" id="item-sort" name="sort_order" value="0">
                </div>
                <!-- AVAILABLE CHECKBOX (ADD THIS) -->
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="item-available" name="is_available" value="1" checked>
                        <?php echo t('available'); ?>
                    </label>
                    <small><?php echo t('available_hint'); ?></small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="print-on-controller" name="print_on_controller" value="1" checked>
                        <?php echo t('print_on_controller'); ?>
                    </label>
                    <small><?php echo t('print_on_controller_hint'); ?></small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="saveItem(false)"><?php echo t('save'); ?></button>
            <button class="btn btn-success" onclick="saveItem(true)">➕ <?php echo t('save_and_add_new'); ?></button>
            <button class="btn btn-danger" onclick="closeModal()"><?php echo t('cancel'); ?></button>
        </div>
    </div>
</div>

<style>
    /* Your existing CSS (unchanged) */
    .category-group {
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
    }
    .category-header {
        background: #f8f9fa;
        padding: 12px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        font-size: 1.1rem;
        border-bottom: 1px solid #e9ecef;
        transition: background 0.2s;
    }
    .category-header:hover {
        background: #e9ecef;
    }
    .category-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .category-toggle {
        font-size: 1.2rem;
        transition: transform 0.2s;
    }
    .category-add-btn {
        background: #27ae60;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.75rem;
        cursor: pointer;
    }
    .category-add-btn:hover {
        background: #219a52;
    }
    .category-items {
        display: none;
        background: white;
    }
    .category-items.open {
        display: block;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table th, .items-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        text-align: left;
    }
    body[dir="rtl"] .items-table th, body[dir="rtl"] .items-table td {
        text-align: right;
    }
    .items-table th {
        background: #f1f3f5;
        font-weight: 600;
    }
    .item-actions {
        display: flex;
        gap: 8px;
    }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

    /* Search/filter styles */
    .category-group.filtered-out {
        display: none;
    }
    .category-items .items-table tr.filtered-out {
        display: none;
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
    }
    .search-clear-btn:hover {
        background: #e9ecef;
        color: #e74c3c;
    }
</style>

<script>
    let currentPresetCategoryId = null;

    function loadMenuTree() {
        fetch('api/get_menu_tree.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('categories-tree').innerHTML = `<div style="color:red;">${data.error}</div>`;
                    return;
                }
                displayTree(data);
                const searchInput = document.getElementById('item-search');
                if (searchInput && searchInput.value.trim() !== '') {
                    filterItems(searchInput.value.trim().toLowerCase());
                }
            })
            .catch(error => {
                console.error('Error loading menu tree:', error);
                document.getElementById('categories-tree').innerHTML = '<div style="color:red;">Failed to load menu tree</div>';
            });
    }

    function displayTree(categories) {
        if (!categories.length) {
            document.getElementById('categories-tree').innerHTML = '<div style="text-align:center; padding:40px;">No categories found</div>';
            return;
        }
        let html = '';
        categories.forEach(cat => {
            const hasItems = cat.items && cat.items.length > 0;
            html += `<div class="category-group">
                        <div class="category-header" onclick="toggleCategory(this)">
                            <div class="category-header-left">
                                <span class="category-toggle">▼</span>
                                <span>📁 ${escapeHtml(cat.name)} <span style="font-size:0.8rem; color:#6c757d;">(${cat.items ? cat.items.length : 0} items)</span></span>
                            </div>
                            <button class="category-add-btn" onclick="event.stopPropagation(); openItemModalWithCategory(${cat.id})">+ <?php echo t('add_item'); ?></button>
                        </div>
                        <div class="category-items">`;
            if (hasItems) {
                html += `<table class="items-table">
                            <thead>
                                <tr><th><?php echo t('item_name'); ?></th><th><?php echo t('price'); ?></th><th><?php echo t('prep_time'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('actions'); ?></th></tr>
                            </thead><tbody>`;
                cat.items.forEach(item => {
                    const statusText = item.is_available ? '<?php echo t('available'); ?>' : '<?php echo t('unavailable'); ?>';
                    const statusClass = item.is_available ? 'badge-success' : 'badge-danger';
                    html += `<tr>
                                <td><strong>${escapeHtml(item.name)}</strong><br><small style="color:#6c757d;">${escapeHtml(item.description) ? escapeHtml(item.description).substring(0, 50) + (item.description.length > 50 ? '...' : '') : ''}</small></td>
                                <td style="color:#27ae60; font-weight:bold;">$${parseFloat(item.price).toFixed(2)}</td>
                                <td>${item.preparation_time || 0} <?php echo t('minutes'); ?></td>
                                <td><span class="badge ${statusClass}">${statusText}</span></td>
                                <td class="item-actions">
                                    <button class="btn btn-warning btn-sm" onclick="editItem(${item.id})"><?php echo t('edit'); ?></button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem(${item.id})"><?php echo t('delete'); ?></button>
                                </td>
                            </tr>`;
                });
                html += `</tbody></table>`;
            } else {
                html += `<div style="padding: 20px; text-align: center; color: #6c757d;"><?php echo t('no_items'); ?></div>`;
            }
            html += `</div></div>`;
        });
        document.getElementById('categories-tree').innerHTML = html;
    }

    function toggleCategory(header) {
        const itemsDiv = header.parentElement.querySelector('.category-items');
        if (!itemsDiv) return;
        const isOpen = itemsDiv.classList.contains('open');
        const toggleIcon = header.querySelector('.category-toggle');
        if (isOpen) {
            itemsDiv.classList.remove('open');
            toggleIcon.textContent = '▶';
        } else {
            itemsDiv.classList.add('open');
            toggleIcon.textContent = '▼';
        }
    }

    function filterItems(searchTerm) {
        const categories = document.querySelectorAll('.category-group');
        categories.forEach(category => {
            const items = category.querySelectorAll('.items-table tbody tr');
            let anyVisible = false;
            items.forEach(item => {
                const itemName = item.querySelector('td:first-child strong')?.innerText.toLowerCase() || '';
                const itemDesc = item.querySelector('td:first-child small')?.innerText.toLowerCase() || '';
                if (searchTerm === '' || itemName.includes(searchTerm) || itemDesc.includes(searchTerm)) {
                    item.classList.remove('filtered-out');
                    anyVisible = true;
                } else {
                    item.classList.add('filtered-out');
                }
            });
            if (searchTerm !== '' && !anyVisible) {
                category.classList.add('filtered-out');
            } else {
                category.classList.remove('filtered-out');
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    let categories = [];

    function loadCategoriesForDropdown() {
        fetch('api/get_categories.php')
            .then(response => response.json())
            .then(data => {
                categories = data;
                const select = document.getElementById('item-category');
                select.innerHTML = '<option value=""><?php echo t('select_category'); ?></option>' + 
                    categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)} (${c.kitchen_name || '<?php echo t('no_kitchen'); ?>'})</option>`).join('');
                if (currentPresetCategoryId) {
                    select.value = currentPresetCategoryId;
                    currentPresetCategoryId = null;
                }
            });
    }

    function resetModalForm() {
        document.getElementById('item-form').reset();
        document.getElementById('item-id').value = '';
        document.getElementById('print-on-controller').checked = true;
        document.getElementById('item-available').checked = true;
        document.getElementById('item-name').focus();
    }

    function openItemModal() {
        currentPresetCategoryId = null;
        document.getElementById('item-modal-title').textContent = '<?php echo t('add_item'); ?>';
        resetModalForm();
        document.getElementById('item-modal').style.display = 'flex';
        loadCategoriesForDropdown();
    }

    function openItemModalWithCategory(categoryId) {
        currentPresetCategoryId = categoryId;
        document.getElementById('item-modal-title').textContent = '<?php echo t('add_item'); ?>';
        resetModalForm();
        document.getElementById('item-modal').style.display = 'flex';
        loadCategoriesForDropdown();
    }

    function editItem(id) {
        fetch(`api/get_menu_item.php?id=${id}`)
            .then(response => response.json())
            .then(item => {
                document.getElementById('item-modal-title').textContent = '<?php echo t('edit_item'); ?>';
                document.getElementById('item-id').value = item.id;
                document.getElementById('item-name').value = item.name;
                document.getElementById('item-price').value = item.price;
                document.getElementById('item-description').value = item.description || '';
                document.getElementById('item-prep-time').value = item.preparation_time || 10;
                document.getElementById('item-sort').value = item.sort_order || 0;
                document.getElementById('print-on-controller').checked = (item.print_on_controller == 1);
                document.getElementById('item-available').checked = (item.is_available == 1);
                document.getElementById('item-modal').style.display = 'flex';
                loadCategoriesForDropdown();
                setTimeout(() => {
                    document.getElementById('item-category').value = item.category_id;
                }, 100);
            });
    }

    function saveItem(keepOpen = false) {
        const data = {
            id: document.getElementById('item-id').value,
            name: document.getElementById('item-name').value,
            category_id: document.getElementById('item-category').value,
            price: document.getElementById('item-price').value,
            description: document.getElementById('item-description').value,
            preparation_time: document.getElementById('item-prep-time').value,
            sort_order: document.getElementById('item-sort').value,
            print_on_controller: document.getElementById('print-on-controller').checked ? 1 : 0,
            is_available: document.getElementById('item-available').checked ? 1 : 0
        };
        showLoading();
        fetch('api/save_menu_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('<?php echo t('item_saved'); ?>', 'success');
                loadMenuTree();
                if (keepOpen) {
                    const currentCategory = document.getElementById('item-category').value;
                    resetModalForm();
                    if (currentCategory) {
                        document.getElementById('item-category').value = currentCategory;
                    }
                    document.getElementById('item-name').focus();
                } else {
                    closeModal();
                }
            } else {
                showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
            }
        });
    }

    function deleteItem(id) {
        if (confirm('<?php echo t('confirm_delete_item'); ?>')) {
            showLoading();
            fetch(`api/delete_menu_item.php?id=${id}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showToast('<?php echo t('item_deleted'); ?>', 'success');
                        loadMenuTree();
                    } else {
                        showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
                    }
                });
        }
    }

    function closeModal() {
        document.getElementById('item-modal').style.display = 'none';
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    function showLoading() {
        let overlay = document.getElementById('loading-overlay');
        if (overlay) return;
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center';
        overlay.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(overlay);
    }

    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.remove();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('item-search');
        const clearBtn = document.getElementById('clear-search');
        if (searchInput && clearBtn) {
            searchInput.addEventListener('input', function() {
                const term = this.value.trim().toLowerCase();
                clearBtn.style.display = this.value ? 'flex' : 'none';
                filterItems(term);
            });
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearBtn.style.display = 'none';
                filterItems('');
                searchInput.focus();
            });
        }
        loadMenuTree();
    });
</script>

<?php include 'layout/sidebar_footer.php'; ?>