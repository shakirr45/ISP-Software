<?php
session_start();

require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stock = $obj->raw_sql("
    SELECT stock.*,
    p.product_name,
    s.supplier_name,
     _createuser.FullName
    FROM stock
    LEFT JOIN products p ON stock.product_id = p.product_id
    LEFT JOIN suppliers s ON stock.supplier_id = s.supplier_id
    LEFT JOIN _createuser ON stock.created_by = _createuser.UserId");
    $i = 1; // Initialize counter
    $minimum_threshold = 0;
    foreach ($stock as &$row) {
        $row['sl'] = $i++; // Add the row number
        $row['minimum_threshold'] = $minimum_threshold;
    }
    echo json_encode($stock);
    exit;
}
