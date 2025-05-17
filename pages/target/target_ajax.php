<?php
session_start();

require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {
    $target_name = $_POST['target_name'];
    $type = $_POST['type'];
    $year = $_POST['year'];
    $year_target = $_POST['year_target'];
    $january_target = $_POST['january_target'];
    $february_target = $_POST['february_target'];
    $march_target = $_POST['march_target'];
    $april_target = $_POST['april_target'];
    $may_target = $_POST['may_target'];
    $june_target = $_POST['june_target'];
    $july_target = $_POST['july_target'];
    $august_target = $_POST['august_target'];
    $september_target = $_POST['september_target'];
    $october_target = $_POST['october_target'];
    $november_target = $_POST['november_target'];
    $december_target = $_POST['december_target'];
    $stmt = $obj->insertData('business_targets', ['target_name' => $target_name, 'type' => $type, 'year' => $year, 'year_target' => $year_target, 'january_target' => $january_target, 'february_target' => $february_target, 'march_target' => $march_target, 'april_target' => $april_target, 'may_target' => $may_target, 'june_target' => $june_target, 'july_target' => $july_target, 'august_target' => $august_target, 'september_target' => $september_target, 'october_target' => $october_target, 'november_target' => $november_target, 'december_target' => $december_target, 'created_by' => $userid]);

    if ($stmt) {
        echo json_encode(['success' => true, 'status' => 'Added successfully']);
        exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'];  // Pagination start
    $length = $_GET['length'];  // Pagination length
    $order_column = $_GET['order'][0]['column'] ?? '';  // Column index
    $order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)
    $search = $_GET['search']['value'] ?? '';  // Search value

    // Define columns
    $columns = [
        'target_name',
        'type',
        'year',
        'year_target',
        'january_target',
        'february_target',
        'march_target',
        'april_target',
        'may_target',
        'june_target',
        'july_target',
        'august_target',
        'september_target',
        'october_target',
        'november_target',
        'december_target',
        'FullName'
    ];

    // Construct the ORDER BY clause dynamically
    if (isset($order_column) && isset($columns[$order_column])) {
        if ($order_column == "FullName") {
            // If the column is FullName, order by created_by (or the corresponding column)
            $order_by = "created_by " . $order_dir;
        } else {
            // Else use the valid column name from the array
            $order_by = $columns[$order_column] . ' ' . $order_dir;
        }
    } else {
        // Default order by id descending
        $order_by = "bt.id DESC";
    }
    // Construct the WHERE clause for search
    $wherecond = "";
    if (!empty($search)) {
        $wherecond .= " AND (bt.target_name LIKE '%$search%' OR  _createuser.FullName LIKE '%$search%')";
    }
    $targets = $obj->raw_sql("SELECT 
        bt.id, 
        bt.target_name, 
        bt.type, 
        bt.year, 
        bt.year_target, 
        bt.january_target, 
        bt.february_target, 
        bt.march_target, 
        bt.april_target, 
        bt.may_target, 
        bt.june_target, 
        bt.july_target, 
        bt.august_target, 
        bt.september_target, 
        bt.october_target, 
        bt.november_target, 
        bt.december_target,
        _createuser.FullName
    FROM business_targets bt
    LEFT JOIN _createuser ON bt.created_by = _createuser.UserId
   WHERE bt.deleted_at IS NULL  $wherecond
    ORDER BY $order_by
    ");

    $i = 1; // Initialize counter
    foreach ($targets as &$row) {
        $row['sl'] = $i++; // Add the row number
    }
    $total = count($targets);
    $totalFiltered = count($targets);

    // $targets = $obj->view_all('business_targets');
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => $total,
        'recordsFiltered' => $totalFiltered,
        'data' => $targets,
    ]);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && $_POST['action'] != 'delete') {
    $id = $_POST['id'];
    $target_name = $_POST['target_name'];
    $type = $_POST['type'];
    $year = $_POST['year'];
    $year_target = $_POST['year_target'];
    $january_target = $_POST['january_target'];
    $february_target = $_POST['february_target'];
    $march_target = $_POST['march_target'];
    $april_target = $_POST['april_target'];
    $may_target = $_POST['may_target'];
    $june_target = $_POST['june_target'];
    $july_target = $_POST['july_target'];
    $august_target = $_POST['august_target'];
    $september_target = $_POST['september_target'];
    $october_target = $_POST['october_target'];
    $november_target = $_POST['november_target'];
    $december_target = $_POST['december_target'];
    $stmt = $obj->updateData('business_targets', ['target_name' => $target_name, 'type' => $type, 'year' => $year, 'year_target' => $year_target, 'january_target' => $january_target, 'february_target' => $february_target, 'march_target' => $march_target, 'april_target' => $april_target, 'may_target' => $may_target, 'june_target' => $june_target, 'july_target' => $july_target, 'august_target' => $august_target, 'september_target' => $september_target, 'october_target' => $october_target, 'november_target' => $november_target, 'december_target' => $december_target, 'updated_by' => $userid], ['id' => $id]);

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && $_POST['action'] == 'delete') {

    $id = $_POST['id'];
    $stmt = $obj->updateData('business_targets', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    if ($stmt > 0) {
        echo json_encode(['success' => true, 'status' => 'Deleted successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to delete']);
        exit;
    }
}
