<?php
  include("dbconfig_robotisp.php");
   $sql = "SELECT * , tbl_complains.id as complain_id 
   FROM 
   tbl_complains 
   INNER JOIN 
   tbl_agent ON 
   tbl_complains.customer_id = tbl_agent.ag_id 
   INNER JOIN 
   tbl_employee ON
   tbl_complains.solve_by = tbl_employee.id 
   INNER JOIN 
   tbl_complain_templates ON 
   tbl_complains.complain_type = tbl_complain_templates.id
   ORDER BY 
   tbl_complains.id DESC";
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