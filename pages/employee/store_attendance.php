<?php

$employees_data = $obj->getAllData("tbl_employee");
$date_time = Date('Y-m-d');

// Handle form submission
if (isset($_POST['attendance'])) {
   
        $employeeId = $_POST['hiden_em_id'];
        $inTime = $_POST['inTime']; // Using employeeId as key to get inTime
        $outTime = $_POST['outTime'];
        $status = $_POST['status'];
        $attendanceDate = $_POST['attendance_date'];

  // Check if attendance already exists for this employee and date
        
        // $existingAttendance = $obj->rawSql("SELECT * FROM attendance WHERE  employee_id = '$employeeId' AND attendance_date = '$date_time';");
        // $obj->getSingleData("attendance",['where'=>['employee_id','=',$employeeId],['attendance_date','=',$attendanceDate]]);
        $attendanceData = [
            'employee_id' => $employeeId,
            'in_time' => $inTime,
            'out_time' => $outTime,
            'attendance_date' => $attendanceDate,
            'status' => $status,
        ];

        // If the attendance already exists, update it
        // if (!empty($existingAttendance)) {
        //     $obj->updateData('attendance', $attendanceData, ['where'=>['employee_id','=',$employeeId]]);
        // } else {
            $obj->insertData('attendance', $attendanceData);
        // }
        $obj->notificationStore("Attendance Add Successfull",'success');
        echo ' <script>window.location="?page=attedance"; </script>';exit;

    exit();
}
if (isset($_POST['search'])) {
    $employee_search =$_POST['employee_search'];
    //$expenseDetails = $obj->getAllData("vw_account", "entry_date BETWEEN '" . date('Y-m-d', strtotime($dateform)) . "' and '" . date('Y-m-d', strtotime($dateto)) . "' AND acc_type='1' ORDER BY entry_date DESC");
    $employee_search_data = $obj->rawSql("SELECT * FROM tbl_employee WHERE id = '$employee_search'");
}
?>
