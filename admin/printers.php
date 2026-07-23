<?php require_once 'includes/auth.php'; ?>
<?php require_once '../config/language.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="page-header">
    <h1 class="page-title"><?php echo t('printer_status'); ?></h1>
    <p class="page-description"><?php echo t('printer_status_description'); ?></p>
</div>

<div class="page-content">
    <!-- Printer Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;" id="printer-cards">
        <div style="text-align: center; padding: 40px;"><?php echo t('loading'); ?></div>
    </div>
    
    <!-- Print Jobs History -->
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('recent_print_jobs'); ?></h3>
            <button class="btn btn-primary btn-sm" onclick="refreshJobs()">🔄 <?php echo t('refresh'); ?></button>
        </div>
        <div class="card-body">
            <table class="data-table" id="jobs-table">
                <thead>
                    <tr>
                        <th><?php echo t('order_number'); ?></th>
                        <th><?php echo t('kitchen'); ?></th>
                        <th><?php echo t('items'); ?></th>
                        <th><?php echo t('time'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="jobs-list">
                    <tr><td colspan="6" style="text-align: center;"><?php echo t('loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function loadPrinters() {
        fetch('api/get_printer_status.php')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('printer-cards');
                if (data.error) {
                    container.innerHTML = `<div style="color: red; text-align: center;">${data.error}</div>`;
                    return;
                }
                
                container.innerHTML = data.map(printer => `
                    <div class="card" style="border-left: 4px solid ${printer.status === 'online' ? '#27ae60' : '#e74c3c'}">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="font-size: 1rem;">${printer.kitchen_name}</h3>
                            <span class="badge ${printer.status === 'online' ? 'badge-success' : 'badge-danger'}">
                                ${printer.status === 'online' ? '🟢 <?php echo t('online'); ?>' : '🔴 <?php echo t('offline'); ?>'}
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 8px;">
                            📡 <?php echo t('printer_ip'); ?>: ${printer.printer_ip || '<?php echo t('not_configured'); ?>'}
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 15px;">
                            🔌 <?php echo t('port'); ?>: ${printer.printer_port || 9100}
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-primary btn-sm" onclick="testPrinter(${printer.id})"><?php echo t('test_printer'); ?></button>
                            <button class="btn btn-warning btn-sm" onclick="viewJobs(${printer.id})"><?php echo t('view_jobs'); ?></button>
                        </div>
                        ${printer.last_checked ? `<div style="margin-top: 12px; font-size: 0.7rem; color: #94a3b8;"><?php echo t('last_checked'); ?>: ${new Date(printer.last_checked).toLocaleString()}</div>` : ''}
                        ${printer.failed_jobs > 0 ? `<div style="margin-top: 8px; font-size: 0.7rem; color: #e74c3c;">⚠️ ${printer.failed_jobs} <?php echo t('failed_jobs'); ?></div>` : ''}
                    </div>
                `).join('');
            });
    }
    
    function loadPrintJobs() {
        fetch('api/get_print_jobs.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('jobs-list');
                if (data.error) {
                    tbody.innerHTML = `<tr><td colspan="6" style="color: red;">${data.error}</td></tr>`;
                    return;
                }
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;"><?php echo t('no_print_jobs'); ?></td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(job => `
                    <tr>
                        <td><strong>${job.order_number || '<?php echo t('test_print'); ?>'}</strong></td>
                        <td>${job.kitchen_name || 'N/A'}</td>
                        <td>${job.item_name ? `${job.quantity}x ${job.item_name}` : '<?php echo t('test_print'); ?>'}</td>
                        <td>${new Date(job.created_at).toLocaleString()}</td>
                        <td>
                            <span class="badge ${job.status === 'success' ? 'badge-success' : job.status === 'failed' ? 'badge-danger' : 'badge-warning'}">
                                ${job.status || '<?php echo t('pending'); ?>'}
                            </span>
                        </td>
                        <td>
                            ${job.status === 'failed' ? `<button class="btn btn-primary btn-sm" onclick="retryPrint(${job.id})"><?php echo t('retry'); ?></button>` : ''}
                        </td>
                    </tr>
                `).join('');
            });
    }
    
    function testPrinter(kitchenId) {
        showToast('<?php echo t('testing_printer'); ?>', 'info');
        fetch(`api/test_printer.php?kitchen_id=${kitchenId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`<?php echo t('printer_test'); ?>: ${data.status} - ${data.message}`, data.status === 'online' ? 'success' : 'warning');
                    loadPrinters();
                } else {
                    showToast('<?php echo t('test_failed'); ?>: ' + data.message, 'error');
                }
            });
    }
    
    function retryPrint(jobId) {
        showToast('<?php echo t('retrying_print'); ?>', 'info');
        fetch('api/retry_print_job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('<?php echo t('print_retried'); ?>', 'success');
                loadPrintJobs();
                loadPrinters();
            } else {
                showToast('<?php echo t('error'); ?>: ' + data.message, 'error');
            }
        });
    }
    
    function viewJobs(kitchenId) {
        document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        showToast(`<?php echo t('showing_jobs_for'); ?> ID ${kitchenId}`, 'info');
    }
    
    function refreshJobs() {
        loadPrintJobs();
        showToast('<?php echo t('refreshed'); ?>', 'success');
    }
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        loadPrinters();
        loadPrintJobs();
    }, 30000);
    
    // Load on page load
    loadPrinters();
    loadPrintJobs();
</script>

<?php include 'layout/sidebar_footer.php'; ?>