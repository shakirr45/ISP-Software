<?php
  include("dbconfig_robotisp.php");
  $entry_by_temp = $_GET['entry_by'];
  //  $sql = "SELECT * FROM tbl_account WHERE entry_by = $entry_by_temp AND acc_type = 1 ORDER BY acc_id DESC ";
//   $sql_new = "SELECT *, tbl_account.acc_id as acc_id FROM tbl_account INNER JOIN tbl_accounts_head ON tbl_account.acc_head = tbl_accounts_head.acc_id WHERE tbl_account.entry_by = $entry_by_temp ORDER BY tbl_account.acc_id DESC";
    $sql_new = "SELECT 
                tbl_account.*, 
                tbl_account.acc_id AS acc_id, 
                acc_head.acc_name AS acc_head_name, 
                acc_sub_head.acc_name AS acc_sub_head_name
            FROM 
                tbl_account
            INNER JOIN 
                tbl_accounts_head AS acc_head 
                ON tbl_account.acc_head = acc_head.acc_id
            LEFT JOIN 
                tbl_accounts_head AS acc_sub_head 
                ON tbl_account.acc_sub_head = acc_sub_head.acc_id
            WHERE 
                tbl_account.entry_by = $entry_by_temp
            ORDER BY 
                tbl_account.acc_id DESC";
                
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