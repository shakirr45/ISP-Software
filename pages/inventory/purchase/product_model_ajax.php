<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modelId']) && !isset($_POST['action'])) {
    $modelId = $_POST['modelId'];
    $modelNo = $_POST['modelNo'];
    $serialNo = $_POST['serialNo'];
    $expireDate = $_POST['expireDate'];
    $stmt = $obj->updateData('product_model', ['model_no' => $modelNo, 'serial_no' => $serialNo, 'expire_date' => $expireDate, 'updated_by' => $userid], ['id' => $modelId]);
    echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['modelId'])) {
    $purchaseId = $_POST['purchaseId'];
    $modelNo = $_POST['modelNo'];
    $serialNo = $_POST['serialNo'];
    $expireDate = $_POST['expireDate'];
    $stmt = $obj->insertData('product_model', ['purchase_id' => $purchaseId, 'model_no' => $modelNo, 'serial_no' => $serialNo, 'expire_date' => $expireDate, 'created_by' => $userid]);
    echo json_encode(['success' => true, 'status' => 'Inserted successfully']);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $modelId = $_POST['modelId'];
    $stmt = $obj->updateData('product_model', ['deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid], ['id' => $modelId]);
    echo json_encode(['success' => true, 'status' => 'Deleted successfully']);
    exit;
}
