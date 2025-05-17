<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {

    // Edit Category

    $name = $_POST['name'];
    $description = $_POST['description'];
    $stmt = $obj->insertData('product_categories', ['category_name' => $name, 'description' => $description, 'created_by' => $userid]);
    if ($stmt) {
        echo json_encode(['success' => true, 'status' => 'Added successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to add']);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $fromUpdate = array('category_name' => $name, 'description' => $description, 'updated_by' => $userid);

    // Call the updateData method
    $stmt = $obj->updateData(
        'product_categories',
        $fromUpdate,
        ['category_id' => $id]
    );

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to update']);
    }
    exit;
}

// Fetch Categories (for DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $categories = $obj->raw_sql("SELECT 
    product_categories.*,_createuser.FullName FROM product_categories 
    LEFT JOIN _createuser ON product_categories.created_by = _createuser.UserId;");

    // $categories = $obj->view_all('product_categories');
    echo json_encode($categories);
    exit;
}
