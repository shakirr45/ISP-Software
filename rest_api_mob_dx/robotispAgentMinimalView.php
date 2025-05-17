<?php
  include("dbconfig_robotisp.php");
  $query = "SELECT ag_id, ag_name FROM tbl_agent";
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

  ?>