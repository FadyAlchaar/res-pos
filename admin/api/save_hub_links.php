<?php
require_once '../../config/config.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') { die('Unauthorized'); }
$links = $_POST['links'] ?? [];
$data = ['links' => array_values($links)]; // reindex
file_put_contents(__DIR__ . '/../../config/hub_links.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: ../hub_links.php?success=1');
?>