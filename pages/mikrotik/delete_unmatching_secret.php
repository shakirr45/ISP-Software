<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Check if 'mkid' is provided in the GET request
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    if ($obj->singleDeleteData('tbl_agent', "ag_id='$user_id'")) {
        $msg = 'Delete Successful';
        $response = [
            'status' => $msg,
            'success' => true,
        ];
    } else {
        $msg = 'Delete Failed';
        $response = [
            'status' => $msg,
            'success' => false,
        ];
    }
}
echo json_encode($response);
exit;
