<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $sale_id = $_GET['sale'] ?? 0;
    $id = $_GET['id'];

    // If sale_id is provided, fetch associated model

    if ($sale_id > 0) {
        $saleID = $obj->rawSqlSingle("SELECT * FROM sales WHERE sale_id = $sale_id AND deleted_at IS NULL");

        $modelId = $saleID['model_id'];

        $modelIdsJson = json_decode($modelId);

        $saleModel = [];

        foreach ($modelIdsJson as $modelId) {
            $Model = $obj->rawSqlSingle("SELECT * FROM product_model pm WHERE pm.deleted_at IS NULL AND pm.id = $modelId");
            $saleModel[] = $Model;
        }
        $unsoldModels = $obj->raw_sql("SELECT * FROM product_model WHERE deleted_at IS NULL AND sold = 0 AND product_id = $id");
        foreach ($unsoldModels as $unsoldModel) {
            $saleModel[] = $unsoldModel;
        }

        echo json_encode(['success' => true, 'modelIds' => $saleModel]);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to add stock']);
        exit;
    }
}
