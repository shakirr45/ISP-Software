<?php
    include("dbconfig_robotisp.php");
    date_default_timezone_set('Asia/Dhaka');
    
    if($_SERVER['REQUEST_METHOD']=='POST'){    
     
		$acc_name = $_POST['acc_name'];
        $acc_desc = $_POST['acc_desc'];
        $entry_by = $_POST['entry_by'];
        $acc_status = $_POST['acc_status'];
        $acc_type = $_POST['acc_type'];
        $entry_date = date("Y-m-d");
        $update_by = $entry_by;
        $level = $_POST['level'];
        $parent_id = $_POST['parent_id'];
    
        $sql = "INSERT INTO tbl_accounts_head (parent_id, acc_name, acc_type, acc_desc, level, acc_status, entry_by, entry_date, update_by) 
        VALUES 
        ($parent_id, '$acc_name', $acc_type, '$acc_desc', $level, $acc_status, $entry_by, '$entry_date', $update_by)";
        
        if(mysqli_query($con,$sql)){
            echo "Account Sub-head Added !";
        }else{
            echo "Error Happend !";
        }
    }
		
?>