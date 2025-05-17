<?php
include("dbconfig_robotisp.php");
    if($_SERVER['REQUEST_METHOD']=='POST'){
      
        //Post value
        $acc_id = $_POST['acc_id'];
        $acc_head_id = $_POST['acc_head_id'];
        $acc_sub_head = $_POST['acc_sub_head'];
        $acc_amount = $_POST['acc_amount'];
        $acc_description = $_POST['acc_description'];
        $update_date = date('Y-m-d H:i:s');

        //int id for manipulation
        $int_id = (int)$acc_id;
        
        $acc_head_id_int = (int)$acc_head_id;
        $acc_sub_head_id_int = (int)$acc_sub_head;

        $sql_update = "UPDATE tbl_account SET 
        acc_head = $acc_head_id_int, 
        acc_sub_head = $acc_sub_head_id_int,
        acc_description = '$acc_description',
        acc_amount = $acc_amount,
        last_update = '$update_date' 
        WHERE acc_id = $int_id";

        if(mysqli_query($con,$sql_update)){
           echo "Expense is Updated !";
        }else{
           echo "Error Happened !";
        }
    }
?>