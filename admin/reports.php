<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('reports'); ?></h1>
    <p class="page-description"><?php echo t('reports_description'); ?></p>
</div>

<div class="page-content">
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('filter_reports'); ?></h3>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group">
                    <label><?php echo t('period'); ?></label>
                    <select id="report-period" onchange="toggleDateRange()">
                        <option value="today"><?php echo t('today'); ?></option>
                        <option value="yesterday"><?php echo t('yesterday'); ?></option>
                        <option value="week"><?php echo t('this_week'); ?></option>
                        <option value="month"><?php echo t('this_month'); ?></option>
                        <option value="custom"><?php echo t('custom_range'); ?></option>
                    </select>
                </div>
                <div id="date-range" style="display: none; gap: 10px;">
                    <div class="form-group">
                        <label><?php echo t('from_date'); ?></label>
                        <input type="date" id="from-date">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('to_date'); ?></label>
                        <input type="date" id="to-date">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="loadSummary()"><?php echo t('generate'); ?></button>
            </div>
        </div>
    </div>

    <div id="reports-container">
        <div style="text-align: center; padding: 40px;"><?php echo t('select_dates_and_generate'); ?></div>
    </div>
</div>

<!-- Modal for detailed lists -->
<div id="detail-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="modal-title"><?php echo t('details'); ?></h3>
            <button class="close-modal" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" id="modal-body" style="max-height: 500px; overflow-y: auto;">
            <?php echo t('loading'); ?>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="exportDetailToCSV()">📎 <?php echo t('export_csv'); ?></button>
            <button class="btn btn-primary" onclick="closeDetailModal()"><?php echo t('close'); ?></button>
        </div>
    </div>
</div>

<style>
    .report-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .summary-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        cursor: pointer;
        transition: all 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        background: #f8fafc;
    }
    .summary-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #3498db;
    }
    .summary-label {
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 5px;
    }
    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .detail-table th, .detail-table td {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
        text-align: left;
    }
    body[dir="rtl"] .detail-table th, body[dir="rtl"] .detail-table td {
        text-align: right;
    }
    .detail-table th {
        background: #f1f3f5;
    }
    @media print {
        .modal-content {
            box-shadow: none;
        }
    }
</style>

<script>
let currentDetailData = [];
let currentDetailType = '';

function toggleDateRange() {
    const period = document.getElementById('report-period').value;
    const dateRange = document.getElementById('date-range');
    dateRange.style.display = period === 'custom' ? 'flex' : 'none';
}

function getDateParams() {
    const period = document.getElementById('report-period').value;
    if (period === 'custom') {
        const from = document.getElementById('from-date').value;
        const to = document.getElementById('to-date').value;
        if (!from || !to) {
            alert('<?php echo t('select_date_range'); ?>');
            return null;
        }
        return { from: from, to: to };
    }
    return { period: period };
}

function loadSummary() {
    const params = getDateParams();
    if (!params) return;
    
    const container = document.getElementById('reports-container');
    container.innerHTML = '<div style="text-align: center; padding: 40px;"><?php echo t('loading'); ?></div>';
    
    let url = 'api/get_reports.php?';
    if (params.from && params.to) {
        url += `from=${params.from}&to=${params.to}`;
    } else {
        url += `period=${params.period}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<div style="color:red;">${data.error}</div>`;
                return;
            }
            displaySummary(data, params);
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div style="color:red;">Failed to load reports</div>';
        });
}

function displaySummary(data, params) {
    const symbol = '<?php echo t('currency'); ?>';
    const isRtl = <?php echo json_encode(get_dir() === 'rtl'); ?>;
    const formatCurrency = (amount) => {
        const formatted = parseFloat(amount).toFixed(2);
        return isRtl ? formatted + ' ' + symbol : symbol + formatted;
    };
    
    // Store params for detail calls (period or from/to)
    window.currentReportParams = params;
    
    let html = `
        <div class="report-summary">
            <div class="summary-card" onclick="showDetails('orders')">
                <div class="summary-number">${data.total_orders}</div>
                <div class="summary-label">📋 <?php echo t('total_orders'); ?></div>
            </div>
            <div class="summary-card" onclick="showDetails('cancelled')">
                <div class="summary-number">${data.cancelled_items}</div>
                <div class="summary-label">❌ <?php echo t('cancelled_items'); ?></div>
            </div>
            <div class="summary-card" onclick="showDetails('revenue')">
                <div class="summary-number">${formatCurrency(data.total_revenue)}</div>
                <div class="summary-label">💰 <?php echo t('total_revenue'); ?></div>
            </div>
            <div class="summary-card" onclick="showDetails('waiter')">
                <div class="summary-number">${data.orders_by_waiter || 0}</div>
                <div class="summary-label">👨‍🍳 <?php echo t('orders_by_waiter'); ?></div>
            </div>
        </div>
    `;
    document.getElementById('reports-container').innerHTML = html;
}

function showDetails(type) {
    currentDetailType = type;
    const modal = document.getElementById('detail-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    
    if (type === 'orders') {
        modalTitle.textContent = '📋 <?php echo t('orders_list'); ?>';
    } else if (type === 'cancelled') {
        modalTitle.textContent = '❌ <?php echo t('cancelled_items_list'); ?>';
    } else if (type === 'revenue') {
        modalTitle.textContent = '💰 <?php echo t('revenue_breakdown'); ?>';
    } else {
        modalTitle.textContent = '👨‍🍳 <?php echo t('orders_by_waiter'); ?>';
    }
    
    modalBody.innerHTML = '<?php echo t('loading'); ?>';
    modal.style.display = 'flex';
    
    let url = 'api/get_reports.php?';
    const params = window.currentReportParams;
    if (params.from && params.to) {
        url += `from=${params.from}&to=${params.to}&action=${type}`;
    } else {
        url += `period=${params.period}&action=${type}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `<div style="color:red;">${data.error}</div>`;
                return;
            }
            currentDetailData = data.data || [];
            displayDetailTable(type, currentDetailData);
        })
        .catch(error => {
            modalBody.innerHTML = '<div style="color:red;">Failed to load details</div>';
            console.error(error);
        });
}

function displayDetailTable(type, data) {
    const symbol = '<?php echo t('currency'); ?>';
    const isRtl = <?php echo json_encode(get_dir() === 'rtl'); ?>;
    const formatCurrency = (amount) => {
        const formatted = parseFloat(amount).toFixed(2);
        return isRtl ? formatted + ' ' + symbol : symbol + formatted;
    };
    
    if (!data || data.length === 0) {
        document.getElementById('modal-body').innerHTML = '<div style="text-align:center; padding:40px;"><?php echo t('no_data'); ?></div>';
        return;
    }
    
    let html = '<table class="detail-table"><thead><tr>';
    
    if (type === 'orders') {
        html += '<th><?php echo t('order_number'); ?></th>';
        html += '<th><?php echo t('table'); ?></th>';
        html += '<th><?php echo t('waiter'); ?></th>';
        html += '<th><?php echo t('total'); ?></th>';
        html += '<th><?php echo t('time'); ?></th>';
        html += '</tr></thead><tbody>';
        data.forEach(order => {
            html += `<tr>
                         <td>${escapeHtml(order.order_number)}</td>
                         <td>${escapeHtml(order.table_number)}</td>
                         <td>${escapeHtml(order.waiter_name || '-')}</td>
                         <td>${formatCurrency(order.total_amount)}</td>
                         <td>${new Date(order.created_at).toLocaleString()}</td>
                     </tr>`;
        });
    } else if (type === 'cancelled') {
        html += '<th><?php echo t('order_number'); ?></th>';
        html += '<th><?php echo t('item_name'); ?></th>';
        html += '<th><?php echo t('quantity'); ?></th>';
        html += '<th><?php echo t('table'); ?></th>';
        html += '<th><?php echo t('waiter'); ?></th>';
        html += '<th><?php echo t('time'); ?></th>';
        html += '</tr></thead><tbody>';
        data.forEach(item => {
            html += `<tr>
                         <td>${escapeHtml(item.order_number)}</td>
                         <td>${escapeHtml(item.item_name)}</td>
                         <td>${item.quantity}</td>
                         <td>${escapeHtml(item.table_number)}</td>
                         <td>${escapeHtml(item.waiter_name || '-')}</td>
                         <td>${new Date(item.created_at).toLocaleString()}</td>
                     </tr>`;
        });
    } else if (type === 'waiter') {
        html += '<th><?php echo t('waiter'); ?></th>';
        html += '<th><?php echo t('orders_count'); ?></th>';
        html += '<th><?php echo t('total_revenue'); ?></th>';
        html += '</table></thead><tbody>';
        data.forEach(waiter => {
            html += `<tr>
                         <td>${escapeHtml(waiter.waiter_name)}</td>
                         <td>${waiter.order_count}</td>
                         <td>${formatCurrency(waiter.total_revenue)}</td>
                     </tr>`;
        });
    } else if (type === 'revenue') {
        html += '<th><?php echo t('order_number'); ?></th>';
        html += '<th><?php echo t('table'); ?></th>';
        html += '<th><?php echo t('waiter'); ?></th>';
        html += '<th><?php echo t('total'); ?></th>';
        html += '<th><?php echo t('time'); ?></th>';
        html += '</tr></thead><tbody>';
        data.forEach(order => {
            html += `<tr>
                         <td>${escapeHtml(order.order_number)}</td>
                         <td>${escapeHtml(order.table_number)}</td>
                         <td>${escapeHtml(order.waiter_name || '-')}</td>
                         <td>${formatCurrency(order.total_amount)}</td>
                         <td>${new Date(order.created_at).toLocaleString()}</td>
                     </tr>`;
        });
    }
    
    html += '</tbody></table>';
    document.getElementById('modal-body').innerHTML = html;
}

function exportDetailToCSV() {
    if (!currentDetailData || currentDetailData.length === 0) return;
    
    let csv = '';
    if (currentDetailType === 'orders') {
        csv = 'Order Number,Table,Waiter,Total,Time\n';
        currentDetailData.forEach(order => {
            csv += `"${order.order_number}","${order.table_number}","${order.waiter_name || '-'}","${order.total_amount}","${order.created_at}"\n`;
        });
    } else if (currentDetailType === 'cancelled') {
        csv = 'Order Number,Item Name,Quantity,Table,Waiter,Time\n';
        currentDetailData.forEach(item => {
            csv += `"${item.order_number}","${item.item_name}","${item.quantity}","${item.table_number}","${item.waiter_name || '-'}","${item.created_at}"\n`;
        });
    } else if (currentDetailType === 'waiter') {
        csv = 'Waiter,Orders Count,Total Revenue\n';
        currentDetailData.forEach(waiter => {
            csv += `"${waiter.waiter_name}","${waiter.order_count}","${waiter.total_revenue}"\n`;
        });
    } else {
        csv = 'Order Number,Table,Waiter,Total,Time\n';
        currentDetailData.forEach(order => {
            csv += `"${order.order_number}","${order.table_number}","${order.waiter_name || '-'}","${order.total_amount}","${order.created_at}"\n`;
        });
    }
    
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `${currentDetailType}_report.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function closeDetailModal() {
    document.getElementById('detail-modal').style.display = 'none';
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

// Add translation keys for custom range (add to language.php)
// 'custom_range' => 'Custom Range',
// 'from_date' => 'From Date',
// 'to_date' => 'To Date',
// 'select_date_range' => 'Please select both dates',

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDateRange();
    loadSummary();
});
</script>

<?php include 'layout/sidebar_footer.php'; ?>