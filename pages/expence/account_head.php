
<?php

$date_time = Date('Y-m-d');
$userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;
// insert 
if (isset($_POST['submit'])) {
    // Update Accout head
    if (isset($_POST['head_id']) && !empty($_POST['head_id'])) {


        $fromUpdate = [
            'acc_name' => $_POST['name'],
            'acc_type' => 1,
            'acc_desc' => $_POST['details'],
            'acc_status' => $_POST['status'],

            'entry_by' => $userId,
            'entry_date' => $date_time,
            'update_by' => $userId
        ];

        $obj->updateData("tbl_accounts_head", $fromUpdate, ['acc_id' => $_POST['head_id']]);

        $obj->notificationStore(" Account head Update Successfull", 'success');
        echo ' <script>window.location="?page=account_head_view"; </script>';
        exit;
    }

    // insert account head
    elseif (isset($_POST['name'])) {
        $fromInsert = [
            'acc_name' => $_POST['name'],
            'acc_type' => 1,
            'acc_desc' => $_POST['details'],
            'acc_status' => $_POST['status'],

            'entry_by' => $userId,
            'entry_date' => $date_time,
            'update_by' => $userId
        ];


        $service_add = $obj->insertData("tbl_accounts_head", $fromInsert);
        $obj->notificationStore("New Account Head Add  Successfull", 'success');
        echo ' <script>window.location="?page=account_head_view"; </script>';
        exit;
    }
    // Update account sub head 
    elseif (!empty($_POST['sub_account_id']) && !empty($_POST['subname']) && !empty($_POST['sub_account_id'])) {
        $fromUpdate = [
            'parent_id' => $_POST['parent_id'],
            'acc_name' => $_POST['subname'],
            'acc_desc' => $_POST['details'],
            'acc_status' => $_POST['status'],
            'update_by' => $userId
        ];
        var_dump($_POST);

        $obj->updateData("tbl_accounts_head", $fromUpdate, ['acc_id' => $_POST['sub_account_id']]);
        $obj->notificationStore(" Sub Account Update Successfull", 'success');
        echo ' <script>window.location="?page=account_sub_head_view"; </script>';
        exit;
    }
    // insert account sub head 
    elseif (isset($_POST['subname']) && isset($_POST['parent_id'])) {

        $fromInsert = [
            'acc_name' => $_POST['subname'],
            'acc_type' => 1,
            'acc_desc' => $_POST['details'],
            'acc_status' => $_POST['status'],
            'parent_id' => $_POST['parent_id'],
            'level' => 2,
            'entry_by' => $userId,
            'entry_date' => $date_time,
            'update_by' => $userId
        ];




        $service_add = $obj->insertData("tbl_accounts_head", $fromInsert);
        $obj->notificationStore("New Sub Account Add Successfull", 'success');
        echo ' <script>window.location="?page=account_sub_head_view"; </script>';
        exit;
    } else {
        $obj->notificationStore("please Provide valid data", 'warning');
        if (isset($_POST['zone_name'])) {
            echo ' <script>window.location="?page=zone_view"; </script>';
        } elseif (isset($_POST['subzone_name'])) {
            echo ' <script>window.location="?page=subzone_view"; </script>';
        } elseif (isset($_POST['destination_name'])) {
            echo ' <script>window.location="?page=destination_view"; </script>';
        }
    }
    exit;
}

$obj->notificationShow();
$viewAccountHead = $obj->getAllData("tbl_accounts_head", ['where' => ['level', '=', '1']]);
$viewAccountSubHead  = $obj->getAllData("tbl_accounts_head", ['where' => ['level', '=', '2']]);

?>