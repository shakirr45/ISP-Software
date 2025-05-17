<?php
  include("dbconfig_robotisp.php");
   $sql = "SELECT 
   vw_user_info.FullName, 
   tbl_agent.ag_id,
   tbl_agent.cus_id,
   tbl_agent.ip, 
   tbl_agent.ag_name,
   tbl_agent.ag_mobile_no,
   tbl_agent.ag_office_address,
   tbl_agent.mb,
   tbl_agent.taka,
   tbl_agent.connect_charge,
   tbl_agent.ag_email,
   tbl_agent.regular_mobile,
   tbl_agent.national_id,
   tbl_agent.mac_address,
   tbl_agent.ag_status,
   tbl_agent.connection_date,
   tbl_zone.zone_name,
   tbl_agent.cus_id
   FROM tbl_agent INNER JOIN vw_user_info ON tbl_agent.billing_person_id = vw_user_info.UserId INNER JOIN tbl_zone ON tbl_agent.zone = tbl_zone.zone_id ORDER BY tbl_agent.ag_id DESC";

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