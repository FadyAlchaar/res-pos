<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0);

echo "retry: 1000\n\n";
ob_end_flush(); // clear any existing buffers
flush();

while (true) {
    echo "data: " . json_encode(['time' => date('H:i:s')]) . "\n\n";
    flush();
    sleep(5);
}
?>