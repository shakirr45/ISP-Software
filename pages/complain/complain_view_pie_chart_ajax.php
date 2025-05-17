<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$currentYear = date("Y");
$currentMonth = date("m");
$previousMonth = ($currentMonth == 1) ? 12 : $currentMonth - 1; // Handle year rollover
$previousMonthYear = ($currentMonth == 1) ? $currentYear - 1 : $currentYear; // Year for previous month

// Fetch data for the current month and previous month
$currentMonthCount = $obj->rawSql("SELECT COUNT(id) AS count FROM tbl_complains 
                                   WHERE YEAR(entry_date) = '$currentYear' AND MONTH(entry_date) = '$currentMonth'")[0]['count'];

$previousMonthCount = $obj->rawSql("SELECT COUNT(id) AS count FROM tbl_complains 
                                    WHERE YEAR(entry_date) = '$previousMonthYear' AND MONTH(entry_date) = '$previousMonth'")[0]['count'];

// Prepare the JSON response
echo json_encode([
    "currentMonthCount" => $currentMonthCount,
    "previousMonthCount" => $previousMonthCount,
    "currentMonth" => $currentMonth,
    "previousMonth" => $previousMonth,
]);
exit();
