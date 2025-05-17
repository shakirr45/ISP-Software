<?php
    include("dbconfig_robotisp.php");
    date_default_timezone_set('Asia/Dhaka');
    
    if($_SERVER['REQUEST_METHOD']=='POST'){
        
        $last_update = date('Y-m-d');
		$acc_head = $_POST['acc_head'];
		$acc_sub_head = $_POST['acc_sub_head'];
        $acc_amount = $_POST['acc_amount'];
        $acc_description = $_POST['acc_description'];
        $acc_type  = 1;
        $entry_by  = $_POST['entry_by'];
        $entry_date = date('Y-m-d');
        $update_by = $entry_by;
      
       
        $sql = "INSERT INTO tbl_account (acc_head, acc_sub_head, acc_amount, acc_description, acc_type, entry_by, entry_date, update_by, last_update) 
        VALUES 
        ($acc_head, $acc_sub_head, $acc_amount,'$acc_description', $acc_type, $entry_by,'$entry_date', $update_by, '$last_update')";
        
        if(mysqli_query($con,$sql)){
            echo "Expense Added !";
        }else{
            echo "Error Happend !";
        }
    }
		
?>