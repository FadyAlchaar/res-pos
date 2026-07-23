<?php
require_once '../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

$options = new QROptions([
    'version'      => 5,
    'outputType'   => QROutputInterface::PNG,
    'eccLevel'     => QRCode::ECC_L,
    'scale'        => 5,
    'imageBase64'  => false,
]);

$qrcode = new QRCode($options);

// Create directory if not exists
$qrDir = __DIR__ . '/../assets/qrcodes';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Generate QR codes for tables 1 to 60 (or fetch from database)
$tables = range(1, 60);
foreach ($tables as $table) {
    $url = "http://192.168.1.240/parking_request.php?table=$table";
    // Replace 'yourdomain.com' with your actual domain/IP
    $qrData = $qrcode->render($url);
    file_put_contents("$qrDir/table_$table.png", $qrData);
    echo "Generated QR for table $table\n";
}