<?php
    include("dbconfig_robotisp.php");

    include '../model/oop.php';
    include '../model/Bill.php';
    $obj = new Controller();
    $bill = new Bill();

    if($_SERVER['REQUEST_METHOD']=='POST'){

        //CUS Counter
        $last_id = mysqli_insert_id($con);

        // $concat = "CUS000".$last_id;
        
		$ag_name = $_POST['ag_name'];
        $ag_mobile_no = $_POST['ag_mobile_no'];
        $ag_email = $_POST['ag_email'];
        $blood_group = $_POST['blood_group'];
        
        $national_id = $_POST['national_id'];
        $occupation = $_POST['occupation'];
        $ag_office_address = $_POST['ag_office_address'];
        $zone = $_POST['zone'];
        $connection_date = $_POST['connection_date'];
        
        $mb = $_POST['mb'];
        $ip = $_POST['ip'];
        $taka = $_POST['taka'];
        
        //Customer Status
        $ag_status = $_POST['ag_status'];
        $status_int = (int)$ag_status;
        
        //Entry By
        $entry_by = $_POST['entry_by'];
        $update_by = $_POST['update_by'];
        // $entry_by_int = (int)$entry_by;
        $entry_date = $_POST['entry_date'];

        //New Fields
        $mac_address = $_POST["mac_address"];
        $regular_mobile = $_POST["regular_mobile"];
        $bill_date = $_POST["bill_date"];
        $billing_person_id = $_POST["billing_person_id"];
       
        // $zone = $_POST['zone'];
        // $last_id = mysqli_insert_id($con);

        //SMS Switch ;)
        $sms_switch = $_POST['sms_switch'];
        
        //Due Entry
        $connection_charge_due = $_POST['connection_charge_due'];
        $running_month_due = $_POST['running_month_due'];
		
		//eee
		$sql_id_selector = "SELECT ag_id FROM tbl_agent ORDER BY ag_id ASC";
		$id_selector_exec = mysqli_query($con,$sql_id_selector);
		
		 while($row = mysqli_fetch_array($id_selector_exec)){
		     $ag_id = $row['ag_id'];
		 }
		 
		//For Accounts
		$acc_amount_run = $_POST['acc_amount_run'];
		$acc_amount_charge = $_POST['acc_amount_charge'];
		
		 
		$id_increment = $ag_id+1;
		$CUS_GEN = "CUS00".$id_increment;
	
        $sql = "INSERT INTO 
        tbl_agent (cus_id, ag_name, ag_mobile_no, 
        ag_email, national_id,ag_office_address, zone, 
        connection_date, mb, 
        ip, ag_status,
        entry_by,entry_date,
        update_by,taka,
        connect_charge,
        mac_address,regular_mobile,
        bill_date,billing_person_id)
         VALUES 
         ('$CUS_GEN','$ag_name',
         '$ag_mobile_no',
         '$ag_email','$national_id',
         '$ag_office_address','$zone',
         '$connection_date',
         '$mb', '$ip',
         $status_int, $entry_by,
         '$entry_date',$update_by,
         $taka,$acc_amount_charge,
         '$mac_address','$regular_mobile',
         $bill_date, $billing_person_id)";
        
        if(mysqli_query($con,$sql)){

            if($sms_switch === "1"){
                $postUrl = "http://api.bulksms.icombd.com/api/v3/sendsms/xml";
                $smsbody = "Welcome to ".$obj->getSettingValue('sms', 'sender'). ". Your UI:$ip, Package:$mb,  Bill Amt:$taka Tk,  Running Amt:$acc_amount_run Tk, C.charge:$acc_amount_charge Tk.  Support-".$obj->getSettingValue('sms', 'support_num').".Thank you.";
                $xmlString =
                    "<SMS>
                    <authentification>
                        <username>" . $obj->getSettingValue('sms', 'user') . "</username>
                        <password>" . $obj->getSettingValue('sms', 'pass') . "</password>
                    </authentification>
                    <message>
                        <sender>".$obj->getSettingValue('sms', 'sender')."</sender>
                        <text>$smsbody</text>
                    </message>
                    <recipients>
                        <gsm>88.$ag_mobile_no</gsm>
                    </recipients>
                </SMS>";
    
                $fields = "XML=" . urlencode($xmlString);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $postUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                $response = curl_exec($ch);
                curl_close($ch);

                echo "1";

            }else if($sms_switch === "0"){
                 echo "1";
            }
            
            // echo "1";
            
        }else{
            echo "Something went Wrong, Please fill up required fields :(";
        }
        
        //For last inserted ID 
      	$sql_id_acc = "SELECT *FROM tbl_agent ORDER BY ag_id ASC";
		$id_acc_exec = mysqli_query($con,$sql_id_acc);
		
		 while($row = mysqli_fetch_array($id_acc_exec)){
		     $ag_id = $row['ag_id'];
		     $ag_name = $row['ag_name'];
		     $entry_by = $row['entry_by'];
		     $entry_date = $row['entry_date'];
		 }
		 
        $id_tbl_acc = $ag_id;
        $acc_name_temp = $ag_name;
        $name_tbl_acc = $ag_name;
        $entry_by_temp = $entry_by;
        $entry_date_temp = $entry_date;
        
        $acc_status_run = "Opening and Running Month amount for connection of";
        $acc_status_charge = "Connection Charge of";
         
        if(!empty($acc_amount_run) && empty($acc_amount_charge)){
            
            $sql_acc_run_1 = "INSERT INTO tbl_account (acc_amount,cus_id,acc_description,agent_id,acc_type,entry_by,entry_date) 
            VALUES ('$acc_amount_run',$id_tbl_acc,'$acc_status_run $acc_name_temp',$id_tbl_acc,5,$entry_by,'$entry_date_temp')";
            
            mysqli_query($con,$sql_acc_run_1);
            
        }else
        if(!empty($acc_amount_charge) && empty($acc_amount_run)){
            
            $sql_acc_charge = "INSERT INTO tbl_account (acc_amount,cus_id,acc_description,agent_id,acc_type,entry_by,entry_date) 
            VALUES ('$acc_amount_charge',$id_tbl_acc,'$acc_status_charge $acc_name_temp',$id_tbl_acc,4,$entry_by,'$entry_date_temp')";
            mysqli_query($con,$sql_acc_charge);
         
            
        }else
        if(!empty($acc_amount_run) && !empty($acc_amount_charge)){
               
            $sql_acc_run = "INSERT INTO tbl_account (acc_amount,cus_id,acc_description,agent_id,acc_type,entry_by,entry_date) 
            VALUES ('$acc_amount_run',$id_tbl_acc,'$acc_status_run $acc_name_temp',$id_tbl_acc,5,$entry_by,'$entry_date_temp')";
            mysqli_query($con,$sql_acc_run);
            
            $sql_acc_charge = "INSERT INTO tbl_account (acc_amount,cus_id,acc_description,agent_id,acc_type,entry_by,entry_date) 
            VALUES ('$acc_amount_charge',$id_tbl_acc,'$acc_status_charge $acc_name_temp',$id_tbl_acc,4,$entry_by,'$entry_date_temp')";
            mysqli_query($con,$sql_acc_charge);
            
        }else{
            // echo "Database Connection Error !";
        }
        
        $sql_due_intersect = "INSERT INTO tbl_due_opening_amount_and_con_charge (connection_charge_due, running_month_due, tbl_agent_id, entry_by) 
            VALUES ($connection_charge_due, $running_month_due, $id_tbl_acc, $entry_by_temp)";
            mysqli_query($con,$sql_due_intersect);
        
	}
?>