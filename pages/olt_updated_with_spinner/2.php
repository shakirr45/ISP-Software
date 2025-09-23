<?php
set_time_limit(0);
date_default_timezone_set('Asia/Dhaka');

require_once '/var/www/html/olt6/services/Database.php';
$db = new Database();
$pdo = $db->getConnection();

$latest = $pdo->query("SELECT MAX(last_updated) AS last_updated FROM onu_status")->fetchColumn();

$total = $pdo->query("SELECT COUNT(*) FROM onu_status")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM onu_status WHERE (rx_power > -30 OR tx_power > -30)")->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'total' => $total ?: 0,
    'active' => $active ?: 0,
    'last_updated' => $latest ?: null
]);
