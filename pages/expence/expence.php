
<?php

$date_time = Date('Y-m-d');
$token = isset($_GET['delete-token']) ? $_GET['delete-token'] : NULL;

$firsDayOfMonth = new DateTime('first day of this month');
$obj->deleteData("tbl_account", ['where' => ['acc_id', '=', $token]]);
// insert 
if (isset($_POST['submit'])) {
    // Update Accout head
    if (isset($_POST['account_id']) && !empty($_POST['account_id'])) {


        $fromUpdate = array(

            'acc_head' => $_POST['parent_id'],
            'acc_sub_head' => $_POST['child_id'],
            'acc_amount' => $_POST['amount'],
            'acc_description' => $_POST['details'],
            'update_by' => $userId
        );

        $obj->updateData("tbl_account", $fromUpdate, ['acc_id' => $_POST['account_id']]);

        $obj->notificationStore(" Expence Update Successfull", 'success');
        echo ' <script>window.location="?page=view_expence"; </script>';
        exit;
    }

    // insert account head
    elseif (isset($_POST['parent_id']) && ($_POST['child_id']) && ($_POST['amount'])) {
        $form_data = array(

            'acc_head' => $_POST['parent_id'],
            'acc_sub_head' => $_POST['child_id'],
            'acc_type' => '1',
            'acc_amount' => $_POST['amount'],
            'acc_description' => $_POST['details'],

            'entry_by' => $userId,
            'entry_date' => $date_time,
            'update_by' => $userId
        );


        $service_add = $obj->insertData("tbl_account", $form_data);
        $obj->notificationStore("New Expence Add  Successfull", 'success');
        echo ' <script>window.location="?page=view_expence"; </script>';
        exit;
    } else {
        $obj->notificationStore("please Provide valid data", 'warning');
    }
    exit;
}

$obj->notificationShow();
$firsDayOfMonth = new DateTime('first day of this month');

if (isset($_POST['search'])) {

    $dateform = date('Y-m-d', strtotime($_POST['dateform']));
    $dateto = date('Y-m-d', strtotime($_POST['dateto']));
    //$expenseDetails = $obj->getAllData("vw_account", "entry_date BETWEEN '" . date('Y-m-d', strtotime($dateform)) . "' and '" . date('Y-m-d', strtotime($dateto)) . "' AND acc_type='1' ORDER BY entry_date DESC");
    $expenseDetails = $obj->rawSql("SELECT * FROM tbl_account WHERE entry_date BETWEEN '$dateform' AND '$dateto' AND acc_type = '1' ORDER BY entry_date DESC");
} else {

    $dateform = $firsDayOfMonth->format('Y-m-d');
    $dateto = date('Y-m-d');
    $expenseDetails = $obj->rawSql("SELECT * FROM tbl_account WHERE MONTH(entry_date) = MONTH(CURRENT_DATE) AND YEAR(entry_date) = YEAR(CURRENT_DATE) AND acc_type = '1' ORDER BY entry_date DESC;");
    //$obj->getAllData("vw_account", "MONTH(entry_date)='" . date('m') . "' and YEAR(entry_date)='" . date('Y') . "'  AND acc_type='1' ORDER BY entry_date DESC");
}
$previewDate = date('d M Y', strtotime($dateform)) . ' to ' . date('d M Y', strtotime($dateto));
$viewAccountHead = $obj->getAllData("tbl_accounts_head", ['where' => ['level', '=', '1']]);
$viewAccountSubHead  = $obj->getAllData("tbl_accounts_head", ['where' => ['level', '=', '2']]);
// echo $token;


?>