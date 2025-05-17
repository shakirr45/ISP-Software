<?php
  include("dbconfig_robotisp.php");

   $sql_new = "SELECT *FROM tbl_accounts_head WHERE level=2 ORDER BY acc_id DESC";
    $result = mysqli_query($con, $sql_new);
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
      $rows = array();
       while($r = mysqli_fetch_assoc($result)) {
          $rows[] = $r; // with result object
       }
      echo json_encode($rows);
    } else {
        echo '{"result": "No data found"}';
    }
  ?>
  