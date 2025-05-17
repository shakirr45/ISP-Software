<?php
session_start();
include '../services/Model.php';
$obj = new Model();

if ($_SERVER['REQUEST_METHOD']=='POST') {

    $userId = $_POST['user_id'];

    header('Content-Type: application/json');
    // Initial response
    $response = ['success' => false, 'message' => 'Invalid Request'];

    $agent = $obj->getSingleData('tbl_agent', ['where' => ['ag_id', '=', $_POST['ag_id']]]);
    $cus_id = $agent['cus_id'];

    try {
    // Check if any of the required parameters are present
    if ((isset($_POST['amount']) && $_POST['amount'] > 0) ||
        (isset($_POST['discount']) && $_POST['discount'] > 0) ||
        (isset($_POST['ag_id']) && $_POST['ag_id'])
    ) {

        // Bill Payment
        if (isset($_POST['amount']) && $_POST['amount'] > 0) {
            $stmt = $obj->rawSql("SELECT function_bill_update(" . $_POST['ag_id'] . ", 'billpay', " . $_POST['amount'] . ", '$cus_id', '',$userId) AS function_bill_update");
            $response['billPayment'] = 'Bill payment updated successfully';
        }

        // Discount
        if (isset($_POST['discount']) && $_POST['discount'] > 0) {
            $month = date('M-Y');
            $stmt = $obj->rawSql("SELECT function_bill_update(" . $_POST['ag_id'] . ", 'discount', " . $_POST['discount'] . ", '', '',$userId) AS function_bill_update");
            $response['discount'] = 'Discount applied successfully';
        }

        // Customer Activation
        if (isset($_POST["ag_id"]) && $_POST["ag_id"]) {
            $customerSingle = $obj->getSingleData('tbl_agent', ['where' => ['ag_id', '=', $_POST['ag_id']]]);
            if ($customerSingle) {
                $obj->enableSingleSecret($customerSingle['mikrotik_id'], $customerSingle['ip']);
                $response['customerActivation'] = 'Customer activated successfully';
            } else {
                $response['customerActivation'] = 'Customer not found';
            }
        }

        // If we've reached this point, at least one operation was successful
        $obj->notificationStore('Pay Operations processed successfully.', 'success');

        // echo $userId;
        
        $response['success'] = true;
        $response['message'] = '1';
    }
} catch (Exception $e) {
    // $obj->notificationStore('Pay Operations processed Failed.', 'error');
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
}
