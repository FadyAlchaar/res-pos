<?php
echo "<h1>Windows Printers</h1>";
echo "<pre>" . shell_exec('wmic printer get name') . "</pre>";
?>