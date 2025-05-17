<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {
    $name = $_POST['product_name'];
    $sku = $_POST['sku'];
    $unit_type = $_POST['unit_type'];
    $category_id = $_POST['category_id'];

    $stmt = $obj->insertData('products', ['product_name' => $name, 'sku' => $sku, 'unit_type' => $unit_type, 'category_id' => $category_id, 'created_by' => $userid]);
    if ($stmt) {
        echo json_encode(['success' => true, 'status' => 'Added successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to add']);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['action'])) {

    $id = $_POST['id'];
    $name = $_POST['product_name'];
    $sku = $_POST['sku'];
    $unit_type = $_POST['unit_type'];
    $category_id = $_POST['category_id'];
    $fromUpdate = array('product_name' => $name, 'sku' => $sku, 'unit_type' => $unit_type, 'category_id' => $category_id, 'updated_by' => $userid);

    // Call the updateData method
    $stmt = $obj->updateData(
        'products',
        $fromUpdate,
        ['product_id' => $id]
    );

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to update']);
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && $_POST['action'] == 'delete') {

    $id = $_POST['id'];
    $stmt = $obj->updateData('products', ['deleted_at' => date('Y-m-d H:i:s')], ['product_id' => $id]);

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to delete']);
    }
    exit;
}

// Fetch Categories (for DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $categories = $obj->raw_sql(" SELECT 
        p.product_id, 
        p.product_name, 
        p.sku, 
        p.unit_type, 
        c.category_name ,_createuser.FullName
    FROM products p
    LEFT JOIN product_categories c ON p.category_id = c.category_id
    LEFT JOIN _createuser ON p.created_by = _createuser.UserId
    WHERE p.deleted_at IS NULL;");
    $i = 1; // Initialize counter
    foreach ($categories as &$row) {
        $row['sl'] = $i++; // Add the row number
    }

    // $categories = $obj->view_all('product_categories');
    echo json_encode($categories);
    exit;
}
