<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $batch_id = $_GET['batch'] ?? 0;
    // $sale_id = $_GET['sale'] ?? 0;
    // Fetch stock details securely
    $stock = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `stock_id` = '$id' AND `deleted_at` IS NULL");

    $modelIds = [];
    $models = [];

    // If sale_id is provided, fetch associated model

    // if ($sale_id > 0) {
    //     $sale = $obj->rawSqlSingle("SELECT * FROM sales WHERE sale_id = '$sale_id' AND deleted_at IS NULL");
    //     if ($sale) {
    //         $models[] = $sale['model_id'];
    //         $model_ids = json_decode($models[0], true);  // Decode as an array

    //         if (!is_array($model_ids)) {
    //             $model_ids = [];  // Ensure it's an array (in case of decoding errors or wrong format)
    //         }
    //         foreach ($model_ids as $key => $model_id) {
    //             $model = $obj->rawSqlSingle("SELECT * FROM product_model WHERE id = '$model_id' AND deleted_at IS NULL");

    //             if ($model) {
    //                 $modelIds[$key] = $model;
    //             }
    //         }
    //     }
    // } else {
    // Fetch models based on batch_id if no sale_id is given
    $modelIds = $obj->rawSql("SELECT * FROM product_model WHERE batch_id = '$batch_id' AND sold = 0 AND returned = 0 AND deleted_at IS NULL");
    // }

    // Check if stock was found and return JSON response
    if ($stock) {
        $batch_id = $stock['batch_id'];
        $quantity = $stock['current_stock'];
        echo json_encode(['success' => true, 'batch_id' => $batch_id, 'quantity' => $quantity, 'modelIds' => $modelIds]);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to add stock']);
        exit;
    }
}
