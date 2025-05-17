<?php
if(isset($_POST['sms_custom'])){
    $sms = $obj->getSingleData("sms",['where'=>['status','=',$_POST['status']]]);
    if($sms){
        $obj->updateData('sms', [
            // 'smshead' =>$_POST['smshead'],
            'smsbody' =>$_POST['smsbody'],
        ],['status' =>$_POST['status']]);
    }else{
        $obj->insertData("sms", [
            // 'smshead' =>$_POST['smshead'],
            'smsbody' =>$_POST['smsbody'],
            'status' =>$_POST['status'],
        ]);
    }
    echo '<script>window.location="?page='.$_POST['sms_custom'].'"; </script>';
}
$obj->notificationShow();


$getCustomSMS =$obj->getSingleData('sms',['where' =>['status', '=', '1']]);
?>