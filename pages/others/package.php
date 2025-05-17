<?php
// insert 
if(isset($_POST['submit'])){
    // Update 
    if(isset($_POST['package_id']) && isset($_POST['package_name']) && isset($_POST['net_speed']) && isset($_POST['bill_amount']) && !empty($_POST['package_id'])){
        $fromData=['package_name'=>$_POST['package_name'],'net_speed'=>$_POST['net_speed'],'bill_amount'=>$_POST['bill_amount']];
        if( isset($_POST['mikrotikuserid'])){
            $fromData['mikrotik_id']=$_POST['mikrotikuserid'];
        }
        $expackage =$obj->getSingleData('tbl_package',['where' => ['package_id', '=', $_POST['package_id']]]);
        $obj->updateData('tbl_package', $fromData,['package_id'=>$_POST['package_id']]);
        $obj->updateData('tbl_agent', ['mb'=>$_POST['net_speed']],['mb'=>$expackage['net_speed']]);

        $obj->notificationStore(" Package Update Successfull",'success');
        echo ' <script>window.location="?page=package_view"; </script>';exit;
    }
    // insert
    elseif(isset($_POST['package_name']) && isset($_POST['net_speed']) && isset($_POST['bill_amount'])){
        $fromData=['package_name'=>$_POST['package_name'],'net_speed'=>$_POST['net_speed'],'bill_amount'=>$_POST['bill_amount']];
        if( isset($_POST['mikrotikuserid'])){
            $fromData['mikrotik_id']=$_POST['mikrotikuserid'];
        } 
        $obj->insertData('tbl_package', $fromData);
        $obj->notificationStore("New Package Add Successfull",'success');
        echo ' <script>window.location="?page=package_view"; </script>';exit;
    }
    else{
        $obj->notificationStore("please Provide valid data", 'warning');
        echo ' <script>window.location="?page=package_view"; </script>';exit;
    }
}
$obj->notificationShow();
?>