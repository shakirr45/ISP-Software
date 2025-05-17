<?php
session_start();

require(realpath(__DIR__ . '/../../../services/Model.php'));
$obj = new Model();

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['id'])) {
    $batch_id = $_POST['batch_id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $supplier = $_POST['supplier'];
    $purchase_date = $_POST['purchase_date'];
    $modelNos = $_POST['modelNos'];
    $serialNos = $_POST['serialNos'];
    $expireDates = $_POST['expireDates'];
    $stmt = $obj->insertData('purchases', ['product_id' => $product, 'quantity' => $quantity, 'supplier_id' => $supplier, 'purchase_date' => $purchase_date, 'created_by' => $userid]);

    if ($stmt) {

        $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$product' AND `supplier_id` = '$supplier' AND `deleted_at` IS NULL");

        if ($stock_check) {
            $update_stock = $obj->updateData('stock', ['current_stock' => $stock_check['current_stock'] + $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid], ['product_id' => $product, 'supplier_id' => $supplier]);

            foreach ($modelNos as $index => $modelNo) {
                $serialNo = $serialNos[$index];
                $expireDate = $expireDates[$index];
                $modelInsert = $obj->insertData('product_model', ['product_id' => $product, 'batch_id' => $stock_check['batch_id'], 'model_no' => $modelNo, 'serial_no' => $serialNo, 'purchase_id' => $stmt, 'expire_date' => $expireDate, 'created_by' => $userid]);
            }

            if ($update_stock) {
                echo json_encode(['success' => true, 'status' => 'Added successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'status' => 'Failed to add']);
                exit;
            }
        } else {


            $insert_stock = $obj->insertData('stock', [
                'product_id' => $product,
                'supplier_id' => $supplier,
                'batch_id' => $batch_id,
                'current_stock' => $quantity,
                'created_by' => $userid,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($insert_stock) {
                $stockId = $obj->rawSqlSingle("SELECT * FROM stock WHERE stock_id = '$insert_stock'");
                $stock_batch = $stockId['batch_id'];

                foreach ($modelNos as $index => $modelNo) {
                    $serialNo = $serialNos[$index];
                    $expireDate = $expireDates[$index];
                    $modelInsert = $obj->insertData('product_model', ['product_id' => $product, 'batch_id' => $stock_batch, 'model_no' => $modelNo, 'serial_no' => $serialNo, 'purchase_id' => $stmt, 'expire_date' => $expireDate, 'created_by' => $userid]);
                }

                echo json_encode(['success' => true, 'status' => 'Added successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'status' => 'Failed to add stock']);
                exit;
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $batch_id = $_POST['batch_id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $supplier = $_POST['supplier'];
    $purchase_date = $_POST['purchase_date'];
    $models = $_POST['models'];

    // Fetch the old purchase details

    $oldPurchase = $obj->rawSqlSingle("SELECT * FROM `purchases` WHERE `purchase_id` = '$id'");
    if (!$oldPurchase) {
        echo json_encode(['success' => false, 'status' => 'Purchase not found']);
        exit;
    }

    $oldProductId = $oldPurchase['product_id'];
    $oldQuantity = $oldPurchase['quantity'];
    $oldSupplierId = $oldPurchase['supplier_id'];

    // Update the purchase record
    $updatePurchase = $obj->updateData(
        'purchases',
        [
            'product_id' => $product,
            'quantity' => $quantity,
            'supplier_id' => $supplier,
            'purchase_date' => $purchase_date,
            'updated_by' => $userid,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        ['purchase_id' => $id]
    );

    if ($updatePurchase > 0) {


        $oldStock = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$oldProductId' AND `supplier_id` ='$oldSupplierId' AND `deleted_at` IS NULL");
        if ($oldStock) {
            $obj->updateData(
                'stock',
                ['current_stock' => $oldStock['current_stock'] - $oldQuantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid],
                ['product_id' => $oldProductId, 'supplier_id' => $oldSupplierId]
            );
        }

        $stock_check = $obj->rawSqlSingle("SELECT * FROM `stock` WHERE `product_id` = '$product' AND `supplier_id` = '$supplier' AND `deleted_at` IS NULL");

        if ($stock_check) {
            // Update stock for the new product
            $obj->updateData(
                'stock',
                ['current_stock' => $stock_check['current_stock'] + $quantity, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userid],
                ['product_id' => $product, 'supplier_id' => $supplier]
            );
            $obj->updateData('product_model', ['product_id' => $product, 'batch_id' => $stock_check['batch_id'], 'updated_by' => $userid], ['purchase_id' => $id]);
        } else {
            // Insert new stock record for the new product
            $insert_stock = $obj->insertData('stock', [
                'product_id' => $product,
                'supplier_id' => $supplier,
                'batch_id' => $batch_id,
                'current_stock' => $quantity,
                'created_by' => $userid,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($insert_stock) {
                $stockId = $obj->rawSqlSingle("SELECT * FROM stock WHERE stock_id = '$insert_stock'");
                $stock_batch = $stockId['batch_id'];
                $obj->updateData('product_model', ['product_id' => $product, 'batch_id' => $stock_batch, 'updated_by' => $userid], ['purchase_id' => $id]);
            }
        }

        echo json_encode(['success' => true, 'status' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'status' => 'Failed to update']);
    }
    exit;
}

// Fetch Categories (for DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['purchaseId'])) {

    $purchases = $obj->raw_sql("SELECT 
        p.*, 
        pro.product_name, 
        s.supplier_name, 
        st.batch_id,
        _createuser.FullName
    FROM purchases p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
    LEFT JOIN products pro ON p.product_id = pro.product_id
    LEFT JOIN stock st ON p.product_id = st.product_id AND p.supplier_id = st.supplier_id
    LEFT JOIN _createuser ON p.created_by = _createuser.UserId WHERE p.deleted_at IS NULL;");

    $i = 1; // Initialize counter
    foreach ($purchases as &$row) {
        $row['sl'] = $i++; // Add the row number
    }
    echo json_encode($purchases);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['purchaseId'])) {
    $purchaseId = $_GET['purchaseId'];
    $models = $obj->rawSql("SELECT * FROM `product_model` WHERE `purchase_id` = '$purchaseId' AND `returned`= 0 AND `sold`= 0 AND `deleted_at` IS NULL");
    $i = 1; // Initialize counter
    foreach ($models as &$row) {
        $row['sl'] = $i++; // Add the row number
    }
    echo json_encode(['success' => true, 'models' => $models]);
    exit;
}
