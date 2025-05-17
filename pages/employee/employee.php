<?php

// End edit

$userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

// var_dump($obj->checkConnection($mikrotikget));
if (isset($_POST['submit'])) {

    if (isset($_POST['name']) && (isset($_POST['phone']))) {
        $data = $obj->getSingleData("tbl_employee");
        $data = $obj->rawSql("SELECT id FROM tbl_employee ORDER BY id DESC LIMIT 1");

        if (!$data) {
            $data[0]["id"]  = 0;
        }
        if (($data[0]["id"] + 1) < 10) {
            $STD = "EMPL0000"; // EMPL for employee
        } else if (($data[0]["id"]  + 1) < 100) {
            $STD = "EMPL000";
        } else if (($data[0]["id"] + 1) < 1000) {
            $STD = "EMPL00";
        } else if (($data[0]["id"] + 1) < 10000) {
            $STD = "EMPL0";
        } else {
            $STD = "EMPL";
        }
        $STD .= $data[0]["id"] + 1;


        $fromInsert = [

            'employee_id' => $STD,
            'employee_name' => $_POST['name'],
            'employee_mobile_no' => $_POST['phone'],
            'employee_address' => $_POST['address'],
            'employee_email' => $_POST['email'],
            'employee_national_id' => $_POST['nid'],
            'designation' => $_POST['designation'],
            'joining_date' => $_POST['joining_date'],
            'employee_status' => $_POST['activeStatus'],
            'entry_by' => $userId,
            'entry_date' => date('Y-m-d'),

        ];


        $obj->insertData('tbl_employee', $fromInsert);



        $obj->notificationStore("New Employee Add  Successfull", 'success');
        echo ' <script>window.location="?page=employee_view"; </script>';
        exit;
    }
    exit;
}
if (isset($_POST['update'])) {
    if (isset($_POST['em_id'])) {
        $fromUpdate = [

            'employee_name' => $_POST['name'],
            'employee_mobile_no' => $_POST['phone'],
            'employee_address' => $_POST['address'],
            'employee_email' => $_POST['email'],
            'employee_national_id' => $_POST['nid'],
            'designation' => $_POST['designation'],
            'joining_date' => $_POST['joining_date'],
            'employee_status' => $_POST['activeStatus'],
            'update_by' => $userId

        ];

        $obj->updateData('tbl_employee', $fromUpdate, ['id' => $_POST['em_id']]);

        $obj->notificationStore("Employe update  Successfull", 'success');
        echo ' <script>window.location="?page=employee_view"; </script>';
        exit;
    }
}



$obj->notificationShow();
