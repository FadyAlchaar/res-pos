<?php
// No authentication required – secret page for manager
require_once '../config/language.php';
// Handle language switch from URL
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['language'] = $_GET['lang'];
}
$lang = $_SESSION['language'] ?? 'en';
$GLOBALS['lang'] = $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo get_dir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('parking_reports'); ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        select, button { padding: 8px 16px; border-radius: 8px; border: 1px solid #ccc; }
        button { background: #3498db; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        body[dir="rtl"] th, body[dir="rtl"] td { text-align: right; }
        .badge { background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
        .lang-switch { display: flex; gap: 8px; }
        .lang-btn { padding: 5px 12px; border-radius: 20px; text-decoration: none; background: #e9ecef; color: #2c3e50; }
        .lang-btn.active { background: #3498db; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><?php echo t('parking_reports'); ?></h1>
            <div class="lang-switch">
                <a href="?lang=en" class="lang-btn <?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                <a href="?lang=ar" class="lang-btn <?php echo $lang == 'ar' ? 'active' : ''; ?>">ع</a>
            </div>
        </div>
        <div>
            <select id="report-period">
                <option value="today"><?php echo t('today'); ?></option>
                <option value="week"><?php echo t('this_week'); ?></option>
                <option value="month"><?php echo t('this_month'); ?></option>
                <option value="all"><?php echo t('all_time'); ?></option>
            </select>
            <button onclick="loadReport()"><?php echo t('generate_report'); ?></button>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3><?php echo t('parking_reports'); ?></h3>
            <span id="total-count" class="badge">0</span>
        </div>
        <div id="report-container"><?php echo t('select_period_and_generate'); ?></div>
    </div>
</div>
<script>
function loadReport() {
    const period = document.getElementById('report-period').value;
    const container = document.getElementById('report-container');
    const totalSpan = document.getElementById('total-count');
    container.innerHTML = '<?php echo t('loading'); ?>';
    fetch(`api/get_parking_reports.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<div style="color:red;">${data.error}</div>`;
                return;
            }
            totalSpan.textContent = data.total;
            if (data.requests.length === 0) {
                container.innerHTML = '<div><?php echo t('no_requests'); ?></div>';
                return;
            }
            let html = '<table><thead><tr><th><?php echo t('date'); ?></th><th><?php echo t('time'); ?></th><th><?php echo t('table'); ?></th><th><?php echo t('lot_numbers'); ?></th></tr></thead><tbody>';
            data.requests.forEach(req => {
                const date = new Date(req.created_at);
                const dateStr = date.toLocaleDateString();
                const timeStr = date.toLocaleTimeString();
                const tableDisplay = req.table_number == 0 ? '—' : req.table_number;
                html += `<tr><td>${dateStr}</td><td>${timeStr}</td><td>${tableDisplay}</td><td>${req.lot_numbers}</td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<div style="color:red;"><?php echo t('error_loading'); ?></div>';
        });
}
loadReport();
</script>
</body>
</html>