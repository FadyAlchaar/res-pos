<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('kitchens'); ?></h1>
    <p class="page-description"><?php echo t('kitchens_description'); ?></p>
</div>

<div class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('kitchen_list'); ?></h3>
            <button class="btn btn-primary" onclick="openKitchenModal()">+ <?php echo t('add_kitchen'); ?></button>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo t('kitchen_name'); ?></th>
                        <th><?php echo t('printer_ip'); ?></th>
                        <th><?php echo t('port'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('last_checked'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="kitchens-list">
                    <tr><td colspan="6" style="text-align: center;"><?php echo t('loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Kitchen Modal -->
<div id="kitchen-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title"><?php echo t('add_kitchen'); ?></h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="kitchen-form">
                <input type="hidden" id="kitchen-id" name="id">
                <div class="form-group">
                    <label><?php echo t('kitchen_name'); ?> *</label>
                    <input type="text" id="kitchen-name" name="name" required>
                </div>
                
                <!-- Windows Printer Fields (main printer) -->
                <div class="form-group">
                    <label><?php echo t('windows_printer_name'); ?></label>
                    <select id="printer-name" name="printer_name">
                        <option value="">-- <?php echo t('select_printer'); ?> --</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-info" onclick="refreshPrinters()">🔄 <?php echo t('refresh_printers'); ?></button>
                    <small><?php echo t('windows_printer_hint'); ?></small>
                </div>
                
                <!-- Printer IP for reachability check (fallback) -->
                <div class="form-group">
                    <label><?php echo t('printer_ip'); ?></label>
                    <input type="text" id="printer-ip" name="printer_ip" placeholder="192.168.1.100">
                    <small><?php echo t('printer_ip_hint'); ?></small>
                </div>
                <div class="form-group">
                    <label><?php echo t('port'); ?></label>
                    <input type="number" id="printer-port" name="printer_port" value="9100">
                </div>
                
                <!-- Fallback Printer (optional) -->
                <div class="form-group">
                    <label><?php echo t('fallback_printer'); ?></label>
                    <select id="fallback-printer-name" name="fallback_printer_name">
                        <option value="">-- <?php echo t('none'); ?> --</option>
                    </select>
                    <small><?php echo t('fallback_printer_hint'); ?></small>
                </div>
                
                <div class="form-group">
                    <label><?php echo t('notes'); ?></label>
                    <textarea id="kitchen-notes" name="notes" rows="3" placeholder="<?php echo t('notes_placeholder'); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="saveKitchen()"><?php echo t('save'); ?></button>
            <button class="btn btn-danger" onclick="closeModal()"><?php echo t('cancel'); ?></button>
        </div>
    </div>
</div>

<script>
let kitchens = [];

function loadKitchens() {
    fetch('api/get_kitchens.php')
        .then(response => response.json())
        .then(data => {
            kitchens = data;
            const tbody = document.getElementById('kitchens-list');
            if (data.error) {
                tbody.innerHTML = `<tr><td colspan="6" style="color: red;">${data.error}</td></tr>`;
                return;
            }
            tbody.innerHTML = data.map(kitchen => `
                <tr>
                    <td><strong>${kitchen.name}</strong><br><small>${kitchen.notes || ''}</small></td>
                    <td>${kitchen.printer_ip || 'Not configured'}</td>
                    <td>${kitchen.printer_port || 9100}</td>
                    <td><span class="badge ${kitchen.status === 'online' ? 'badge-success' : 'badge-danger'}">${kitchen.status === 'online' ? '🟢 Online' : '🔴 Offline'}</span></td>
                    <td>${kitchen.last_checked ? new Date(kitchen.last_checked).toLocaleString() : 'Never'}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editKitchen(${kitchen.id})">Edit</button>
                        <button class="btn btn-primary btn-sm" onclick="testPrinter(${kitchen.id})">Test</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteKitchen(${kitchen.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('kitchens-list').innerHTML = '<tr><td colspan="6" style="color: red;">Failed to load kitchens</td></tr>';
        });
}

function openKitchenModal() {
    document.getElementById('modal-title').textContent = 'Add Kitchen';
    document.getElementById('kitchen-form').reset();
    document.getElementById('kitchen-id').value = '';
    loadWindowsPrinters();
    loadFallbackPrinters();
    document.getElementById('kitchen-modal').style.display = 'flex';
}

function loadWindowsPrinters() {
    fetch('api/get_windows_printers.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('printer-name');
            if (data.error) {
                select.innerHTML = '<option value="">Error loading printers</option>';
                return;
            }
            select.innerHTML = '<option value="">-- Select Printer --</option>';
            data.forEach(printer => {
                const escapedName = printer.name.replace(/"/g, '&quot;');
                select.innerHTML += `<option value="${escapedName}">${printer.name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading printers:', error);
            document.getElementById('printer-name').innerHTML = '<option value="">Error loading printers</option>';
        });
}

function loadFallbackPrinters() {
    fetch('api/get_windows_printers.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('fallback-printer-name');
            if (data.error) {
                select.innerHTML = '<option value="">Error loading printers</option>';
                return;
            }
            select.innerHTML = '<option value="">-- None --</option>';
            data.forEach(printer => {
                const escapedName = printer.name.replace(/"/g, '&quot;');
                select.innerHTML += `<option value="${escapedName}">${printer.name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading fallback printers:', error);
            document.getElementById('fallback-printer-name').innerHTML = '<option value="">Error loading printers</option>';
        });
}

function refreshPrinters() {
    loadWindowsPrinters();
    loadFallbackPrinters();
    showToast('Printer list refreshed', 'success');
}

function editKitchen(id) {
    fetch(`api/get_kitchen.php?id=${id}`)
        .then(response => response.json())
        .then(kitchen => {
            document.getElementById('modal-title').textContent = 'Edit Kitchen';
            document.getElementById('kitchen-id').value = kitchen.id;
            document.getElementById('kitchen-name').value = kitchen.name;
            document.getElementById('printer-ip').value = kitchen.printer_ip || '';
            document.getElementById('printer-port').value = kitchen.printer_port || 9100;
            document.getElementById('kitchen-notes').value = kitchen.notes || '';
            
            // Wait for printers to load then set values
            setTimeout(() => {
                if (kitchen.printer_name) {
                    document.getElementById('printer-name').value = kitchen.printer_name;
                }
                if (kitchen.fallback_printer_name) {
                    document.getElementById('fallback-printer-name').value = kitchen.fallback_printer_name;
                }
            }, 300);
            
            document.getElementById('kitchen-modal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error loading kitchen:', error);
            showToast('Failed to load kitchen details', 'error');
        });
}

function saveKitchen() {
    const id = document.getElementById('kitchen-id').value;
    const name = document.getElementById('kitchen-name').value;
    const printerName = document.getElementById('printer-name').value;
    const printerIp = document.getElementById('printer-ip').value;
    const printerPort = document.getElementById('printer-port').value;
    const fallbackPrinterName = document.getElementById('fallback-printer-name').value;
    
    if (!name) {
        showToast('Kitchen name is required', 'error');
        return;
    }
    if (!printerName) {
        showToast('Please select a printer', 'error');
        return;
    }
    
    const data = {
        id: id,
        name: name,
        printer_type: 'windows',
        printer_name: printerName,
        printer_ip: printerIp || null,
        printer_port: printerPort || 9100,
        fallback_printer_name: fallbackPrinterName || null,
        notes: document.getElementById('kitchen-notes').value
    };
    
    showLoading();
    fetch('api/save_kitchen.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            closeModal();
            loadKitchens();
            showToast('Kitchen saved successfully', 'success');
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('Failed to save kitchen', 'error');
    });
}

function testPrinter(kitchenId) {
    showToast('Testing printer...', 'info');
    fetch(`api/test_printer.php?kitchen_id=${kitchenId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`Printer test: ${data.status} - ${data.message}`, data.status === 'online' ? 'success' : 'warning');
                loadKitchens();
            } else {
                showToast('Test failed: ' + data.message, 'error');
            }
        });
}

function deleteKitchen(id) {
    if (confirm('Are you sure you want to delete this kitchen? This will also delete all associated categories.')) {
        fetch(`api/delete_kitchen.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadKitchens();
                    showToast('Kitchen deleted', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
    }
}

function closeModal() {
    document.getElementById('kitchen-modal').style.display = 'none';
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
    overlay.innerHTML = '<div style="background:white;padding:20px;border-radius:10px;">⏳ Loading...</div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.remove();
}

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    loadKitchens();
});
</script>

<?php include 'layout/sidebar_footer.php'; ?>