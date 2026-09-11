<?php

// Start session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Project URL
define("BASE_URL", "/library_management");


// --------------------------------------------------
// Check if user is logged in
// --------------------------------------------------

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {

        header("Location: " . BASE_URL . "/login.php");

        exit();

    }
}


// --------------------------------------------------
// Check if logged-in user is Admin
// --------------------------------------------------

function requireAdmin()
{
    requireLogin();

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
    ) {

        header("Location: " . BASE_URL . "/login.php");

        exit();

    }
}


// --------------------------------------------------
// Check if logged-in user is Normal User
// --------------------------------------------------

function requireUser()
{
    requireLogin();

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'user'
    ) {

        header("Location: " . BASE_URL . "/login.php");

        exit();

    }
}

?>