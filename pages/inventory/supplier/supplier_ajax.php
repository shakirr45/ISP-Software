<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {
    $name = $_POST['supplier_name'];
    $contact_info = $_POST['contact_info'];
    $address = $_POST['address'];
    $stmt = $obj->insertData('suppliers', ['supplier_name' => $name, 'contact_info' => $contact_info, 'address' => $address, 'created_by' => $userid]);
    if ($stmt) {
        echo json_encode(['success' => true, 'status' => 'Added successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to add']);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['action'])) {

    $id = $_POST['id'];
    $name = $_POST['supplier_name'];
    $contact_info = $_POST['contact_info'];
    $address = $_POST['address'];
    $fromUpdate = array('supplier_name' => $name, 'contact_info' => $contact_info, 'address' => $address, 'updated_by' => $userid);

    // Call the updateData method
    $stmt = $obj->updateData(
        'suppliers',
        $fromUpdate,
        ['supplier_id' => $id]
    );

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to update']);
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && $_POST['action'] == 'delete') {

    $id = $_POST['id'];
    $stmt = $obj->updateData('suppliers', ['deleted_at' => date('Y-m-d H:i:s')], ['supplier_id' => $id]);

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to delete']);
    }
    exit;
}

// Fetch Categories (for DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $suppliers = $obj->raw_sql("SELECT 
        s.supplier_id, 
        s.supplier_name, 
        s.contact_info, 
        s.address,
        _createuser.FullName
    FROM suppliers s
    LEFT JOIN _createuser ON s.created_by = _createuser.UserId
     WHERE deleted_at IS NULL
    ;");

    $i = 1; // Initialize counter
    foreach ($suppliers as &$row) {
        $row['sl'] = $i++; // Add the row number
    }

    // $suppliers = $obj->view_all('suppliers');
    echo json_encode($suppliers);
    exit;
}
