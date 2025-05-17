<?php
// insert 
if (isset($_POST['submit'])) {
    $usernameCount = $obj->details_by_cond("_createuser", "UserName='" . $_POST['username'] . "'");
    if ($usernameCount > 0) {
        // If username already exists, display an error message
        $obj->notificationStore("Username already exists, please choose another one.", 'danger');
        echo '<script>window.location="?page=user_create";</script>';
        exit;
    }

    if (!empty($_FILES["pimage"]["name"]) && isset($_FILES["pimage"]["name"])) {
        $target_file = "assets/images/" . basename($_FILES["pimage"]["name"]);
        if (move_uploaded_file($_FILES["pimage"]["tmp_name"], $target_file)) {
            $photopath = $target_file;
        }
    } else {
        $photopath = 'assets/images/default.jpg';
    }


    $form_user = array(
        'FullName' => $_POST['fullname'],
        'UserName' => $_POST['username'],
        'Password' => MD5($_POST['password']),
        'Email' => $_POST['email'],
        'MobileNo' => $_POST['phone'],
        'NationalId' => $_POST['nid'],
        'Address' => $_POST['address'],
        'PhotoPath' => $photopath,
        'Status' => $_POST['status'],
        'EntryBy' => $userId,
        'EntryDate' => date('Y-m-d'),
        'UpdateBy' => $userId
    );
    $lastId = $obj->insertData('_createuser', $form_user);

    $form_access = array(
        'UserId' => $lastId,
        'UserType' => $_POST['type'],
        'MenuPermission' => serialize(!empty($_POST['menu_permission']) ? $_POST['menu_permission'] : []),
        'WorkPermission' => serialize(!empty($_POST['workPermission']) ?  $_POST['workPermission']  : []),
        'EntryBy' => $userId,
        'EntryDate' => date('Y-m-d'),
        'UpdateBy' => $userId
    );
    $lastId = $obj->insertData('_useraccess', $form_access);

    $obj->notificationStore("New User Add Successfull", 'success');
    echo ' <script>window.location="?page=user_view"; </script>';
    exit;
}

// password Update 
if (isset($_POST['passwordupdate'])) {
    $passwordid = $_POST['passwordid'];
    $newPassword = $_POST['password'];
    $obj->updateData("_createuser", ['Password' => md5($newPassword)], ['UserId' => $passwordid]);
    $obj->notificationStore("Password Change  Successfull", 'success');
    echo ' <script>window.location="?page=user_view"; </script>';
    exit;
}

// update
if (isset($_POST['update'])) {
    $usernameCount = $obj->details_by_cond("_createuser", "UserName='" . $_POST['username'] . "' AND UserId!='" . $_POST['updateId'] . "'");
    if ($usernameCount > 0) {
        // If username already exists, display an error message
        $obj->notificationStore("Username already exists, please choose another one.", 'danger');
        echo '<script>window.location="?page=user_view";</script>';
        exit;
    }
    $form_updateuser = array(
        'FullName' => $_POST['fullname'],
        'UserName' => $_POST['username'],
        'Email' => $_POST['email'],
        'MobileNo' => $_POST['phone'],
        'NationalId' => $_POST['nid'],
        'Address' => $_POST['address'],
        'Status' => $_POST['status'],
        'EntryDate' => date('Y-m-d'),
        'UpdateBy' => $userId
    );

    if (!empty($_FILES["pimage"]["name"]) && isset($_FILES["pimage"]["name"])) {
        $target_file = "assets/images/" . basename($_FILES["pimage"]["name"]);
        if (move_uploaded_file($_FILES["pimage"]["tmp_name"], $target_file)) {
            $form_updateuser['PhotoPath'] = $target_file;
        }
    }


    $form_updateaccess = array(
        'UserType' => $_POST['type'],
        'MenuPermission' => serialize(!empty($_POST['menu_permission']) ? $_POST['menu_permission'] : []),
        'WorkPermission' => serialize(!empty($_POST['workPermission']) ?  $_POST['workPermission']  : []),
        'EntryDate' => date('Y-m-d'),
        'UpdateBy' => $userId
    );
    if (isset($_POST['updateId'])) {
        $obj->updateData('_createuser', $form_updateuser, ['UserId' => $_POST['updateId']]);
        $obj->updateData('_useraccess', $form_updateaccess, ['UserId' => $_POST['updateId']]);
        $obj->notificationStore("User Update Successfull", 'success');
    } else {
        $obj->notificationStore("User Update Failed", 'danger');
    }
    echo ' <script>window.location="?page=user_view"; </script>';
    exit;
}


$obj->notificationShow();
