<?php
    include("dbconfig_robotisp.php");
        //SMS Generation
        include '../model/oop.php';
        include '../model/Bill.php';
        $obj = new Controller();
        $bill = new Bill();

    if($_SERVER['REQUEST_METHOD']=='POST'){
      
        //Current month and year 
        $month_year = date('M Y');

        $cus_name = $_POST['cus_name'];
        $agent_id = $_POST['agent_id'];
        $acc_amount = $_POST['acc_amount'];
        $entry_by = $_POST['entry_by'];
        $entry_date = $_POST['entry_date'];
        $update_by = $_POST['update_by'];
        $ip_val = $_POST['ip_val'];
        $acc_description = $_POST["acc_description"];

        //SMS Generation
        $mobile_number = $_POST['mobile_number'];
        // $date_stamp_solid_string_form = $_POST['date_string'];
        $month_string = $_POST['month_string'];
        //   $description_temp = $cus_name;

        $acc_type = 3;
        
        $sql = "INSERT INTO tbl_account (agent_id, acc_amount, acc_description, acc_type, entry_by, entry_date, update_by) 
        VALUES 
        ($agent_id, $acc_amount,'$acc_description', $acc_type, $entry_by,'$entry_date',$update_by)";
        
        if(mysqli_query($con,$sql)){

      
        if(empty($month_string)){
             //SMS Generation
        //////////////////////////////////////////////////////////////////////////////
        $smsbody_test = "Dear ".$ip_val.",Your Internet Bill of ".$month_year.", ".$acc_amount." taka has been paid successfully. Thank you. ".$obj->getSettingValue('sms', 'company_name').".Support-".$obj->getSettingValue('sms', 'support_num');
        $mobilenum="88".$mobile_number;
        $obj->sendsms($smsbody_test, $mobilenum);
        
        
        ////////////////////////////////////////////////////////////////////////////////
        }else {
              //SMS Generation
        //////////////////////////////////////////////////////////////////////////////
            $postUrl = "http://api.bulksms.icombd.com/api/v3/sendsms/xml";
            $smsbody_test = "Dear ".$ip_val.",Your Internet Bill of ".$month_string.", ".$acc_amount." taka has been paid successfully. Thank you. ".$obj->getSettingValue('sms', 'sender').".Support-".$obj->getSettingValue('sms', 'support_num');
            
            $mobilenum="88".$mobile_number;
            $obj->sendsms($smsbody_test, $mobilenum);
        }


            echo "1";

        }else{
            echo "Database Connection Error !";
        }
        
    }
?>