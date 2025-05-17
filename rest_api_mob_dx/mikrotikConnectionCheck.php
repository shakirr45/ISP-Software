<?php
include("dbconfig_robotisp.php");
// Check if POST parameters are set
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mik_ip = $_POST['mik_ip'];
    // Prepare the SQL query to fetch the status
    $query = "SELECT * FROM mikrotik_user WHERE mik_ip = '$mik_ip' AND status=1 ";
    $result_all = mysqli_query($con, $query);

        if (mysqli_num_rows($result_all) > 0) {
        // output data of each row
        $rows = array();
        while($row = mysqli_fetch_assoc($result_all)) {
            $rows[] = $row;
        }
        echo json_encode($rows);
    }else{
        echo "0";
    }
        
}
?>