<?php

// Debug: Check session before clearing
// echo "Before clearing session: ";
// print_r($_SESSION);

// Clear session
$_SESSION = [];
session_destroy();

// // Debug: Check cookies before clearing
// echo "Before clearing cookies: ";
// print_r($_COOKIE);

// Clear cookies
$cookies = ['login', 'userid', 'username', 'usertype', 'userfullname', 'userimage'];
foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/'); // Clear cookie
    }
}

// Debug: Check session and cookies after clearing
// echo "After clearing session and cookies: ";
// var_dump($_SESSION);
// print_r($_COOKIE);
// var_dump($_COOKIE);

// Redirect to login page
header('Location: ?page=login');
exit();
