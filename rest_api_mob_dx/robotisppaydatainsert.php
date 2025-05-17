<?php
    include("dbconfig_robotisp.php");

    //SMS Generation
    include '../model/oop.php';
    include '../model/Bill.php';
    $obj = new Controller();
    $bill = new Bill();

    if($_SERVER['REQUEST_METHOD']=='POST'){
      
        $cus_name = $_POST['cus_name'];
        $agent_id = $_POST['agent_id'];
        $acc_amount = $_POST['acc_amount'];
        $entry_by = $_POST['entry_by'];
        $entry_date = $_POST['entry_date'];
        $update_by = $_POST['update_by'];
        $ip_val = $_POST['ip_val'];
        $mobile_number = $_POST['mobile_number'];

        
        
        //Internal Generation
        $description_temp = date('M')." Months Bill collection of ". $cus_name ." IP: ".$ip_val;
        
        //   $description_temp = $cus_name;
        
        $acc_type = 3;
        
        $sql = "INSERT INTO tbl_account (agent_id, acc_amount, acc_description, acc_type, entry_by, entry_date, update_by) 
        VALUES 
        ($agent_id, $acc_amount,'$description_temp', $acc_type, $entry_by,'$entry_date',$update_by)";
        
        if(mysqli_query($con,$sql)){
            //SMS Generation
        //////////////////////////////////////////////////////////////////////////////
        $smsbody_test = "Dear Sir/Madam, Your Internet Bill-".$acc_amount." taka has been paid successfully. Thank you. ".$obj->getSettingValue('sms', 'company_name').".Support-".$obj->getSettingValue('sms', 'support_num');
        $mobilenum="88".$mobile_number;
            $obj->sendsms($smsbody_test, $mobilenum);
        
       
        ////////////////////////////////////////////////////////////////////////////////
            echo "1";
        }else{
            echo "Database Connection Error !";
        }
        
    }
?>