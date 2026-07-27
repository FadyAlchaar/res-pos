<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('restaurant_settings'); ?></h1>
    <p class="page-description"><?php echo t('configure_restaurant'); ?></p>
</div>

<div class="page-content">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
        
        <!-- General Settings -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo t('general_settings'); ?></h3>
            </div>
            <div class="card-body">
                <form id="settings-form">
                    <div class="form-group">
                        <label><?php echo t('restaurant_name'); ?></label>
                        <input type="text" id="restaurant-name" name="restaurant_name" placeholder="<?php echo t('restaurant_name'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('total_tables'); ?></label>
                        <input type="number" id="total-tables" name="total_tables" min="1" max="500">
                        <small style="color: #64748b;"><?php echo t('changing_regenerates'); ?></small>
                    </div>
                    <div class="form-group">
                        <label><?php echo t('table_prefix'); ?></label>
                        <input type="text" id="table-prefix" name="table_prefix" placeholder="<?php echo t('table_prefix_placeholder'); ?>">
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary" onclick="saveSettings()"><?php echo t('save_settings'); ?></button>
                        <button type="button" class="btn btn-warning" onclick="regenerateTables()"><?php echo t('regenerate_tables'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Current Status -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo t('system_status'); ?></h3>
            </div>
            <div class="card-body">
                <div id="system-status">
                    <?php echo t('loading'); ?>
                </div>
            </div>
        </div>
        
        <!-- Table Preview -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo t('table_preview'); ?></h3>
            </div>
            <div class="card-body">
                <div id="table-preview" style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 300px; overflow-y: auto;">
                    <?php echo t('loading'); ?>
                </div>
                <p style="margin-top: 10px; font-size: 0.7rem; color: #64748b;"><?php echo t('click_table_to_toggle_reserved'); ?></p>
            </div>
        </div>
        
        <!-- Accountant Printer Settings -->
        <div class="card">
            <div class="card-header">
                <h3>🧾 <?php echo t('accountant_printer'); ?></h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><?php echo t('enable_printing'); ?></label>
                    <select id="accountant-print-enabled">
                        <option value="1"><?php echo t('yes'); ?></option>
                        <option value="0"><?php echo t('no'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php echo t('printer_type'); ?></label>
                    <select id="accountant-printer-type" onchange="toggleAccountantPrinterFields()">
                        <option value="windows"><?php echo t('windows_printer'); ?></option>
                        <option value="network"><?php echo t('network_printer'); ?></option>
                    </select>
                </div>
                
                <div id="accountant-network-fields">
                    <div class="form-group">
                        <label><?php echo t('printer_ip'); ?></label>
                        <input type="text" id="accountant-printer-ip" placeholder="192.168.1.100">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('port'); ?></label>
                        <input type="number" id="accountant-printer-port" value="9100">
                    </div>
                </div>
                
                <div id="accountant-windows-fields" style="display: none;">
                    <div class="form-group">
                        <label><?php echo t('windows_printer_name'); ?></label>
                        <select id="accountant-printer-name">
                            <option value="">-- <?php echo t('select_printer'); ?> --</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-info" onclick="loadWindowsPrinters()">🔄 <?php echo t('refresh_printers'); ?></button>
                    </div>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="saveAccountantSettings()"><?php echo t('save_accountant_settings'); ?></button>
            </div>
        </div>
        
        <!-- Controller Printer Settings -->
        <div class="card">
            <div class="card-header">
                <h3>🎛️ <?php echo t('controller_printer'); ?></h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><?php echo t('enable_printing'); ?></label>
                    <select id="controller-print-enabled">
                        <option value="1"><?php echo t('yes'); ?></option>
                        <option value="0"><?php echo t('no'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo t('windows_printer_name'); ?></label>
                    <select id="controller-printer-name">
                        <option value="">-- <?php echo t('disable'); ?> --</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-info" onclick="loadControllerPrinters()">🔄 <?php echo t('refresh_printers'); ?></button>
                    <small><?php echo t('controller_printer_hint'); ?></small>
                </div>
                <button type="button" class="btn btn-primary" onclick="saveControllerSettings()"><?php echo t('save_controller_settings'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Reserved Table Modal -->
<div id="reserved-modal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 id="reserved-modal-title"><?php echo t('reserved_table'); ?></h3>
            <button class="close-modal" onclick="closeReservedModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="reserved-table-name"></p>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="reserved-toggle">
                    <?php echo t('is_reserved'); ?>
                </label>
                <small><?php echo t('reserved_table_hint'); ?></small>
            </div>
            <div class="form-group" id="reserved-for-group" style="display: none;">
                <label><?php echo t('reserved_for'); ?></label>
                <input type="text" id="reserved-for" class="form-control" placeholder="<?php echo t('reserved_for_placeholder'); ?>">
                <small><?php echo t('reserved_for_hint'); ?></small>
            </div>
            <div class="form-group">
                <label><?php echo t('customer_name'); ?></label>
                <input type="text" id="customer-name" class="form-control" placeholder="<?php echo t('customer_name_placeholder'); ?>">
                <small><?php echo t('customer_name_hint'); ?></small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="saveReservedStatus()"><?php echo t('save'); ?></button>
            <button class="btn btn-danger" onclick="closeReservedModal()"><?php echo t('cancel'); ?></button>
        </div>
    </div>
</div>

<style>
    .table-badge-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .table-badge-item:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .table-badge-item.reserved {
        background: #f39c12;
        color: white;
    }
    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }
    .badge-warning {
        background: #fee2e2;
        color: #991b1b;
    }
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
</style>

<script>
let currentReservedTableId = null;
let currentReservedTableName = null;

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

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function openReservedModal(tableId, tableName, isReserved, reservedFor, customerName) {
    currentReservedTableId = tableId;
    currentReservedTableName = tableName;
    document.getElementById('reserved-table-name').innerHTML = `<strong>${escapeHtml(tableName)}</strong>`;
    document.getElementById('reserved-toggle').checked = isReserved == 1;
    document.getElementById('reserved-for').value = reservedFor || '';
    document.getElementById('customer-name').value = customerName || '';
    const group = document.getElementById('reserved-for-group');
    group.style.display = (isReserved == 1) ? 'block' : 'none';
    document.getElementById('reserved-modal').style.display = 'flex';
}

function closeReservedModal() {
    document.getElementById('reserved-modal').style.display = 'none';
    currentReservedTableId = null;
}

function saveReservedStatus() {
    if (!currentReservedTableId) return;
    const isReserved = document.getElementById('reserved-toggle').checked ? 1 : 0;
    const reservedFor = document.getElementById('reserved-for').value.trim();
    const customerName = document.getElementById('customer-name').value.trim();
    
    showLoading();
    fetch('api/update_table_reserved.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            table_id: currentReservedTableId,
            is_reserved: isReserved,
            reserved_for: reservedFor,
            customer_name: customerName
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('<?php echo t('settings_saved'); ?>', 'success');
            closeReservedModal();
            loadSettings();
        } else {
            showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('<?php echo t('error'); ?>', 'error');
    });
}

function loadSettings() {
    fetch('api/get_settings.php')
        .then(response => response.json())
        .then(data => {
            console.log('Settings loaded:', data);
            
            // General
            document.getElementById('restaurant-name').value = data.restaurant_name || 'My Restaurant';
            document.getElementById('total-tables').value = data.total_tables || 60;
            document.getElementById('table-prefix').value = data.table_prefix || 'Table ';
            
            // Accountant
            document.getElementById('accountant-print-enabled').value = data.accountant_print_enabled || '1';
            document.getElementById('accountant-printer-type').value = data.accountant_printer_type || 'windows';
            toggleAccountantPrinterFields();
            
            // Load printers and set values after dropdown is ready
            loadWindowsPrinters().then(() => {
                document.getElementById('accountant-printer-name').value = data.accountant_printer_name || '';
            });
            
            // Controller
            document.getElementById('controller-print-enabled').value = data.controller_print_enabled ? '1' : '0';
            loadControllerPrinters().then(() => {
                document.getElementById('controller-printer-name').value = data.controller_printer_name || '';
            });
            
            // Status
            const statusHtml = `
                <div style="margin-bottom: 12px;">
                    <strong><?php echo t('active_tables'); ?>:</strong> ${data.active_tables || 0}
                </div>
                <div style="margin-bottom: 12px;">
                    <strong><?php echo t('today_orders'); ?>:</strong> ${data.today_orders || 0}
                </div>
                <div style="margin-bottom: 12px;">
                    <strong><?php echo t('active_printers'); ?>:</strong> ${data.online_printers || 0} / ${data.total_printers || 0}
                </div>
                <div>
                    <strong><?php echo t('last_updated'); ?>:</strong> ${new Date().toLocaleString()}
                </div>
            `;
            document.getElementById('system-status').innerHTML = statusHtml;
            
            // Table preview with customer_name
            if (data.tables && data.tables.length) {
                const previewHtml = data.tables.map(t => `
                    <span class="table-badge-item ${t.status === 'available' ? 'badge-success' : 'badge-warning'} ${t.is_reserved ? 'reserved' : ''}" 
                          data-id="${t.id}" 
                          data-name="${escapeHtml(t.table_name || 'Table ' + t.table_number)}"
                          data-reserved="${t.is_reserved}"
                          data-reserved-for="${escapeHtml(t.reserved_for || '')}"
                          data-customer-name="${escapeHtml(t.customer_name || '')}"
                          onclick="openReservedModal(${t.id}, '${escapeHtml(t.table_name || 'Table ' + t.table_number)}', ${t.is_reserved}, '${escapeHtml(t.reserved_for || '')}', '${escapeHtml(t.customer_name || '')}')">
                        ${escapeHtml(t.table_name || 'Table ' + t.table_number)}
                        ${t.is_reserved ? ' 👑' : ''}
                    </span>
                `).join('');
                document.getElementById('table-preview').innerHTML = previewHtml;
            } else {
                document.getElementById('table-preview').innerHTML = '<div style="text-align: center; color: #64748b;"><?php echo t('no_tables'); ?></div>';
            }
        })
        .catch(error => {
            console.error('Error loading settings:', error);
            document.getElementById('system-status').innerHTML = '<div style="color: red;">Failed to load settings</div>';
        });
}

function loadWindowsPrinters() {
    return fetch('api/get_windows_printers.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('accountant-printer-name');
            if (data.error || !Array.isArray(data)) {
                select.innerHTML = '<option value=""><?php echo t('error'); ?></option>';
                return;
            }
            select.innerHTML = '<option value="">-- <?php echo t('select_printer'); ?> --</option>';
            data.forEach(printer => {
                select.innerHTML += `<option value="${printer.name.replace(/"/g, '&quot;')}">${printer.name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading printers:', error);
            document.getElementById('accountant-printer-name').innerHTML = '<option value="">Error loading printers</option>';
        });
}

function loadControllerPrinters() {
    return fetch('api/get_windows_printers.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('controller-printer-name');
            if (data.error || !Array.isArray(data)) {
                select.innerHTML = '<option value=""><?php echo t('error'); ?></option>';
                return;
            }
            select.innerHTML = '<option value="">-- <?php echo t('disable'); ?> --</option>';
            data.forEach(printer => {
                select.innerHTML += `<option value="${printer.name.replace(/"/g, '&quot;')}">${printer.name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading printers:', error);
            document.getElementById('controller-printer-name').innerHTML = '<option value="">Error loading printers</option>';
        });
}

function toggleAccountantPrinterFields() {
    const type = document.getElementById('accountant-printer-type').value;
    if (type === 'network') {
        document.getElementById('accountant-network-fields').style.display = 'block';
        document.getElementById('accountant-windows-fields').style.display = 'none';
    } else {
        document.getElementById('accountant-network-fields').style.display = 'none';
        document.getElementById('accountant-windows-fields').style.display = 'block';
        loadWindowsPrinters();
    }
}

function saveSettings() {
    const data = {
        restaurant_name: document.getElementById('restaurant-name').value,
        total_tables: document.getElementById('total-tables').value,
        table_prefix: document.getElementById('table-prefix').value
    };
    
    showLoading();
    fetch('api/save_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('<?php echo t('settings_saved'); ?>', 'success');
            loadSettings();
        } else {
            showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('<?php echo t('error_saving'); ?>', 'error');
    });
}

function regenerateTables() {
    if (confirm('<?php echo t('confirm_regenerate'); ?>')) {
        showLoading();
        fetch('api/regenerate_tables.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast('<?php echo t('tables_regenerated'); ?>', 'success');
                    loadSettings();
                } else {
                    showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
                }
            });
    }
}

function saveAccountantSettings() {
    const data = {
        accountant_print_enabled: document.getElementById('accountant-print-enabled').value,
        accountant_printer_type: document.getElementById('accountant-printer-type').value,
    };
    if (data.accountant_printer_type === 'windows') {
        data.accountant_printer_name = document.getElementById('accountant-printer-name').value;
    } else {
        data.accountant_printer_ip = document.getElementById('accountant-printer-ip').value;
        data.accountant_printer_port = document.getElementById('accountant-printer-port').value;
    }
    showLoading();
    fetch('api/save_accountant_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('<?php echo t('accountant_saved'); ?>', 'success');
        } else {
            showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
        }
    });
}

function saveControllerSettings() {
    const data = {
        controller_print_enabled: document.getElementById('controller-print-enabled').value,
        controller_printer_name: document.getElementById('controller-printer-name').value
    };
    showLoading();
    fetch('api/save_controller_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('<?php echo t('controller_saved'); ?>', 'success');
        } else {
            showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
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

// Add event listener for reserved toggle visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('reserved-toggle');
    if (toggle) {
        toggle.addEventListener('change', function() {
            const group = document.getElementById('reserved-for-group');
            if (group) group.style.display = this.checked ? 'block' : 'none';
        });
    }
    loadSettings();
});
</script>

<?php include 'layout/sidebar_footer.php'; ?>