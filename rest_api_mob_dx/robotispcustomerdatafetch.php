<?php
  include("dbconfig_robotisp.php");
   $sql = "SELECT * FROM tbl_agent";
    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
      $rows = array();
       while($r = mysqli_fetch_assoc($result)) {
          $rows[] = $r; // with result object
        //  $rows[] = $r; // only array
    
       }
      echo json_encode($rows);
    } else {
        echo '{"result": "No data found"}';
    }
  ?>