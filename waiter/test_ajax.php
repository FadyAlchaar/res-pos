<?php
require_once '../config/config.php';
require_once '../config/language.php';
require_once '../includes/session_helper.php';

header('Content-Type: application/json');

echo json_encode(['success' => false, 'message' => 'Test - If you see this, file loads correctly']);
exit;