<?php
// var_dump($obj->getAllData("tbl_agent", [['ag_id','>',0]]));
// var_dump($obj->getAllData("tbl_agent"));

// var_dump($obj->getSingleData("tbl_agent", [['ag_id','>',0]]));
// var_dump($obj->getSingleData("tbl_agent"));

// var_dump($obj->totalCount("tbl_agent", [['ag_id','>',0]]));
// // var_dump($obj->totalCount("tbl_agent"));

$messageNotice = "";
$date = date('Y-m-d');
$offdates = '2030-12-12';
$bnotice = $obj->getSingleData("tbl_notice", [['user_id', '=', 0]]);
$totacntuser = $obj->totalCount("tbl_agent");
$totalcountuser = ($totacntuser) ? $totacntuser : 0;

if ($bnotice) {
    if ($bnotice['trail_date'] >= $date) {
        $offdates = $bnotice['trail_date'];
        $trairemaining = floor((strtotime($bnotice['trail_date']) - time()) / 86400);
        if ($trairemaining < 7) {
            $trairemaining = $trairemaining + 1;
            $messageNotice = "Trail period will be end within $trairemaining days. Before Inactive contact with support " . $bnotice['disconnected_message'];
        }
    } elseif (($bnotice['next_disconnected_date'] >= $date) && ($bnotice['free_customer_limit'] <= $totalcountuser)) {
        $offdates = $bnotice['next_disconnected_date'];
        $disremaining = floor((strtotime($bnotice['next_disconnected_date']) - time()) / 86400);
        if ($disremaining < 7) {
            $disremaining = $disremaining + 1;
            $messageNotice = "Please Pay your software bill within $disremaining days. Before Inactive contact with support " . $bnotice['disconnected_message'];
        }
    } elseif (($bnotice['next_disconnected_date'] < $date) && ($bnotice['free_customer_limit'] <= $totalcountuser)) {
        $offdates = $bnotice['next_disconnected_date'];
        $messageNotice = "Please Pay your software bill to active your account. Contact with support " . $bnotice['disconnected_message'];
        session_destroy();
        // header("location: login.php");
    }
}


$date = strtotime("$offdates 4:00 PM"); //provide disconnect date    
$remaining = $date - time();
$days_remaining = floor($remaining / 86400);
$days_rem_pos = abs($days_remaining);
$hours_remaining = floor(($remaining % 86400) / 3600);
$hours_remaining_pos = abs($hours_remaining);
if ($days_remaining <= 0  && $hours_remaining == 0 || $hours_remaining < 0) {
    $amethod = "";
    $etype = "hidden";
    $ptype = "hidden";
    $rtype = "hidden";
    $battr = "hidden";
} else {
    $amethod = "POST";
    $etype = "text";
    $ptype = "password";
    $rtype = "checkbox";
    $battr = "";
}

if (isset($_POST['login'])) {

    if ((!isset($_POST['username']) || empty($_POST['username'])) || (!isset($_POST['password']) || empty($_POST['password']))) {
        $obj->notificationStore("User Name or Password Must Not be empty", 'danger');
        header('Location: ?page=login');
        exit();
    }

    $username = stripcslashes(trim($_POST['username']));
    $password = stripcslashes(trim($_POST['password']));
    $remember = (isset($_POST['remember'])) ? true : false;

    $users = $obj->getSingleData("vw_user_info", [['UserName', '=', "$username"], ['Password', '=', md5($password)], ['Status', '=', 1]]);
    if ($users) {
        $_SESSION["login"] = true;
        $_SESSION['userid'] = $users['UserId'];
        $_SESSION['username'] = $users['UserName'];
        $_SESSION['usertype'] = $users['UserType'];
        $_SESSION['userfullname'] = $users['FullName'];
        $_SESSION['userimage'] = $users['PhotoPath'];

        if ($remember) {
            setcookie("login", true, time() + (1 * 24 * 60 * 60)); // 1 days
            setcookie("userid", $users['UserId'], time() + (1 * 24 * 60 * 60));
            setcookie("username", $users['UserName'], time() + (1 * 24 * 60 * 60));
            setcookie("usertype", $users['UserType'], time() + (1 * 24 * 60 * 60));
            setcookie("userfullname", $users['FullName'], time() + (1 * 24 * 60 * 60));
            setcookie("userimage", $users['PhotoPath'], time() + (1 * 24 * 60 * 60));
        }
        $obj->notificationStore("Login Success", 'success');
        header('Location: ?page=dashboard');
    }
    $obj->notificationStore("Invalid User Name or Password", 'danger');
    header('Location: ?page=login');
    exit();
}
