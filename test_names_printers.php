<?php
// list_printers_ps.php
$psCommand = 'Get-Printer | Select-Object -ExpandProperty Name';
exec("powershell -Command \"$psCommand\"", $output);
foreach ($output as $printer) {
    echo "'$printer'<br>";
}
?>