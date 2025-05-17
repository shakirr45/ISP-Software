<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$limit = isset($_GET['length']) ? intval($_GET['length']) : 10;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$search = $_GET['search']['value'] ?? '';
$wherecond = '';
if (!empty($search)) {
    $wherecond .= " AND (p.product_name LIKE '%$search%' OR  ag.ag_name LIKE '%$search%' OR  ag.ag_mobile_no LIKE '%$search%' )";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $customer = $_POST['customer'];
    $sale_date = $_POST['sale_date'];
    $batch_id = $_POST['batch_id'];
    $stock_id = $_POST['stock_id'];
    $model_products = $_POST['model_products'];
    // Convert the model_products array into a JSON string
    $model_products_json = json_encode($model_products);
    $stmt = $obj->insertData('sales', ['product_id' => $product, 'model_id' => $model_products_json, 'batch_id' => $batch_id, 'quantity' => $quantity, 'customer_id' => $customer, 'stock_id' => $stock_id, 'sale_date' => $sale_date, 'created_by' => $userid, 'created_at' => date('Y-m-d H:i:s')]);
    $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$product' AND `batch_id` = '$batch_id' AND `deleted_at` IS NULL");

    if ($stock_check && $stmt) {
        foreach ($model_products as $key => $model_id) {
            $update_model = $obj->updateData('product_model', ['sold' => 1, 'updated_by' => $userid], ['id' => $model_id]);
        }
        $update_stock = $obj->updateData('stock', ['current_stock' => $stock_check['current_stock'] - $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid], ['product_id' => $product, 'batch_id' => $batch_id]);

        if ($update_stock) {
            echo json_encode(['success' => true, 'status' => 'Added successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'status' => 'Failed to add stock']);
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['action'])) {
    $id = $_POST['id'];
    $batch_id = $_POST['batch_id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $customer = $_POST['customer'];
    $sale_date = $_POST['sale_date'];
    $stock_id = $_POST['stock_id'];
    $models = $_POST['models'];
    $model_products_json = json_encode($models);

    // Fetch the old purchase details

    $oldSale = $obj->rawSqlSingle("SELECT * FROM `sales` WHERE `sale_id` = '$id'");
    if (!$oldSale) {
        echo json_encode(['success' => false, 'status' => 'Sale not found']);
        exit;
    }
    $oldProductId = $oldSale['product_id'];
    $oldStockId = $oldSale['stock_id'];
    $oldQuantity = $oldSale['quantity'];
    $oldCustomerId = $oldSale['customer_id'];
    $oldSaleDate = $oldSale['sale_date'];
    $oldBatchId = $oldSale['batch_id'];
    $oldCreatedBy = $oldSale['created_by'];
    $oldModelId = $oldSale['model_id'];
    $oldModelIds = json_decode($oldModelId, true);


    foreach ($oldModelIds as $key => $modelId) {
        $updateModel = $obj->updateData('product_model', ['sold' => 0, 'updated_by' => $userid], ['id' => $modelId]);
    }

    $updateSale = $obj->updateData(
        'sales',
        [
            'product_id' => $product,
            'quantity' => $quantity,
            'batch_id' => $batch_id,
            'model_id' => $model_products_json,
            'stock_id' => $stock_id,
            'customer_id' => $customer,
            'sale_date' => $sale_date,
            'updated_by' => $userid,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        ['sale_id' => $id]
    );

    foreach ($models as $key => $modelId) {
        $updateModel = $obj->updateData('product_model', ['sold' => 1, 'updated_by' => $userid], ['id' => $modelId]);
    }

    if ($updateSale > 0) {
        // Adjust stock for the old product

        $oldStock = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$oldProductId' AND `batch_id` ='$oldBatchId' AND `deleted_at` IS NULL");
        if ($oldStock) {
            $obj->updateData(
                'stock',
                ['current_stock' => $oldStock['current_stock'] + $oldQuantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid],
                ['product_id' => $oldProductId, 'batch_id' => $oldBatchId]
            );
        }
        $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$product' AND `batch_id` = '$batch_id' AND `deleted_at` IS NULL");

        if ($stock_check) {
            // Update stock for the new product
            $obj->updateData(
                'stock',
                ['current_stock' => $stock_check['current_stock'] - $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid],
                ['product_id' => $product, 'batch_id' => $batch_id]
            );
        }
        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to update']);
    }
    exit;
}

// if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['sale'])) {
//     $sales = $obj->raw_sql(
//         "
//         SELECT sales.*,
//         p.product_name,
//         ag.ag_name,
//         _createuser.FullName
//         FROM sales
//         LEFT JOIN products p ON sales.product_id = p.product_id
//         LEFT JOIN _createuser ON sales.created_by = _createuser.UserId
//         LEFT JOIN tbl_agent ag ON sales.customer_id = ag.ag_id
//         WHERE sales.quantity > 0 $wherecond order by sales.sale_id desc LIMIT $limit OFFSET $start;
//         "
//     );
//     $totalRecords = $obj->raw_sql("SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL");
//     $totalFiltered = $obj->raw_sql("SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL AND quantity > 0 LIMIT $limit OFFSET $start");

//     $i = 1; // Initialize counter
//     foreach ($sales as &$row) {
//         $row['sl'] = $i++; // Add the row number
//         $model_ids = json_decode($row['model_id'], true);  // Decode as an array

//         if (!is_array($model_ids)) {
//             $model_ids = [];  // Ensure it's an array (in case of decoding errors or wrong format)
//         }

//         $model_numbers = [];  // Initialize an array to store model numbers
//         foreach ($model_ids as $key => $model_id) {
//             // Ensure the model_id is numeric or valid
//             $model = $obj->rawSqlSingle("SELECT * FROM product_model WHERE id = $model_id");

//             // If the model is found, store all relevant data in an associative array
//             if ($model) {
//                 $model_numbers[$key] = [
//                     'model_no'    => $model['model_no'],
//                     'serial_no'   => $model['serial_no'],
//                     'expire_date' => $model['expire_date']
//                 ];
//             }
//         }

//         // Now assign the model_numbers array back to 'model_id' (or another key if needed)
//         $row['model_id'] = $model_numbers;  // This will be an array of associative arrays
//     }

//     // Total records without filtering
//     $totalDataQuery = "
//         SELECT COUNT(*) AS total 
//         FROM sales 
//         WHERE deleted_at IS NULL
//     ";
//     $totalData = $obj->rawSql($totalDataQuery)[0]['total'];

//     // Total records after filtering
//     $totalFilteredQuery = "
//         SELECT COUNT(*) AS total 
//         FROM sales 
//         WHERE deleted_at IS NULL AND quantity > 0 LIMIT $limit OFFSET $start
//     ";
//     $totalFilteredResult = $obj->rawSql($totalFilteredQuery);
//     $totalFiltered = $totalFilteredResult[0]['total'] ?? 0;
//     echo json_encode(
//         [
//             'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
//             'recordsTotal' => $totalData,
//             'recordsFiltered' => $totalFiltered,
//             'data' => $sales
//         ]
//     );
//     exit;
// } 
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sale'])) {
    $saleId = $_GET['sale'];

    // Fetch sale details
    $sale = $obj->rawSqlSingle("SELECT * FROM `sales` WHERE `sale_id` = '$saleId' AND `deleted_at` IS NULL");

    if ($sale) {
        $modelId = $sale['model_id'];
        $modelIdJson = json_decode($modelId, true);
        $model_numbers = [];

        foreach ($modelIdJson as $key => $modelId) {

            // Query for model details
            $models = $obj->rawSql("SELECT * FROM `product_model` WHERE `id` = '$modelId' AND `sold` = 1 AND `deleted_at` IS NULL");

            if ($models) {
                // Assuming $models is an array and we want to fetch the first result
                $model_numbers[$key] = [
                    'id'          => $models[0]['id'],
                    'model_no'    => $models[0]['model_no'],
                    'serial_no'   => $models[0]['serial_no'],
                    'expire_date' => $models[0]['expire_date']
                ];
            }
        }

        // Return JSON response with model numbers
        echo json_encode(['success' => true, 'models' => $model_numbers]);
    } else {
        // Handle case where the sale is not found
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
    }

    exit;
}
