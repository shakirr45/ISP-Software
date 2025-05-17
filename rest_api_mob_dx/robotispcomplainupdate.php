<?php
    include("dbconfig_robotisp.php");
    
    if($_SERVER['REQUEST_METHOD']=='POST'){

        $complain_id = $_POST['complain_id'];

        //Generation
		$details = $_POST['details'];
        $note = $_POST['note'];
        $customer_id = $_POST['customer_id'];
        $status = $_POST['status'];
        $solve_by = $_POST['solve_by'];
        $entry_by = $_POST['entry_by'];
        $update_by = $_POST['update_by'];
        $complain_date = $_POST['complain_date'];
        $solve_date = $_POST['solve_date'];
        $entry_date = date("Y-m-d");

        $id_helper = (int)$complain_id;

        $sql_update_worker = "UPDATE tbl_complains SET 
        details ='$details', 
        note = '$note', 
        customer_id = $customer_id, 
        status = $status, 
        solve_by = $solve_by, 
        entry_by = $entry_by, 
        update_by = $update_by,
        complain_date = '$complain_date', 
        solve_date = '$solve_date', 
        entry_date = '$entry_date'
        WHERE id = $id_helper";
        
        // $check_duplicate_data = "SELECT phone FROM worker_table WHERE phone = '$phone' ";
        // $result = mysqli_query($con, $check_duplicate_data);
        // $count = mysqli_num_rows($result);
        if(mysqli_query($con,$sql_update_worker)){
            echo "Congrats , Your Information is successfully updated";
        }else{
            echo "Database Connection Error !";
        } 
	}
?>