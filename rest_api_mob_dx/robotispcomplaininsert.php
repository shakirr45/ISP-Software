<?php
    include("dbconfig_robotisp.php");
    
    if($_SERVER['REQUEST_METHOD']=='POST'){

		$details = $_POST['details'];
        $note = $_POST['note'];
        $customer_id = $_POST['customer_id'];
        $status = 1;
        $solve_by = $_POST['solve_by'];
        $entry_by_update_by = $_POST['entry_by'];
        $complain_date = $_POST['complain_date'];
        $entry_date = date("Y-m-d");
       
        $sql = "INSERT INTO tbl_complains (details, note, customer_id, status, solve_by, entry_by, update_by, complain_date, entry_date) 
        VALUES 
        ('$details','$note','$customer_id',$status,'$solve_by','$entry_by_update_by','$entry_by_update_by','$complain_date', '$entry_date')";
        
        if(mysqli_query($con,$sql)){
            echo "Your Complain is successfully posted !";
        }else{
            echo "Something Wrong, Please fill up required fields :(";
        }
    }
		
?>