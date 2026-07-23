<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('orders'); ?></h1>
    <p class="page-description"><?php echo t('orders_description'); ?></p>
</div>

<div class="page-content">
    <!-- Filters -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3><?php echo t('filter_orders'); ?></h3>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('date_range'); ?></label>
                    <select id="filter-date" onchange="loadOrders()">
                        <option value="today"><?php echo t('today'); ?></option>
                        <option value="yesterday"><?php echo t('yesterday'); ?></option>
                        <option value="week"><?php echo t('this_week'); ?></option>
                        <option value="month"><?php echo t('this_month'); ?></option>
                        <option value="all"><?php echo t('all_time'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('status'); ?></label>
                    <select id="filter-status" onchange="loadOrders()">
                        <option value="all"><?php echo t('all_status'); ?></option>
                        <option value="pending"><?php echo t('pending'); ?></option>
                        <option value="preparing"><?php echo t('preparing'); ?></option>
                        <option value="ready"><?php echo t('ready'); ?></option>
                        <option value="delivered"><?php echo t('delivered'); ?></option>
                        <option value="cancelled"><?php echo t('cancelled'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('waiter'); ?></label>
                    <select id="filter-waiter" onchange="loadOrders()">
                        <option value="all"><?php echo t('all_waiters'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('table'); ?></label>
                    <input type="text" id="filter-table" placeholder="<?php echo t('table_number'); ?>" onkeyup="loadOrders()" style="width: 100px;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button class="btn btn-primary" onclick="resetFilters()"><?php echo t('reset'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('orders_list'); ?></h3>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span id="order-count" style="background: #e9ecef; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">0 <?php echo t('orders'); ?></span>
                <button class="btn btn-primary btn-sm" onclick="exportOrders()">📎 <?php echo t('export_csv'); ?></button>
                <button class="btn btn-info btn-sm" id="manual-refresh-btn" onclick="manualRefresh()" style="background: #17a2b8;">🔄 <?php echo t('refresh_now'); ?></button>
                <div class="auto-refresh-toggle" style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 0.7rem; color: #64748b;">⏱️ <?php echo t('auto_refresh'); ?></span>
                    <label class="switch">
                        <input type="checkbox" id="auto-refresh-checkbox" checked>
                        <span class="slider round"></span>
                    </label>
                    <span id="refresh-status" style="font-size: 0.7rem; color: #27ae60;">ON</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div style="overflow-x: auto;">
                <table class="data-table" id="orders-table">
                    <thead>
                        <tr>
                            <th><?php echo t('order_number'); ?></th>
                            <th><?php echo t('date_time'); ?></th>
                            <th><?php echo t('table'); ?></th>
                            <th><?php echo t('waiter'); ?></th>
                            <th><?php echo t('items'); ?></th>
                            <th><?php echo t('total'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('payment_status'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="orders-list">
                        <tr><td colspan="9" style="text-align: center;"><?php echo t('loading'); ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div id="pagination-info" style="color: #64748b; font-size: 0.8rem;"></div>
                <div id="pagination-buttons" style="display: flex; gap: 8px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modal-title"><?php echo t('order_details'); ?></h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modal-body">
            <?php echo t('loading'); ?>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="updateOrderStatus()"><?php echo t('update_status'); ?></button>
            <button class="btn btn-danger" onclick="closeModal()"><?php echo t('close'); ?></button>
        </div>
    </div>
</div>

<style>
/* Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: 0.3s;
}

input:checked + .slider {
    background-color: #27ae60;
}

input:focus + .slider {
    box-shadow: 0 0 1px #27ae60;
}

input:checked + .slider:before {
    transform: translateX(20px);
}

.slider.round {
    border-radius: 20px;
}

.slider.round:before {
    border-radius: 50%;
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    z-index: 1000;
    animation: slideIn 0.3s;
}

.toast-success { background: #27ae60; }
.toast-error { background: #e74c3c; }
.toast-warning { background: #f39c12; }
.toast-info { background: #17a2b8; }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<script>
// ============================================
// GLOBAL VARIABLES
// ============================================
let currentPage = 1;
let totalPages = 1;
let currentOrderId = null;
let autoRefreshEnabled = true;
let refreshInterval = null;
let lastOrderCount = 0;

// Translations
const statusOptions = {
    pending: '<?php echo t('pending'); ?>',
    preparing: '<?php echo t('preparing'); ?>',
    ready: '<?php echo t('ready'); ?>',
    delivered: '<?php echo t('delivered'); ?>',
    cancelled: '<?php echo t('cancelled'); ?>'
};

const paymentOptions = {
    unpaid: '<?php echo t('unpaid'); ?>',
    paid: '<?php echo t('paid'); ?>',
    refunded: '<?php echo t('refunded'); ?>'
};

// ============================================
// AUTO-REFRESH FUNCTIONS
// ============================================

function startAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    refreshInterval = setInterval(function() {
        if (autoRefreshEnabled && document.visibilityState === 'visible') {
            checkForNewOrders();
        }
    }, 10000);
}

function checkForNewOrders() {
    const date = document.getElementById('filter-date').value;
    const status = document.getElementById('filter-status').value;
    const waiter = document.getElementById('filter-waiter').value;
    const table = document.getElementById('filter-table').value;
    
    fetch('api/get_orders_count.php?date=' + date + '&status=' + status + '&waiter=' + waiter + '&table=' + table)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.total_orders !== undefined && data.total_orders !== lastOrderCount) {
                if (data.total_orders > lastOrderCount) {
                    var newCount = data.total_orders - lastOrderCount;
                    showToast('🔔 ' + newCount + ' new order(s) received!', 'success');
                    playNotificationSound();
                }
                lastOrderCount = data.total_orders;
                loadOrders();
            }
        })
        .catch(function(error) {
            console.error('Error checking for new orders:', error);
        });
}

function playNotificationSound() {
    try {
        var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var oscillator = audioCtx.createOscillator();
        var gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.frequency.value = 880;
        gainNode.gain.value = 0.3;
        
        oscillator.start();
        gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.5);
        oscillator.stop(audioCtx.currentTime + 0.5);
    } catch(e) {
        // Audio not supported
    }
}

function toggleAutoRefresh() {
    autoRefreshEnabled = document.getElementById('auto-refresh-checkbox').checked;
    var statusSpan = document.getElementById('refresh-status');
    if (autoRefreshEnabled) {
        statusSpan.textContent = 'ON';
        statusSpan.style.color = '#27ae60';
        showToast('<?php echo t('auto_refresh_on'); ?>', 'success');
        checkForNewOrders();
    } else {
        statusSpan.textContent = 'OFF';
        statusSpan.style.color = '#e74c3c';
        showToast('<?php echo t('auto_refresh_off'); ?>', 'warning');
    }
}

function manualRefresh() {
    showToast('<?php echo t('refreshing_orders'); ?>', 'info');
    loadOrders();
}

// ============================================
// LOAD ORDERS
// ============================================
function loadOrders() {
    const date = document.getElementById('filter-date').value;
    const status = document.getElementById('filter-status').value;
    const waiter = document.getElementById('filter-waiter').value;
    const table = document.getElementById('filter-table').value;
    
    fetch('api/get_orders.php?page=' + currentPage + '&date=' + date + '&status=' + status + '&waiter=' + waiter + '&table=' + table)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.error) {
                document.getElementById('orders-list').innerHTML = '<tr><td colspan="9" style="color: red;">' + data.error + '</td></tr>';
                return;
            }
            
            displayOrders(data.orders);
            updatePagination(data);
            document.getElementById('order-count').textContent = data.total_orders + ' <?php echo t('orders'); ?>';
            lastOrderCount = data.total_orders;
        })
        .catch(function(error) {
            console.error('Error:', error);
            document.getElementById('orders-list').innerHTML = '<tr><td colspan="9" style="color: red;"><?php echo t('error_loading_orders'); ?></td></tr>';
        });
}

function displayOrders(orders) {
    var tbody = document.getElementById('orders-list');
    
    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center;"><?php echo t('no_orders'); ?></td></tr>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < orders.length; i++) {
        var order = orders[i];
        var dateStr = new Date(order.created_at).toLocaleString();
        var totalAmount = parseFloat(order.total_amount).toFixed(2);
        var paidClass = (order.payment_status === 'paid') ? 'badge-success' : 'badge-warning';
        var paidText = (order.payment_status === 'paid') ? paymentOptions.paid : paymentOptions.unpaid;
        
        html += '<tr>';
        html += '<td><strong>' + order.order_number + '</strong></td>';
        html += '<td>' + dateStr + '</td>';
        html += '<td>' + (order.table_number || 'N/A') + '</td>';
        html += '<td>' + order.waiter_name + '</td>';
        html += '<td>' + order.item_count + '</td>';
        html += '<td><strong style="color: #27ae60;">$' + totalAmount + '</strong></td>';
        html += '<td>';
        html += '<select class="status-select" data-order-id="' + order.id + '" onchange="updateOrderStatusFromSelect(this)" style="padding: 4px 8px; border-radius: 20px; font-size: 0.75rem; border: 1px solid #ddd;">';
        html += '<option value="pending"' + (order.status === 'pending' ? ' selected' : '') + '>' + statusOptions.pending + '</option>';
        html += '<option value="preparing"' + (order.status === 'preparing' ? ' selected' : '') + '>' + statusOptions.preparing + '</option>';
        html += '<option value="ready"' + (order.status === 'ready' ? ' selected' : '') + '>' + statusOptions.ready + '</option>';
        html += '<option value="delivered"' + (order.status === 'delivered' ? ' selected' : '') + '>' + statusOptions.delivered + '</option>';
        html += '<option value="cancelled"' + (order.status === 'cancelled' ? ' selected' : '') + '>' + statusOptions.cancelled + '</option>';
        html += '</select>';
        html += '</td>';
        html += '<td><span class="badge ' + paidClass + '">' + paidText + '</span></td>';
        html += '<td>';
        html += '<button class="btn btn-primary btn-sm" onclick="viewOrder(' + order.id + ')"><?php echo t('view_details'); ?></button> ';
        html += '<button class="btn btn-warning btn-sm" onclick="printReceipt(' + order.id + ')"><?php echo t('print_receipt'); ?></button>';
        html += '</td>';
        html += '</tr>';
    }
    
    tbody.innerHTML = html;
}

// ============================================
// PAGINATION
// ============================================
function updatePagination(data) {
    currentPage = data.current_page;
    totalPages = data.total_pages;
    
    var info = document.getElementById('pagination-info');
    info.textContent = '<?php echo t('showing'); ?> ' + (data.from || 0) + ' <?php echo t('to'); ?> ' + (data.to || 0) + ' <?php echo t('of'); ?> ' + data.total_orders + ' <?php echo t('orders'); ?>';
    
    var buttons = document.getElementById('pagination-buttons');
    var html = '';
    
    if (currentPage > 1) {
        html += '<button class="btn btn-sm" onclick="goToPage(' + (currentPage - 1) + ')">← <?php echo t('previous'); ?></button>';
    }
    
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        html += '<button class="btn btn-sm" onclick="goToPage(1)">1</button>';
        if (startPage > 2) html += '<span style="padding: 0 5px;">...</span>';
    }
    
    for (var i = startPage; i <= endPage; i++) {
        var activeClass = (i === currentPage) ? 'btn-primary' : '';
        html += '<button class="btn btn-sm ' + activeClass + '" onclick="goToPage(' + i + ')">' + i + '</button>';
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span style="padding: 0 5px;">...</span>';
        html += '<button class="btn btn-sm" onclick="goToPage(' + totalPages + ')">' + totalPages + '</button>';
    }
    
    if (currentPage < totalPages) {
        html += '<button class="btn btn-sm" onclick="goToPage(' + (currentPage + 1) + ')"><?php echo t('next'); ?> →</button>';
    }
    
    buttons.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadOrders();
}

// ============================================
// LOAD WAITERS
// ============================================
function loadWaiters() {
    fetch('api/get_waiters.php')
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            var select = document.getElementById('filter-waiter');
            if (data && data.length) {
                var html = '<option value="all"><?php echo t('all_waiters'); ?></option>';
                for (var i = 0; i < data.length; i++) {
                    html += '<option value="' + data[i].id + '">' + data[i].full_name + '</option>';
                }
                select.innerHTML = html;
            }
        })
        .catch(function(error) {
            console.error('Error loading waiters:', error);
        });
}

// ============================================
// ORDER DETAILS
// ============================================
function viewOrder(orderId) {
    currentOrderId = orderId;
    
    fetch('api/get_order_details.php?id=' + orderId)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.error) {
                document.getElementById('modal-body').innerHTML = '<div style="color: red;">' + data.error + '</div>';
                return;
            }
            
            displayOrderDetails(data);
            document.getElementById('order-modal').style.display = 'flex';
        })
        .catch(function(error) {
            console.error('Error:', error);
            document.getElementById('modal-body').innerHTML = '<div style="color: red;"><?php echo t('error_loading_orders'); ?></div>';
        });
}

function displayOrderDetails(order) {
    // Group items by kitchen
    var itemsByKitchen = {};
    for (var i = 0; i < (order.items || []).length; i++) {
        var item = order.items[i];
        var kitchenId = item.kitchen_id || 1;
        var kitchenName = item.kitchen_name || 'Main Kitchen';
        if (!itemsByKitchen[kitchenId]) {
            itemsByKitchen[kitchenId] = {
                id: kitchenId,
                name: kitchenName,
                items: []
            };
        }
        itemsByKitchen[kitchenId].items.push(item);
    }
    
    // Create buttons for kitchens that have items
    var kitchenButtonsHtml = '';
    var kitchenIds = Object.keys(itemsByKitchen);
    for (var i = 0; i < kitchenIds.length; i++) {
        var kitchenId = kitchenIds[i];
        var kitchen = itemsByKitchen[kitchenId];
        kitchenButtonsHtml += '<button class="btn btn-warning btn-sm" onclick="printToKitchen(' + order.id + ', ' + kitchen.id + ')" style="margin-right: 8px;">';
        kitchenButtonsHtml += '🖨️ <?php echo t('print_to'); ?> ' + kitchen.name;
        kitchenButtonsHtml += '</button>';
    }
    
    var itemsHtml = '';
    for (var i = 0; i < (order.items || []).length; i++) {
        var item = order.items[i];
        itemsHtml += '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef;">';
        itemsHtml += '<div><strong>' + item.quantity + 'x ' + item.name + '</strong>';
        if (item.notes) {
            itemsHtml += '<br><small style="color: #f39c12;">📝 ' + item.notes + '</small>';
        }
        itemsHtml += '<br><small><?php echo t('kitchen'); ?>: ' + (item.kitchen_name || 'Main Kitchen') + '</small></div>';
        itemsHtml += '<div style="color: #27ae60;">$' + parseFloat(item.subtotal).toFixed(2) + '</div>';
        itemsHtml += '</div>';
    }
    
    var html = '';
    html += '<div style="margin-bottom: 20px;">';
    html += '<p><strong><?php echo t('order_number'); ?>:</strong> ' + order.order_number + '</p>';
    html += '<p><strong><?php echo t('date_time'); ?>:</strong> ' + new Date(order.created_at).toLocaleString() + '</p>';
    html += '<p><strong><?php echo t('table'); ?>:</strong> ' + (order.table_number || 'N/A') + '</p>';
    html += '<p><strong><?php echo t('waiter'); ?>:</strong> ' + order.waiter_name + '</p>';
    html += '<p><strong><?php echo t('status'); ?>:</strong> ';
    html += '<select id="order-status-select" style="padding: 4px 8px; border-radius: 5px;">';
    html += '<option value="pending"' + (order.status === 'pending' ? ' selected' : '') + '>' + statusOptions.pending + '</option>';
    html += '<option value="preparing"' + (order.status === 'preparing' ? ' selected' : '') + '>' + statusOptions.preparing + '</option>';
    html += '<option value="ready"' + (order.status === 'ready' ? ' selected' : '') + '>' + statusOptions.ready + '</option>';
    html += '<option value="delivered"' + (order.status === 'delivered' ? ' selected' : '') + '>' + statusOptions.delivered + '</option>';
    html += '<option value="cancelled"' + (order.status === 'cancelled' ? ' selected' : '') + '>' + statusOptions.cancelled + '</option>';
    html += '</select></p>';
    html += '<p><strong><?php echo t('payment_status'); ?>:</strong> ';
    html += '<select id="payment-status-select">';
    html += '<option value="unpaid"' + (order.payment_status === 'unpaid' ? ' selected' : '') + '>' + paymentOptions.unpaid + '</option>';
    html += '<option value="paid"' + (order.payment_status === 'paid' ? ' selected' : '') + '>' + paymentOptions.paid + '</option>';
    html += '<option value="refunded"' + (order.payment_status === 'refunded' ? ' selected' : '') + '>' + paymentOptions.refunded + '</option>';
    html += '</select></p>';
    html += '</div>';
    
    html += '<div style="margin-bottom: 20px;">';
    html += '<h4><?php echo t('order_items'); ?>:</h4>';
    html += itemsHtml;
    html += '<div style="margin-top: 15px; padding-top: 10px; border-top: 2px solid #e9ecef; display: flex; justify-content: space-between; font-weight: bold;">';
    html += '<span><?php echo t('total'); ?>:</span>';
    html += '<span style="color: #27ae60;">$' + parseFloat(order.total_amount).toFixed(2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    if (order.special_instructions) {
        html += '<div><strong><?php echo t('special_instructions'); ?>:</strong><br>' + order.special_instructions + '</div>';
    }
    
    // Print Options Section
    html += '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef;">';
    html += '<h4><?php echo t('print_options'); ?>:</h4>';
    html += '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';
    html += '<button class="btn btn-success" onclick="printLocalReceipt(' + order.id + ')">';
    html += '🖨️ <?php echo t('local_print'); ?>';
    html += '</button>';
    html += kitchenButtonsHtml;
    if (Object.keys(itemsByKitchen).length > 1) {
        html += '<button class="btn btn-primary" onclick="printAllKitchens(' + order.id + ')">';
        html += '🔄 <?php echo t('print_all_kitchens'); ?>';
        html += '</button>';
    }
    html += '</div>';
    html += '</div>';
    
    document.getElementById('modal-body').innerHTML = html;
    document.getElementById('modal-title').textContent = '<?php echo t('order'); ?> ' + order.order_number;
}
// ============================================
// STATUS UPDATES
// ============================================
function updateOrderStatus() {
    var status = document.getElementById('order-status-select').value;
    var payment = document.getElementById('payment-status-select').value;
    
    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: currentOrderId,
            status: status,
            payment_status: payment
        })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            alert('<?php echo t('order_updated'); ?>');
            closeModal();
            loadOrders();
        } else {
            alert('<?php echo t('error'); ?>: ' + data.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('<?php echo t('error_updating'); ?>');
    });
}

function updateOrderStatusFromSelect(select) {
    var orderId = select.getAttribute('data-order-id');
    var newStatus = select.value;
    
    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            loadOrders();
        } else {
            alert('<?php echo t('error'); ?>: ' + data.message);
            loadOrders();
        }
    });
}

// ============================================
// PRINT FUNCTIONS
// ============================================

function printReceipt(orderId) {
    window.open('print_receipt.php?id=' + orderId, '_blank', 'width=400,height=600');
}

function printLocalReceipt(orderId) {
    showLoading();
    fetch('api/print_local_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        hideLoading();
        if (data.success) {
            window.open('print_receipt.php?id=' + orderId + '&mode=local', '_blank', 'width=400,height=600');
        } else {
            alert('<?php echo t('error'); ?>: ' + data.message);
        }
    })
    .catch(function(error) {
        hideLoading();
        console.error('Error:', error);
        alert('<?php echo t('print_failed'); ?>');
    });
}

function printToKitchen(orderId, kitchenId) {
    showLoading();
    fetch('api/print_to_kitchen.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: orderId,
            kitchen_id: kitchenId
        })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        hideLoading();
        if (data.already_printed) {
            if (confirm(data.message + '\n\nClick OK to reprint anyway, Cancel to skip.')) {
                showLoading();
                fetch('api/print_to_kitchen.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        kitchen_id: kitchenId,
                        force: true
                    })
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(retryData) {
                    hideLoading();
                    if (retryData.success) {
                        alert('✅ ' + retryData.message);
                    } else {
                        alert('❌ ' + retryData.message);
                    }
                });
            }
        } else if (data.success) {
            if (data.is_reprint) {
                alert('✅ ' + data.message);
            } else {
                alert('✅ ' + data.message + '\n' + data.items_count + ' <?php echo t('items_sent'); ?>');
            }
        } else {
            alert('❌ <?php echo t('error'); ?>: ' + data.message);
        }
    })
    .catch(function(error) {
        hideLoading();
        console.error('Error:', error);
        alert('<?php echo t('print_failed'); ?>');
    });
}

function printAllKitchens(orderId) {
    showLoading();
    fetch('api/print_to_kitchen.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, force: true })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            let msg = '✅ Printed to all kitchens';
            if (data.results) {
                data.results.forEach(r => msg += `\n${r.kitchen}: ${r.success ? 'Success' : 'Failed'}`);
            }
            alert(msg);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Print failed');
    });
}

function showLoading() {
    if (!document.getElementById('loading-overlay')) {
        var overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;justify-content:center;align-items:center;z-index:9999';
        overlay.innerHTML = '<div style="background:white;padding:20px;border-radius:10px;">⏳ <?php echo t('printing'); ?>...</div>';
        document.body.appendChild(overlay);
    } else {
        document.getElementById('loading-overlay').style.display = 'flex';
    }
}

function hideLoading() {
    var overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}
// ============================================
// PRINT FUNCTIONS
// ============================================
function printReceipt(orderId) {
    window.open('print_receipt.php?id=' + orderId, '_blank', 'width=400,height=600');
}

// ============================================
// EXPORT & RESET
// ============================================
function exportOrders() {
    var date = document.getElementById('filter-date').value;
    var status = document.getElementById('filter-status').value;
    var waiter = document.getElementById('filter-waiter').value;
    var table = document.getElementById('filter-table').value;
    window.location.href = 'api/export_orders.php?date=' + date + '&status=' + status + '&waiter=' + waiter + '&table=' + table;
}

function resetFilters() {
    document.getElementById('filter-date').value = 'today';
    document.getElementById('filter-status').value = 'all';
    document.getElementById('filter-waiter').value = 'all';
    document.getElementById('filter-table').value = '';
    currentPage = 1;
    loadOrders();
}

function closeModal() {
    document.getElementById('order-modal').style.display = 'none';
}

function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.remove();
    }, 3000);
}

// ============================================
// INITIALIZE
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadWaiters();
    loadOrders();
    startAutoRefresh();
    
    // Set up auto-refresh toggle
    var toggleCheckbox = document.getElementById('auto-refresh-checkbox');
    if (toggleCheckbox) {
        toggleCheckbox.addEventListener('change', toggleAutoRefresh);
    }
    
    // Set initial last order count after first load
    setTimeout(function() {
        var orderCountSpan = document.getElementById('order-count');
        if (orderCountSpan) {
            var countText = orderCountSpan.textContent;
            var match = countText.match(/\d+/);
            if (match) {
                lastOrderCount = parseInt(match[0]);
            }
        }
    }, 1000);
});

window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

window.onclick = function(event) {
    if (event.target === document.getElementById('order-modal')) {
        closeModal();
    }
};
</script>

<?php include 'layout/sidebar_footer.php'; ?>