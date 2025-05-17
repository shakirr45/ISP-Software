<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$wherecond = '1=1'; // Default condition to avoid empty WHERE clause

// Filtering by customer
if (isset($_GET['customer']) && !empty($_GET['customer'])) {
    $customer = intval($_GET['customer']);
    $wherecond .= " AND customer_return.customer_id = $customer";
}

// Filtering by product
if (isset($_GET['product']) && !empty($_GET['product'])) {
    $product = intval($_GET['product']);
    $wherecond .= " AND customer_return.product_id = $product";
}

// Filtering by batch
if (isset($_GET['batch']) && !empty($_GET['batch'])) {
    $batch = $_GET['batch']; // Ensure batch is sanitized
    $wherecond .= " AND customer_return.batch_id = '$batch'";
}

// Filtering by return date
if (isset($_GET['datefrom']) && !empty($_GET['datefrom'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $wherecond .= " AND DATE(customer_return.return_date) >= '$dateFrom'";
}
if (isset($_GET['dateto']) && !empty($_GET['dateto'])) {
    $dateTo = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND DATE(customer_return.return_date) <= '$dateTo'";
}
$order_column = $_GET['order'][0]['column'] ?? '';  // Column index
$order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)
$columns = [
    'return_id',
    'ag_name',
    'product_name',
    'batch_id',
    'qty_return',
    'model_id',
    'return_date',
    'return_reason',
    'FullName',
];
// Construct the ORDER BY clause dynamically
if (isset($order_column) && isset($columns[$order_column])) {
    if ($order_column == "1" || $order_column == "5") {
        $order_by = "customer_return.customer_id " . $order_dir;
    } elseif ($order_column == "2") {
        $order_by = "customer_return.product_id " . $order_dir;
    } elseif ($order_column == "8") {
        $order_by = "customer_return.created_by " . $order_dir;
    } else {
        // Else use the valid column name from the array
        $order_by = "customer_return." . $columns[$order_column] . ' ' . $order_dir;
    }
} else {
    // Default order by id descending
    $order_by = "customer_return.return_id DESC";
}

// Pagination
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (
        vw_agent.ag_name LIKE '%$search%' OR 
        products.product_name LIKE '%$search%' OR
        customer_return.batch_id LIKE '%$search%' OR 
        customer_return.qty_return LIKE '%$search%' OR 
        customer_return.return_date LIKE '%$search%' OR
        customer_return.return_reason LIKE '%$search%' OR
        _createuser.FullName LIKE '%$search%' 
    )";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch filtered data
    $query = "
        SELECT customer_return.*, 
               products.product_name, 
               vw_agent.ag_name, 
               _createuser.FullName
        FROM customer_return
        LEFT JOIN products ON customer_return.product_id = products.product_id
        LEFT JOIN vw_agent ON customer_return.customer_id = vw_agent.ag_id
        LEFT JOIN _createuser ON customer_return.created_by = _createuser.UserId
        WHERE customer_return.deleted_at IS NULL AND $wherecond
        ORDER BY $order_by
        LIMIT $length OFFSET $start
    ";
    $returnData = $obj->rawSql($query);

    // Add serial numbers
    $i = $start + 1;
    foreach ($returnData as &$row) {
        $row['sl'] = $i++;
        $row['sl'] = $i++;
        $model_ids = json_decode($row['model_id'], true);  // Decode as an array

        if (!is_array($model_ids)) {
            $model_ids = [];  // Ensure it's an array (in case of decoding errors or wrong format)
        }

        $model_numbers = [];  // Initialize an array to store model numbers
        foreach ($model_ids as $key => $model_id) {
            // Ensure the model_id is numeric or valid
            $model = $obj->rawSqlSingle("SELECT * FROM product_model WHERE id = $model_id");

            // If the model is found, store all relevant data in an associative array
            if ($model) {
                $model_numbers[$key] = [
                    'model_no'    => $model['model_no'],
                    'serial_no'   => $model['serial_no'],
                    'expire_date' => $model['expire_date']
                ];
            }
        }

        // Now assign the model_numbers array back to 'model_id' (or another key if needed)
        $row['model_id'] = $model_numbers;  // This will be an array of associative arrays
    }

    // Total records without filtering
    $totalDataQuery = "
        SELECT COUNT(*) AS total 
        FROM customer_return 
        WHERE deleted_at IS NULL
    ";
    $totalData = $obj->rawSql($totalDataQuery)[0]['total'];

    // Total records after filtering
    $totalFilteredQuery = "
       SELECT COUNT(*) AS total 
FROM customer_return
LEFT JOIN products ON customer_return.product_id = products.product_id
LEFT JOIN vw_agent ON customer_return.customer_id = vw_agent.ag_id
LEFT JOIN _createuser ON customer_return.created_by = _createuser.UserId
WHERE customer_return.deleted_at IS NULL 
AND $wherecond
ORDER BY $order_by;

    ";
    $totalFilteredResult = $obj->rawSql($totalFilteredQuery);
    $totalFiltered = $totalFilteredResult[0]['total'] ?? 0;

    // Return JSON response
    echo json_encode([
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => $totalData,
        "recordsFiltered" => $totalFiltered,
        "data" => $returnData
    ]);
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $customer = $_POST['customer'];
    $sale_date = $_POST['sale_date'];
    $batch_id = $_POST['batch_id'];
    $stock_id = $_POST['stock_id'];
    $reason = $_POST['reason'];
    $models =  isset($_POST['models']) ? $_POST['models'] : [];
    $modelsJson = json_encode($models);

    $stmt = $obj->insertData('customer_return', ['product_id' => $product, 'batch_id' => $batch_id, 'qty_return' => $quantity, 'customer_id' => $customer, 'return_reason' => $reason, 'stock_id' => $stock_id, 'model_id' => $modelsJson, 'return_date' => $sale_date, 'created_by' => $userid, 'created_at' => date('Y-m-d H:i:s')]);


    $sale_check = $obj->rawSqlSingle("SELECT * FROM `sales` WHERE `sale_id` = '$id'");

    if ($sale_check) {

        if (!empty($models)) {
            // $modelsJson = json_decode($models);
            foreach ($models as $model) {
                $updateModel = $obj->updateData('product_model', [
                    'sold' => 0,
                    'updated_by' => $userid
                ], ['id' => $model]);
                // If updating the model fails, return an error and stop the process
                if (!$updateModel) {
                    echo json_encode(['success' => false, 'status' => 'Failed to update product model']);
                    exit;
                }
            }
        }
        $update_sale = $obj->updateData('sales', ['return_qty' => $sale_check['return_qty'] + $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid], ['sale_id' => $id]);


        $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$product' AND `batch_id` = '$batch_id' AND `deleted_at` IS NULL");
        if ($stock_check) {
            $update_stock = $obj->updateData('stock', ['current_stock' => $stock_check['current_stock'] + $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid], ['product_id' => $product, 'batch_id' => $batch_id]);

            if ($update_stock) {
                echo json_encode(['success' => true, 'status' => 'Added successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'status' => 'Failed to add stock']);
                exit;
            }
        }
    }
}
