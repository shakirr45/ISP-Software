<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;



$checkIp = $_GET['ip'];
$findData = $obj->Total_Count('tbl_agent', "`ip` = '$checkIp'");
if ($findData == 0) {
    echo 1;
    exit;
} else {
    // echo '<p class="bg-danger pt-1"><span  class="glyphicon glyphicon-remove text-primary"></span> Sorry this Client Id is already exists. </p>';
    echo 0;
    exit;
}
