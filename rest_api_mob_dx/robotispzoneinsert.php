<?php
    include("dbconfig_robotisp.php");
    
    if($_SERVER['REQUEST_METHOD']=='POST'){

        $zone_name = $_POST['zone_name'];
        $created_by = $_POST['created_by'];
        
        $created_by_int = (int)$created_by;
        
        $sql = "INSERT INTO tbl_zone (zone_name, created_by) VALUES ('$zone_name', $created_by_int)";
     
        if(mysqli_query($con,$sql)){
            echo "Zone Added";
        }else{
            echo "Database Connection Error !";
        }
        
    
	}
?>