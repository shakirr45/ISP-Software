<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$dateMonth = date("n");
$dateYear = date("Y");

if (isset($_GET['dateMonth']) && isset($_GET['dateYear'])) {
    $dateMonth = intval($_GET['dateMonth']);
    $dateYear = intval($_GET['dateYear']);
}

$total_bill_collection;
$total_connection_charge;
$total_others;

$result = $obj->rawSql("SELECT SUM(CASE WHEN acc_type = 3 THEN acc_amount ELSE 0 END) AS total_bill_collection,SUM(CASE WHEN acc_type = 1 THEN acc_amount ELSE 0 END) AS total_expense,SUM(CASE WHEN acc_type = 4 THEN acc_amount ELSE 0 END) AS total_connection_charge,SUM(CASE WHEN acc_type = 2 THEN acc_amount ELSE 0 END) AS total_others,SUM(CASE WHEN acc_type = 5 THEN acc_amount ELSE 0 END) AS total_opening_income FROM tbl_account WHERE YEAR(entry_date) = $dateYear AND MONTH(entry_date) = $dateMonth");



if (!empty($result)) {
    $data = $result[0]; // First (and only) row of results

    $total_bill_collection = $data['total_bill_collection'];
    $total_connection_charge = $data['total_connection_charge'];
    $total_others = $data['total_others'];
    $total_opening_income = $data['total_opening_income'];
    $total_expense = $data['total_expense'];
    $total_income = $total_opening_income + $total_bill_collection + $total_connection_charge + $total_others;
}

echo json_encode([
    "totalIncome" => $total_income,
    "totalExpense" => $total_expense,
]);
exit();
