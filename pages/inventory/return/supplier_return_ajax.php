<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$wherecond = '1=1'; // Default condition to avoid empty WHERE clause

// Filtering by customer
if (isset($_GET['supplier']) && !empty($_GET['supplier'])) {
    $supplier = intval($_GET['supplier']);
    $wherecond .= " AND supplier_return.supplier_id = $supplier";
}

// Filtering by product
if (isset($_GET['product']) && !empty($_GET['product'])) {
    $product = intval($_GET['product']);
    $wherecond .= " AND supplier_return.product_id = $product";
}

// Filtering by return date
if (isset($_GET['datefrom']) && !empty($_GET['datefrom'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $wherecond .= " AND DATE(supplier_return.return_date) >= '$dateFrom'";
}
if (isset($_GET['dateto']) && !empty($_GET['dateto'])) {
    $dateTo = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND DATE(supplier_return.return_date) <= '$dateTo'";
}

$order_column = $_GET['order'][0]['column'] ?? '';  // Column index
$order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)
$columns = [
    'return_id',
    'supplier_name',
    'product_name',
    'quantity_returned',
    'model_id',
    'return_date',
    'return_reason',
    'FullName',
];
// Construct the ORDER BY clause dynamically
if (isset($order_column) && isset($columns[$order_column])) {
    if ($order_column == "1" || $order_column == "5") {
        $order_by = "supplier_return.supplier_id " . $order_dir;
    } elseif ($order_column == "2") {
        $order_by = "supplier_return.product_id " . $order_dir;
    } elseif ($order_column == "7") {
        $order_by = "supplier_return.created_by " . $order_dir;
    } else {
        // Else use the valid column name from the array
        $order_by = "supplier_return." . $columns[$order_column] . ' ' . $order_dir;
    }
} else {
    // Default order by id descending
    $order_by = "supplier_return.return_id DESC";
}

// Pagination
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (
        suppliers.supplier_name LIKE '%$search%' OR 
        products.product_name LIKE '%$search%' OR
        supplier_return.quantity_returned LIKE '%$search%' OR
        supplier_return.return_date LIKE '%$search%' OR
        supplier_return.return_reason LIKE '%$search%' OR
        _createuser.FullName LIKE '%$search%' 
    )";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $returnData = $obj->rawSql("
     SELECT supplier_return.*,
           products.product_name,
           suppliers.supplier_name,
           _createuser.FullName
    FROM supplier_return
    LEFT JOIN products ON supplier_return.product_id = products.product_id
    LEFT JOIN suppliers ON supplier_return.supplier_id = suppliers.supplier_id
    LEFT JOIN _createuser ON supplier_return.created_by = _createuser.UserId
    WHERE supplier_return.deleted_at IS NULL AND $wherecond
    ORDER BY $order_by
    LIMIT $length OFFSET $start
    ");

    // Add serial numbers
    $i = 1;
    foreach ($returnData as &$row) {
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
        FROM supplier_return 
        WHERE deleted_at IS NULL
    ";
    $totalData = $obj->rawSql($totalDataQuery)[0]['total'];

    // Total records after filtering
    $totalFilteredQuery = "
        SELECT COUNT(*) AS total 
    FROM supplier_return
    LEFT JOIN products ON supplier_return.product_id = products.product_id
    LEFT JOIN suppliers ON supplier_return.supplier_id = suppliers.supplier_id
    LEFT JOIN _createuser ON supplier_return.created_by = _createuser.UserId
    WHERE supplier_return.deleted_at IS NULL AND $wherecond
    ORDER BY $order_by
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
    // Get POST data
    $id = $_POST['id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $supplier = $_POST['supplier'];
    $return_date = $_POST['return_date'];
    $reason = $_POST['reason'];
    $models = $_POST['models'];
    // foreach ($models as $key => $model) {
    //     $stmt = $obj->rawSqlSingle("SELECT * FROM product_model WHERE purchase_id = $id AND serial_no = '$model[serialNo]'");

    //     if ($stmt) {
    //         // Append the 'id' from the $model to the $product_model array
    //         $product_model[] = $stmt['id'];
    //     } else {
    //         echo json_encode(['success' => false, 'status' => 'Model not found']);
    //         exit;
    //     }
    // }
    $product_model_json = json_encode($models);

    // Insert data into 'supplier_return'
    $insertReturnData = $obj->insertData('supplier_return', [
        'product_id' => $product,
        'quantity_returned' => $quantity,
        'supplier_id' => $supplier,
        'return_date' => $return_date,
        'purchase_id' => $id,
        'model_id' => $product_model_json,
        'return_reason' => $reason,
        'created_by' => $userid
    ]);

    // Check if insert was successful
    if (!$insertReturnData) {
        echo json_encode(['success' => false, 'status' => 'Failed to record return data']);
        exit;
    }

    // Process models (update 'product_model' for each model)
    if (!empty($models)) {
        foreach ($models as $model) {
            $updateModel = $obj->updateData('product_model', [
                'returned' => 1,
                'updated_by' => $userid
            ], ['purchase_id' => $id, 'id' => $model]);
            // If updating the model fails, return an error and stop the process
            if (!$updateModel) {
                echo json_encode(['success' => false, 'status' => 'Failed to update product model']);
                exit;
            }
        }
    }

    // Check if the purchase exists
    $purchase_check = $obj->rawSqlSingle("SELECT * FROM `purchases` WHERE `purchase_id` = $id");
    if ($purchase_check) {
        // Update purchase record with returned quantity
        $updatePurchase = $obj->updateData('purchases', [
            'return_qty' => $purchase_check['return_qty'] + $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userid
        ], ['purchase_id' => $id]);

        // If updating the purchase fails
        if (!$updatePurchase) {
            echo json_encode(['success' => false, 'status' => 'Failed to update purchase']);
            exit;
        }

        // Check if the stock exists for the given product and supplier
        $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = $product AND `supplier_id` = $supplier AND `deleted_at` IS NULL");

        if ($stock_check) {
            // Update stock
            $updateStock = $obj->updateData('stock', [
                'current_stock' => $stock_check['current_stock'] - $quantity,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userid
            ], ['product_id' => $product, 'supplier_id' => $supplier]);

            // If stock update fails
            if (!$updateStock) {
                echo json_encode(['success' => false, 'status' => 'Failed to update stock']);
                exit;
            }

            // If everything is successful
            echo json_encode(['success' => true, 'status' => 'Added successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'status' => 'Stock not found for this product']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'status' => 'Purchase not found']);
        exit;
    }
}
