<?php
class SMS {


    // One to Many
    function sendsms($api_key, $senderid, $number, $message) {

        $url = "https://bulksmsbd.net/api/smsapi";
        // $api_key = "";
        // $senderid = "";
        $data = [
        "api_key" => $api_key,
        "senderid" => $senderid,
        "number" => $number,
        "message" => $message
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
        //return mustbe sent is suseecss or false message
    }


    //send Bulk sms many to many
    function sms_send($api_key, $senderid,$smsArray) {

        $url = "https://bulksmsbd.net/api/smsapimany";
        // $api_key = "";
        // $senderid = "";
        $messages = json_encode($smsArray);
        $data = [
            "api_key" => $api_key,
            "senderid" => $senderid,
            "messages" => $messages
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $json_array=json_decode($response,true);
        $status=$json_array['response_code'];
        $error=$json_array['error_message'];
        echo $httpCode.' S  '.$status.' E'.$error;
        return $response;
    }
}
?>