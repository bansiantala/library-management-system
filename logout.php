<?php

session_start();

/*
|--------------------------------------------------------------------------
| Store current user role before destroying session
|--------------------------------------------------------------------------
*/

$role = $_SESSION['role'] ?? '';

/*
|--------------------------------------------------------------------------
| Remove all session variables
|--------------------------------------------------------------------------
*/

$_SESSION = array();

/*
|--------------------------------------------------------------------------
| Destroy session
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| Redirect based on role
|--------------------------------------------------------------------------
*/

if ($role === 'user') {

    // User logout → User Login
    header("Location: user_login.php");
    exit();

} elseif ($role === 'admin') {

    // Admin logout → Admin Login
    header("Location: login.php");
    exit();

} else {

    // Unknown/no role → Home
    header("Location: index.php");
    exit();

}

?>