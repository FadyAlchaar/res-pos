<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('Session Management'); ?></h1>
    <p class="page-description"><?php echo t('view_all_customer_sessions_and_their_orders'); ?></p>
</div>

<div class="page-content">
    <!-- Filters -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3>🔍 <?php echo t('filter_session'); ?></h3>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('status'); ?></label>
                    <select id="filter-status" onchange="loadSessions()">
                        <option value="all"><?php echo t('all_sessions'); ?></option>
                        <option value="open"><?php echo t('open_sessions'); ?></option>
                        <option value="closed"><?php echo t('closed_sessions'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('date_range'); ?></label>
                    <select id="filter-date" onchange="loadSessions()">
                        <option value="today"><?php echo t('today'); ?></option>
                        <option value="yesterday"><?php echo t('yesterday'); ?></option>
                        <option value="week"><?php echo t('this_week'); ?></option>
                        <option value="month"><?php echo t('this_month'); ?></option>
                        <option value="all"><?php echo t('all_time'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><?php echo t('table'); ?></label>
                    <input type="text" id="filter-table" placeholder="<?php echo t('table_number'); ?>" onkeyup="loadSessions()" style="width: 100px;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button class="btn btn-primary" onclick="resetFilters()"><?php echo t('reset'); ?></button>
                    <button class="btn btn-success" onclick="exportSessions()" style="margin-left: 10px;">📎 <?php echo t('export_csv'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="card">
        <div class="card-header">
            <h3>📊 <?php echo t('sessions'); ?></h3>
            <span id="session-count" style="background: #e9ecef; padding: 4px 12px; border-radius: 20px;">0 <?php echo t('sessions'); ?></span>
        </div>
        <div class="card-body">
            <div id="sessions-list">
                <div style="text-align: center; padding: 40px;"><?php echo t('loading'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
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
.session-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.2s;
}

.session-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.session-header {
    background: linear-gradient(135deg, #2c3e50 0%, #1a2632 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    cursor: pointer;
}

.session-number {
    font-size: 1.2rem;
    font-weight: bold;
    font-family: monospace;
}

.session-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-open {
    background: #27ae60;
    color: white;
}

.badge-closed {
    background: #95a5a6;
    color: white;
}

.session-info {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #bdc3c7;
    flex-wrap: wrap;
}

.session-body {
    padding: 16px 20px;
    display: none;
    border-top: 1px solid #e9ecef;
    background: #f8fafc;
}

.session-body.open {
    display: block;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.orders-table th {
    text-align: left;
    padding: 12px;
    background: #f1f3f5;
    font-size: 0.8rem;
    color: #495057;
}

body[dir="rtl"] .orders-table th {
    text-align: right;
}

.orders-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.85rem;
}

.session-total {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 2px solid #dee2e6;
    text-align: right;
    font-weight: bold;
    font-size: 1.1rem;
    color: #27ae60;
}

body[dir="rtl"] .session-total {
    text-align: left;
}

.empty-orders {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    font-style: italic;
}

.btn-sm {
    padding: 4px 12px;
    font-size: 0.7rem;
    margin: 2px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
}

.btn-print {
    background: #27ae60;
    color: white;
}

.btn-print:hover {
    background: #219a52;
}

/* Toast */
.toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 50px;
    color: white;
    z-index: 1100;
    font-size: 0.85rem;
    animation: fadeUp 0.3s;
}

.toast-success { background: #27ae60; }
.toast-error { background: #e74c3c; }
.toast-warning { background: #f39c12; }
.toast-info { background: #3498db; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateX(-50%) translateY(20px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>

<script>
let currentOrderId = null;
let currentOrderData = null;
let currentSessionId = null;

// Translations for status options
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

function loadSessions() {
    const status = document.getElementById('filter-status').value;
    const date = document.getElementById('filter-date').value;
    const table = document.getElementById('filter-table').value;
    
    fetch(`api/get_sessions.php?status=${status}&date=${date}&table=${table}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('sessions-list').innerHTML = `<div style="color: red; text-align: center; padding: 40px;">${data.error}</div>`;
                return;
            }
            
            displaySessions(data.sessions);
            document.getElementById('session-count').textContent = data.sessions.length + ' <?php echo t('sessions'); ?>';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('sessions-list').innerHTML = '<div style="color: red; text-align: center; padding: 40px;"><?php echo t('error_loading'); ?></div>';
        });
}

function displaySessions(sessions) {
    const container = document.getElementById('sessions-list');
    if (!sessions || sessions.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6c757d;"><?php echo t('no_sessions'); ?></div>';
        return;
    }
    
    container.innerHTML = sessions.map(session => `
        <div class="session-card">
            <div class="session-header" onclick="toggleSession(${session.id})">
                <div>
                    <span class="session-number">🧾 ${session.session_number}</span>
                    <span class="session-badge badge-${session.status}">${session.status.toUpperCase()}</span>
                </div>
                <div class="session-info">
                    <span>📋 <?php echo t('table'); ?> ${session.table_number}</span>
                    <span>👤 ${session.customer_count} <?php echo t('guests'); ?></span>
                    <span>👨‍🍳 ${session.waiter_name}</span>
                    <span>🕐 ${new Date(session.opened_at).toLocaleString()}</span>
                    ${session.closed_at ? `<span>✅ ${new Date(session.closed_at).toLocaleString()}</span>` : ''}
                    ${session.status === 'open' ? `<button class="btn-sm btn-danger" onclick="event.stopPropagation(); closeSession(${session.id})">🔒 <?php echo t('close_session'); ?></button>` : ''}
                </div>
            </div>
            <div class="session-body" id="session-${session.id}">
                <div id="session-orders-${session.id}">
                    <div style="text-align: center; padding: 20px;"><?php echo t('loading'); ?></div>
                </div>
            </div>
        </div>
    `).join('');
}

function toggleSession(sessionId) {
    const body = document.getElementById(`session-${sessionId}`);
    
    if (body.classList.contains('open')) {
        body.classList.remove('open');
    } else {
        document.querySelectorAll('.session-body').forEach(el => el.classList.remove('open'));
        body.classList.add('open');
        loadSessionOrders(sessionId);
    }
}

function loadSessionOrders(sessionId) {
    const container = document.getElementById(`session-orders-${sessionId}`);
    
    fetch(`api/get_session_orders.php?session_id=${sessionId}&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<div style="color: red; text-align: center; padding: 20px;">${data.error}</div>`;
                return;
            }
            
            if (data.orders && data.orders.length > 0) {
                let ordersHtml = `
                    <table class="orders-table">
                        <thead>
                             <tr>
                                <th><?php echo t('order_number'); ?></th>
                                <th><?php echo t('time'); ?></th>
                                <th><?php echo t('items'); ?></th>
                                <th><?php echo t('total'); ?></th>
                                <th><?php echo t('status'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                             </tr>
                        </thead>
                        <tbody>
                `;
                
                data.orders.forEach(order => {
                    const statusText = statusOptions[order.status] || order.status;
                    ordersHtml += `
                        <tr>
                            <td><strong>${order.order_number}</strong></td>
                            <td>${new Date(order.created_at).toLocaleTimeString()}</td>
                            <td>${order.item_count}</td>
                            <td style="color: #27ae60;">$${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td>
                                <span class="badge ${order.status === 'pending' ? 'badge-warning' : order.status === 'delivered' ? 'badge-success' : 'badge-info'}">
                                    ${statusText}
                                </span>
                            </td>
                            <td>
                                <button class="btn-sm btn-primary" onclick="viewOrder(${order.id})">📋 <?php echo t('view_details'); ?></button>
                                <button class="btn-sm btn-print" onclick="printToKitchen(${order.id})">🖨️ <?php echo t('print_receipt'); ?></button>
                            </td>
                        </tr>
                    `;
                });
                
                ordersHtml += `
                            </tbody>
                        </table>
                        <div class="session-total">
                            <?php echo t('session_total'); ?>: $${parseFloat(data.session_total).toFixed(2)}
                        </div>
                    `;
                
                container.innerHTML = ordersHtml;
            } else {
                container.innerHTML = '<div class="empty-orders"><?php echo t('no_orders_in_session'); ?></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div style="color: red; text-align: center; padding: 20px;"><?php echo t('error_loading'); ?></div>';
        });
}

function closeSession(sessionId) {
    if (!confirm('<?php echo t('confirm_close_session'); ?>')) return;
    
    showLoading();
    fetch('api/close_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('✅ <?php echo t('session_closed'); ?>', 'success');
            loadSessions(); // refresh the sessions list
        } else {
            showToast('❌ <?php echo t('error'); ?>: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('<?php echo t('error'); ?>', 'error');
    });
}

function viewOrder(orderId) {
    currentOrderId = orderId;
    
    showLoading();
    fetch(`api/get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.error) {
                document.getElementById('modal-body').innerHTML = `<div style="color: red;">${data.error}</div>`;
                return;
            }
            
            currentOrderData = data;
            displayOrderDetails(data);
            document.getElementById('order-modal').style.display = 'flex';
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            document.getElementById('modal-body').innerHTML = '<div style="color: red;"><?php echo t('error_loading'); ?></div>';
        });
}

function displayOrderDetails(order) {
    // Group items by kitchen
    const itemsByKitchen = {};
    (order.items || []).forEach(item => {
        const kitchenId = item.kitchen_id || 1;
        const kitchenName = item.kitchen_name || 'Main Kitchen';
        if (!itemsByKitchen[kitchenId]) {
            itemsByKitchen[kitchenId] = {
                id: kitchenId,
                name: kitchenName,
                items: []
            };
        }
        itemsByKitchen[kitchenId].items.push(item);
    });
    
    const kitchenButtonsHtml = Object.values(itemsByKitchen).map(kitchen => `
        <button class="btn btn-warning btn-sm" onclick="printToKitchen(${order.id}, ${kitchen.id})" style="margin-right: 8px;">
            🖨️ <?php echo t('print_to'); ?> ${kitchen.name}
        </button>
    `).join('');
    
    const itemsHtml = (order.items || []).map(item => `
        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
            <div>
                <strong>${item.quantity}x ${item.name}</strong>
                ${item.notes ? `<br><small style="color: #f39c12;">📝 ${item.notes}</small>` : ''}
                <br><small><?php echo t('kitchen'); ?>: ${item.kitchen_name || 'Main Kitchen'}</small>
            </div>
            <div style="color: #27ae60;">$${parseFloat(item.subtotal).toFixed(2)}</div>
        </div>
    `).join('');
    
    const html = `
        <div style="margin-bottom: 20px;">
            <p><strong><?php echo t('order_number'); ?>:</strong> ${order.order_number}</p>
            <p><strong><?php echo t('date_time'); ?>:</strong> ${new Date(order.created_at).toLocaleString()}</p>
            <p><strong><?php echo t('table'); ?>:</strong> ${order.table_number || 'N/A'}</p>
            <p><strong><?php echo t('waiter'); ?>:</strong> ${order.waiter_name}</p>
            <p><strong><?php echo t('status'); ?>:</strong> 
                <select id="order-status-select" style="padding: 4px 8px; border-radius: 5px;">
                    <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>${statusOptions.pending}</option>
                    <option value="preparing" ${order.status === 'preparing' ? 'selected' : ''}>${statusOptions.preparing}</option>
                    <option value="ready" ${order.status === 'ready' ? 'selected' : ''}>${statusOptions.ready}</option>
                    <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>${statusOptions.delivered}</option>
                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>${statusOptions.cancelled}</option>
                </select>
            </p>
            <p><strong><?php echo t('payment_status'); ?>:</strong> 
                <select id="payment-status-select">
                    <option value="unpaid" ${order.payment_status === 'unpaid' ? 'selected' : ''}>${paymentOptions.unpaid}</option>
                    <option value="paid" ${order.payment_status === 'paid' ? 'selected' : ''}>${paymentOptions.paid}</option>
                    <option value="refunded" ${order.payment_status === 'refunded' ? 'selected' : ''}>${paymentOptions.refunded}</option>
                </select>
            </p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4><?php echo t('order_items'); ?>:</h4>
            ${itemsHtml}
            <div style="margin-top: 15px; padding-top: 10px; border-top: 2px solid #e9ecef; display: flex; justify-content: space-between; font-weight: bold;">
                <span><?php echo t('total'); ?>:</span>
                <span style="color: #27ae60;">$${parseFloat(order.total_amount).toFixed(2)}</span>
            </div>
        </div>
        
        ${order.special_instructions ? `<div style="margin-bottom: 20px;"><strong><?php echo t('special_instructions'); ?>:</strong><br>${order.special_instructions}</div>` : ''}
        
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef;">
            <h4><?php echo t('print_options'); ?>:</h4>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                <button class="btn btn-success" onclick="printLocalReceipt(${order.id})">
                    🖨️ <?php echo t('local_print'); ?>
                </button>
                ${kitchenButtonsHtml}
                ${Object.keys(itemsByKitchen).length > 1 ? `
                    <button class="btn btn-primary" onclick="printAllKitchens(${order.id})">
                        🔄 <?php echo t('print_all_kitchens'); ?>
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    document.getElementById('modal-body').innerHTML = html;
    document.getElementById('modal-title').textContent = `<?php echo t('order'); ?> ${order.order_number}`;
}

function updateOrderStatus() {
    const status = document.getElementById('order-status-select').value;
    const payment = document.getElementById('payment-status-select').value;
    
    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: currentOrderId,
            status: status,
            payment_status: payment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('<?php echo t('order_updated'); ?>', 'success');
            closeModal();
            // Reload the session view to refresh orders
            if (currentOrderData && currentOrderData.session_id) {
                loadSessionOrders(currentOrderData.session_id);
            }
            loadSessions();
        } else {
            showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
        }
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
    .then(response => response.json())
    .then(data => {
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
                .then(response => response.json())
                .then(retryData => {
                    hideLoading();
                    if (retryData.success) {
                        showToast('✅ ' + retryData.message, 'success');
                    } else {
                        showToast('❌ ' + retryData.message, 'error');
                    }
                });
            }
        } else if (data.success) {
            showToast('✅ ' + data.message, 'success');
        } else {
            showToast('❌ <?php echo t('error'); ?>: ' + data.message, 'error');
        }
    });
}

function printAllKitchens(orderId) {
    showLoading();
    fetch('api/print_to_all_kitchens.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, force: true })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            let message = '✅ ' + data.message + '\n\n';
            data.details.forEach(detail => {
                message += `\n${detail.kitchen}: ${detail.item_count} <?php echo t('items'); ?> - ${detail.message}`;
            });
            alert(message);
        } else {
            showToast('❌ <?php echo t('error'); ?>: ' + data.message, 'error');
        }
    });
}

function printLocalReceipt(orderId) {
    window.open(`print_receipt.php?id=${orderId}&mode=local`, '_blank', 'width=400,height=600');
}

function exportSessions() {
    const status = document.getElementById('filter-status').value;
    const date = document.getElementById('filter-date').value;
    const table = document.getElementById('filter-table').value;
    window.location.href = `api/export_sessions.php?status=${status}&date=${date}&table=${table}`;
}

function resetFilters() {
    document.getElementById('filter-status').value = 'all';
    document.getElementById('filter-date').value = 'today';
    document.getElementById('filter-table').value = '';
    loadSessions();
}

function closeModal() {
    document.getElementById('order-modal').style.display = 'none';
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
    overlay.innerHTML = '<div style="background:white;padding:20px;border-radius:10px;">⏳ <?php echo t('loading'); ?>...</div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.remove();
}

// Load on page load
loadSessions();

window.onclick = function(event) {
    if (event.target === document.getElementById('order-modal')) {
        closeModal();
    }
};
</script>

<?php include 'layout/sidebar_footer.php'; ?>