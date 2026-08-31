<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ==============================
   SECURITY HEADERS
================================ */

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");


/* ==============================
   REDIRECT LOGGED IN USER
================================ */

function redirectLoggedInUser()
{
    if (!isset($_SESSION["user_id"])) {
        return;
    }

    $role = $_SESSION["role"] ?? "";

    if ($role === "student") {

        header("Location: student/dashboard.php");
        exit;

    } elseif ($role === "recruiter") {

        header("Location: recruiter/dashboard.php");
        exit;

    } elseif ($role === "admin") {

        header("Location: admin/dashboard.php");
        exit;
    }
}


/* ==============================
   CSRF TOKEN
================================ */

function generateCSRFToken()
{
    if (empty($_SESSION["csrf_token"])) {

        $_SESSION["csrf_token"] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}


function verifyCSRFToken($token)
{
    if (
        empty($token) ||
        empty($_SESSION["csrf_token"])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}


/* ==============================
   LOGIN RATE LIMIT
================================ */

function checkLoginAttempts()
{
    if (!isset($_SESSION["login_attempts"])) {

        $_SESSION["login_attempts"] = 0;

        $_SESSION["login_attempt_time"] = time();
    }


    $maxAttempts = 5;

    $lockoutTime = 300;


    if (
        $_SESSION["login_attempts"] >= $maxAttempts
    ) {

        $timePassed =
            time() -
            $_SESSION["login_attempt_time"];


        if ($timePassed < $lockoutTime) {

            return false;

        } else {

            $_SESSION["login_attempts"] = 0;

            $_SESSION["login_attempt_time"] = time();
        }
    }

    return true;
}


function increaseLoginAttempts()
{
    if (!isset($_SESSION["login_attempts"])) {

        $_SESSION["login_attempts"] = 0;

        $_SESSION["login_attempt_time"] = time();
    }

    $_SESSION["login_attempts"]++;
}


function resetLoginAttempts()
{
    unset(
        $_SESSION["login_attempts"],
        $_SESSION["login_attempt_time"]
    );
}


/* ==============================
   PASSWORD VALIDATION
================================ */

function validatePassword($password)
{
    if (strlen($password) < 8) {
        return
            "Password must contain at least 8 characters.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return
            "Password must contain at least one uppercase letter.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        return
            "Password must contain at least one lowercase letter.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        return
            "Password must contain at least one number.";
    }

    return true;
}


/* ==============================
   FULL NAME VALIDATION
================================ */

function validateFullName($name)
{
    if (strlen($name) < 3) {
        return
            "Full name must contain at least 3 characters.";
    }

    if (strlen($name) > 100) {
        return
            "Full name is too long.";
    }

    if (!preg_match("/^[a-zA-Z\s.'-]+$/", $name)) {
        return
            "Full name contains invalid characters.";
    }

    return true;
}