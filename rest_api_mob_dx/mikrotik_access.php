<?php
    include("dbconfig_robotisp.php");
    include '../model/oop.php';
    include '../model/Mikrotik.php';
    $obj = new Controller();
    if ($obj->tableExists('mikrotik_user')) {

    $mikrotikLoginData = $obj->details_by_cond('mikrotik_user', 'id = 1');
    $mikrotik = new Mikrotik($mikrotikLoginData['mik_ip'], $mikrotikLoginData['mik_username'], $mikrotikLoginData['mik_password']);

    if ($mikrotik->checkConnection()) {

        $mikrotikConnect = true;

    }
}
if (isset($mikrotik)) {
  $rows = array();
     foreach ($mikrotik->profileStatus() as $singlePackage) 
     {
        $rows[]=array("name" =>$singlePackage['name']);
     }
     echo json_encode($rows);
  }
  ?>