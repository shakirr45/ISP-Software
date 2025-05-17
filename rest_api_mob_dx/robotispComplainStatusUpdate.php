<?php
include("dbconfig_robotisp.php");
    if($_SERVER['REQUEST_METHOD']=='POST'){
      
        //Post value
        $id = $_POST['id'];
        $status = $_POST['status'];

        $sql_update = "UPDATE tbl_complains SET 
        status = $status
        WHERE id = $id";

        if(mysqli_query($con,$sql_update)){
           echo "1";
        }else{
           echo "0";
        }
    }
?>