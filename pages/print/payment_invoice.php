<?php
$token = isset($_GET['token'])? $_GET['token'] :NULL;
header("location: ../pdf/receipt.php?token=".$token);
?>