<?php
include("dbconfig_robotisp.php");

    if($_SERVER['REQUEST_METHOD']=='POST'){
      
        $acc_id = $_POST['acc_id'];
        $int_id = (int)$acc_id;

        $sql_delete_acc = "DELETE FROM tbl_account WHERE acc_id = $int_id"; 

        if(mysqli_query($con,$sql_delete_acc)){
           echo "Expense is Deleted !";
        }else{
           echo "Error Happened !";
        }
       }
?>