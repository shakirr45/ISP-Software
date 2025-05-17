<?php
  include("dbconfig_robotisp.php");
//   $sql = "SELECT * FROM vw_agent ORDER BY ag_id DESC ";

    $sql = "SELECT 
    vw_agent.*, 
    tbl_zone.zone_name 
    FROM 
    vw_agent 
    INNER JOIN 
    tbl_zone 
    ON 
    vw_agent.zone = tbl_zone.zone_id";
    
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