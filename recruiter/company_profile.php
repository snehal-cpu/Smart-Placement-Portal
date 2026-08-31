<?php

require_once "../includes/auth.php";
requireRole("recruiter");

require_once "../config/database.php";


/* =========================================
   GET RECRUITER / COMPANY INFORMATION
========================================= */

$user_id = $_SESSION["user_id"];


$stmt = mysqli_prepare(
    $conn,
    "SELECT
        c.id AS company_id,
        c.company_name,
        c.industry,
        c.website,
        c.location,
        u.id AS user_id,
        u.full_name,
        u.email
     FROM companies c
     INNER JOIN users u
        ON c.user_id = u.id
     WHERE c.user_id = ?
     LIMIT 1"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$company = mysqli_fetch_assoc($result);


/* =========================================
   SAFETY CHECK
========================================= */

if (!$company) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}
/* =========================================
   COMPANY LOGO UPLOAD
========================================= */

$company_logo = $company["company_logo"] ?? "";


/* Check if a new logo was uploaded */

if (
    isset($_FILES["company_logo"]) &&
    $_FILES["company_logo"]["error"] === UPLOAD_ERR_OK
) {

    $allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/jpg",
        "image/webp"
    ];


    $fileType =
        mime_content_type(
            $_FILES["company_logo"]["tmp_name"]
        );


    if (
        !in_array(
            $fileType,
            $allowedTypes
        )
    ) {

        $message =
            "Please upload a valid image file (JPG, PNG or WEBP).";

        $messageType =
            "error";

    } else {


        $uploadDirectory =
            "../uploads/company_logos/";


        /* Create folder if it does not exist */

        if (
            !is_dir(
                $uploadDirectory
            )
        ) {

            mkdir(
                $uploadDirectory,
                0777,
                true
            );

        }


        /* Generate unique file name */

        $fileExtension =
            strtolower(
                pathinfo(
                    $_FILES["company_logo"]["name"],
                    PATHINFO_EXTENSION
                )
            );


        $newFileName =
            "company_" .
            $company["company_id"] .
            "_" .
            time() .
            "." .
            $fileExtension;


        $uploadPath =
            $uploadDirectory .
            $newFileName;


        if (
            move_uploaded_file(
                $_FILES["company_logo"]["tmp_name"],
                $uploadPath
            )
        ) {


            /* Delete old logo */

            if (
                !empty($company_logo) &&
                file_exists(
                    "../" .
                    $company_logo
                )
            ) {

                unlink(
                    "../" .
                    $company_logo
                );

            }


            /* Save relative path */

            $company_logo =
                "uploads/company_logos/" .
                $newFileName;


        } else {

            $message =
                "Failed to upload company logo.";

            $messageType =
                "error";

        }

    }

}


/* =========================================
   VARIABLES
========================================= */

$message = "";
$messageType = "";


/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $full_name = trim(
        $_POST["full_name"] ?? ""
    );


    $email = trim(
        $_POST["email"] ?? ""
    );


    $company_name = trim(
        $_POST["company_name"] ?? ""
    );


    $industry = trim(
        $_POST["industry"] ?? ""
    );


    $website = trim(
        $_POST["website"] ?? ""
    );


    $location = trim(
        $_POST["location"] ?? ""
    );


    /* =========================================
       VALIDATION
    ========================================= */

    if (
        empty($full_name) ||
        empty($email) ||
        empty($company_name)
    ) {

        $message =
            "Please fill in all required fields.";

        $messageType =
            "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message =
            "Please enter a valid email address.";

        $messageType =
            "error";

    } else {


        /* =========================================
           UPDATE USERS TABLE
        ========================================= */

        $userStmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET
                full_name = ?,
                email = ?
             WHERE id = ?"
        );


        mysqli_stmt_bind_param(
            $userStmt,
            "ssi",
            $full_name,
            $email,
            $user_id
        );


        $userUpdated =
            mysqli_stmt_execute(
                $userStmt
            );


        /* =========================================
           UPDATE COMPANIES TABLE
        ========================================= */

        $companyStmt = mysqli_prepare(
            $conn,
            "UPDATE companies
             SET
                company_name = ?,
                industry = ?,
                website = ?,
                location = ?
             WHERE user_id = ?"
        );


        mysqli_stmt_bind_param(
            $companyStmt,
            "ssssi",
            $company_name,
            $industry,
            $website,
            $location,
            $user_id
        );


        $companyUpdated =
            mysqli_stmt_execute(
                $companyStmt
            );


        if (
            $userUpdated &&
            $companyUpdated
        ) {


            $message =
                "Company profile updated successfully!";

            $messageType =
                "success";


            /* =========================================
               REFRESH DATA
            ========================================= */

            $stmt = mysqli_prepare(
                $conn,
                "SELECT
                    c.id AS company_id,
                    c.company_name,
                    c.industry,
                    c.website,
                    c.location,
                    u.id AS user_id,
                    u.full_name,
                    u.email
                 FROM companies c
                 INNER JOIN users u
                    ON c.user_id = u.id
                 WHERE c.user_id = ?
                 LIMIT 1"
            );


            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $user_id
            );


            mysqli_stmt_execute($stmt);


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $company =
                mysqli_fetch_assoc(
                    $result
                );


        } else {


            $message =
                "Something went wrong while updating your profile.";

            $messageType =
                "error";

        }

    }

}


/* =========================================
   USER INFORMATION
========================================= */

$recruiterName =
    htmlspecialchars(
        $company["full_name"]
    );


$firstName =
    explode(
        " ",
        trim(
            $company["full_name"]
        )
    )[0];


$firstName =
    htmlspecialchars(
        $firstName
    );


$initial =
    strtoupper(
        substr(
            trim(
                $company["full_name"]
            ),
            0,
            1
        )
    );


$companyName =
    htmlspecialchars(
        $company["company_name"]
    );


/* =========================================
   TIME BASED GREETING
========================================= */

$hour =
    (int)date("H");


if ($hour < 12) {

    $greeting =
        "Good morning";

} elseif ($hour < 17) {

    $greeting =
        "Good afternoon";

} else {

    $greeting =
        "Good evening";

}

?>


<!DOCTYPE html>

<html lang="en">


<head>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    href="../assets/css/sidebar(r).css"
    rel="stylesheet"
>
<meta charset="UTF-8">


<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<title>
    Company Profile | Smart Placement Portal
</title>


<!-- =========================================
     GOOGLE FONTS
========================================= -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>


<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
    rel="stylesheet"
>


<!-- =========================================
     FONT AWESOME
========================================= -->

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>


<style>


/* =========================================
   ROOT
========================================= */

:root {

    --bg-dark:
        #030712;

    --bg-dark-secondary:
        #07111f;

    --bg-dark-light:
        #0b1020;

    --primary:
        #5b7cff;

    --primary-light:
        #7c3aed;

    --cyan:
        #22d3ee;

    --green:
        #22c55e;

    --red:
        #ef4444;

    --text:
        #f8fafc;

    --muted:
        #94a3b8;

    --muted-dark:
        #64748b;

    --border:
        rgba(255,255,255,.09);

    --card:
        rgba(255,255,255,.045);

    --card-hover:
        rgba(255,255,255,.07);

}


/* =========================================
   GLOBAL
========================================= */

* {

    margin:
        0;

    padding:
        0;

    box-sizing:
        border-box;

}


html {

    scroll-behavior:
        smooth;

}


body {

    min-height:
        100vh;

    font-family:
        "DM Sans",
        sans-serif;

    background:

        radial-gradient(
            circle at 15% 20%,
            rgba(91,124,255,.16),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 80%,
            rgba(124,58,237,.14),
            transparent 28%
        ),

        linear-gradient(
            135deg,
            var(--bg-dark),
            var(--bg-dark-secondary) 55%,
            var(--bg-dark-light)
        );

    color:
        var(--text);

    overflow-x:
        hidden;

}


/* =========================================
   GRID BACKGROUND
========================================= */

body::before {

    content:
        "";

    position:
        fixed;

    inset:
        0;

    pointer-events:
        none;

    background-image:

        linear-gradient(
            rgba(255,255,255,.025) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(255,255,255,.025) 1px,
            transparent 1px
        );

    background-size:
        55px 55px;

    z-index:
        -1;

}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {

    position:
        fixed;

    top:
        0;

    left:
        0;

    width:
        265px;

    height:
        100vh;

    padding:
        25px 16px;

    display:
        flex;

    flex-direction:
        column;

    background:
        rgba(3,7,18,.88);

    backdrop-filter:
        blur(24px);

    border-right:
        1px solid var(--border);

    z-index:
        1000;

}


/* =========================================
   SIDEBAR BRAND
========================================= */

.brand {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        8px 10px 34px;

    text-decoration:
        none;

    color:
        white;

}


.brand-icon {

    width:
        44px;

    height:
        44px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        14px;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 12px 30px
        rgba(91,124,255,.25);

}


.brand-text {

    display:
        flex;

    flex-direction:
        column;

}


.brand-text strong {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        15px;

    letter-spacing:
        -.3px;

    color:
        white;

}


.brand-text span {

    margin-top:
        2px;

    font-size:
        9px;

    color:
        var(--muted);

}


/* =========================================
   SIDEBAR NAVIGATION
========================================= */

.nav-section-title {

    color:
        var(--muted-dark);

    font-size:
        9px;

    letter-spacing:
        1.3px;

    text-transform:
        uppercase;

    font-weight:
        700;

    padding:
        0 12px;

    margin-bottom:
        10px;

}


.sidebar-nav {

    display:
        flex;

    flex-direction:
        column;

    gap:
        5px;

}


.nav-link {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    padding:
        13px 14px;

    border-radius:
        12px;

    color:
        var(--muted);

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        500;

    transition:
        .25s ease;

}


.nav-link i {

    width:
        19px;

    text-align:
        center;

    font-size:
        14px;

}


.nav-link:hover {

    color:
        white;

    background:
        rgba(255,255,255,.055);

    transform:
        translateX(3px);

}


.nav-link.active {

    color:
        white;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.90),
            rgba(124,58,237,.90)
        );

    box-shadow:

        0 10px 25px
        rgba(91,124,255,.16);

}


.sidebar-bottom {

    margin-top:
        auto;

}


.sidebar-divider {

    height:
        1px;

    background:
        var(--border);

    margin:
        15px 8px;

}


.logout-link:hover {

    color:
        #fca5a5;

    background:
        rgba(239,68,68,.08);

}
/* =========================================
   COMPANY LOGO UPLOAD
========================================= */

.logo-upload-area {

    width:
        100%;

    min-height:
        125px;

    display:
        flex;

    align-items:
        center;

    gap:
        22px;

    padding:
        20px;

    border:
        1px dashed
        rgba(91,124,255,.35);

    border-radius:
        15px;

    background:
        rgba(91,124,255,.035);

    transition:
        .25s ease;

}


.logo-upload-area:hover {

    border-color:
        rgba(124,58,237,.60);

    background:
        rgba(91,124,255,.055);

}


/* =========================================
   LOGO PREVIEW
========================================= */

.logo-preview {

    width:
        82px;

    height:
        82px;

    min-width:
        82px;

    border-radius:
        16px;

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);

}


.logo-preview img {

    width:
        100%;

    height:
        100%;

    display:
        block;

    object-fit:
        cover;

}


.logo-placeholder {

    width:
        100%;

    height:
        100%;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        28px;

    color:
        #8ab4ff;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.20),
            rgba(124,58,237,.20)
        );

}


/* =========================================
   UPLOAD CONTENT
========================================= */

.logo-upload-content {

    display:
        flex;

    flex-direction:
        column;

    align-items:
        flex-start;

    justify-content:
        center;

    gap:
        7px;

}


.logo-upload-content strong {

    color:
        #f8fafc;

    font-size:
        13px;

    font-weight:
        700;

}


.logo-upload-content span {

    color:
        var(--muted);

    font-size:
        10px;

}


/* =========================================
   UPLOAD BUTTON
========================================= */

.logo-upload-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    margin-top:
        5px;

    padding:
        10px 15px;

    border-radius:
        9px;

    cursor:
        pointer;

    color:
        white;

    font-family:
        "DM Sans",
        sans-serif;

    font-size:
        10px;

    font-weight:
        700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:
        0 8px 20px
        rgba(91,124,255,.18);

    transition:
        .25s ease;

}


.logo-upload-button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(91,124,255,.30);

}


/* =========================================
   COMPANY PROFILE LOGO
========================================= */

.company-avatar {

    overflow:
        hidden;

}


.company-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left:
        265px;

    min-height:
        100vh;

}


/* =========================================
   TOPBAR
========================================= */

.topbar {

    min-height:
        82px;

    padding:
        18px 38px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    border-bottom:
        1px solid var(--border);

    background:
        rgba(3,7,18,.30);

    backdrop-filter:
        blur(18px);

}


.mobile-menu {

    display:
        none;

    width:
        42px;

    height:
        42px;

    border:
        none;

    border-radius:
        12px;

    background:
        rgba(255,255,255,.06);

    color:
        white;

    cursor:
        pointer;

}


.welcome {

    flex:
        1;

}


.welcome small {

    display:
        block;

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        5px;

}


.welcome h2 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        22px;

    font-weight:
        700;

    letter-spacing:
        -.5px;

}


.welcome h2 span {

    background:

        linear-gradient(
            90deg,
            #60a5fa,
            #a78bfa,
            #22d3ee
        );

    -webkit-background-clip:
        text;

    -webkit-text-fill-color:
        transparent;

}


/* =========================================
   PROFILE
========================================= */

.top-profile {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.profile-text {

    text-align:
        right;

}


.profile-text strong {

    display:
        block;

    font-size:
        11px;

    font-weight:
        600;

}


.profile-text span {

    font-size:
        9px;

    color:
        var(--muted);

}


.avatar {

    width:
        43px;

    height:
        43px;

    border-radius:
        14px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        700;

    font-size:
        14px;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 10px 25px
        rgba(91,124,255,.25);

}
.company-avatar {

    overflow:
        hidden;

}


.company-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}

/* =========================================
   CONTENT
========================================= */

.content {

    max-width:
        1350px;

    margin:
        auto;

    padding:
        35px 38px 55px;

}


/* =========================================
   PAGE HERO
========================================= */

.profile-hero {

    position:
        relative;

    overflow:
        hidden;

    padding:
        32px 36px;

    margin-bottom:
        28px;

    border-radius:
        24px;

    border:
        1px solid rgba(255,255,255,.10);

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.20),
            rgba(124,58,237,.14),
            rgba(34,211,238,.05)
        );

    backdrop-filter:
        blur(20px);

}


.profile-hero::before {

    content:
        "";

    position:
        absolute;

    width:
        260px;

    height:
        260px;

    right:
        -90px;

    top:
        -120px;

    border-radius:
        50%;

    background:
        rgba(96,165,250,.16);

    filter:
        blur(45px);

}


.profile-hero-content {

    position:
        relative;

    z-index:
        2;

}


.profile-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    padding:
        7px 12px;

    border-radius:
        30px;

    margin-bottom:
        15px;

    border:
        1px solid rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

    color:
        #cbd5e1;

    font-size:
        10px;

}


.profile-badge i {

    color:
        #67e8f9;

}


.profile-hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        32px;

    letter-spacing:
        -.8px;

    margin-bottom:
        10px;

}


.profile-hero h1 span {

    background:

        linear-gradient(
            90deg,
            #60a5fa,
            #a78bfa,
            #22d3ee
        );

    -webkit-background-clip:
        text;

    -webkit-text-fill-color:
        transparent;

}


.profile-hero p {

    color:
        var(--muted);

    font-size:
        12px;

    line-height:
        1.8;

}


/* =========================================
   PROFILE LAYOUT
========================================= */

.profile-grid {

    display:
        grid;

    grid-template-columns:
        330px minmax(0,1fr);

    gap:
        24px;

    align-items:
        start;

}


/* =========================================
   GLASS PANEL
========================================= */

.glass-panel {

    border-radius:
        20px;

    border:
        1px solid var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

    overflow:
        hidden;

}


/* =========================================
   COMPANY SUMMARY
========================================= */

.company-card {

    padding:
        28px 24px;

    text-align:
        center;

}


.company-avatar {

    width:
        85px;

    height:
        85px;

    margin:
        0 auto 17px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        24px;

    font-size:
        29px;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 20px 45px
        rgba(91,124,255,.25);

}


.company-card h2 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        19px;

    margin-bottom:
        6px;

}


.company-card > p {

    color:
        var(--muted);

    font-size:
        11px;

}


.company-info-list {

    margin-top:
        24px;

    text-align:
        left;

    border-top:
        1px solid rgba(255,255,255,.07);

}


.company-info-item {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        15px 0;

    border-bottom:
        1px solid rgba(255,255,255,.07);

}


.company-info-icon {

    width:
        36px;

    height:
        36px;

    min-width:
        36px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        11px;

    background:
        rgba(91,124,255,.12);

    color:
        #8ab4ff;

    font-size:
        12px;

}


.company-info-text {

    min-width:
        0;

}


.company-info-text span {

    display:
        block;

    color:
        var(--muted-dark);

    font-size:
        8px;

    margin-bottom:
        3px;

}


.company-info-text strong {

    display:
        block;

    color:
        #dbeafe;

    font-size:
        10px;

    font-weight:
        600;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


/* =========================================
   FORM
========================================= */

.form-panel-header {

    padding:
        24px 28px;

    border-bottom:
        1px solid var(--border);

}


.form-panel-header h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        18px;

    margin-bottom:
        5px;

}


.form-panel-header p {

    color:
        var(--muted);

    font-size:
        10px;

}


.profile-form {

    padding:
        28px;

}


.form-section {

    margin-bottom:
        30px;

}


.form-section:last-child {

    margin-bottom:
        0;

}


.form-section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    margin-bottom:
        18px;

}


.form-section-title i {

    width:
        30px;

    height:
        30px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        9px;

    background:
        rgba(91,124,255,.12);

    color:
        #8ab4ff;

    font-size:
        11px;

}


.form-section-title h4 {

    font-size:
        12px;

    font-weight:
        700;

}


.form-grid {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        18px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

}


.form-group.full-width {

    grid-column:
        1 / -1;

}


.form-group label {

    margin-bottom:
        8px;

    color:
        #cbd5e1;

    font-size:
        10px;

    font-weight:
        600;

}


.required {

    color:
        #f87171;

}


.input-wrapper {

    position:
        relative;

}


.input-wrapper i {

    position:
        absolute;

    left:
        13px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--muted-dark);

    font-size:
        11px;

    pointer-events:
        none;

}


.form-control {

    width:
        100%;

    height:
        45px;

    padding:
        0 14px 0 38px;

    border:
        1px solid rgba(255,255,255,.09);

    border-radius:
        11px;

    outline:
        none;

    color:
        #e2e8f0;

    font-family:
        "DM Sans",
        sans-serif;

    font-size:
        11px;

    background:
        rgba(255,255,255,.035);

    transition:
        .25s;

}


.form-control::placeholder {

    color:
        var(--muted-dark);

}


.form-control:focus {

    border-color:
        rgba(91,124,255,.75);

    background:
        rgba(255,255,255,.055);

    box-shadow:

        0 0 0 3px
        rgba(91,124,255,.10);

}


.form-actions {

    display:
        flex;

    justify-content:
        flex-end;

    gap:
        12px;

    padding-top:
        25px;

    margin-top:
        25px;

    border-top:
        1px solid rgba(255,255,255,.07);

}


.btn-primary {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    padding:
        11px 20px;

    border:
        none;

    border-radius:
        11px;

    cursor:
        pointer;

    color:
        white;

    font-family:
        "DM Sans",
        sans-serif;

    font-size:
        11px;

    font-weight:
        700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 12px 28px
        rgba(91,124,255,.22);

    transition:
        .25s;

}


.btn-primary:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 18px 35px
        rgba(91,124,255,.32);

}


.btn-secondary {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    padding:
        11px 18px;

    border:
        1px solid rgba(255,255,255,.10);

    border-radius:
        11px;

    color:
        #cbd5e1;

    text-decoration:
        none;

    font-size:
        11px;

    background:
        rgba(255,255,255,.04);

    transition:
        .25s;

}


.btn-secondary:hover {

    color:
        white;

    background:
        rgba(255,255,255,.08);

}


/* =========================================
   ALERT
========================================= */

.alert {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        13px 16px;

    margin-bottom:
        22px;

    border-radius:
        12px;

    font-size:
        11px;

}


.alert-success {

    color:
        #bbf7d0;

    border:
        1px solid rgba(34,197,94,.20);

    background:
        rgba(34,197,94,.08);

}


.alert-error {

    color:
        #fecaca;

    border:
        1px solid rgba(239,68,68,.20);

    background:
        rgba(239,68,68,.08);

}


/* =========================================
   SIDEBAR OVERLAY
========================================= */

.sidebar-overlay {

    display:
        none;

    position:
        fixed;

    inset:
        0;

    background:
        rgba(0,0,0,.65);

    backdrop-filter:
        blur(3px);

    z-index:
        999;

}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1050px) {

    .profile-grid {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 850px) {

    .sidebar {

        transform:
            translateX(-100%);

        transition:
            transform .3s ease;

    }


    .sidebar.show {

        transform:
            translateX(0);

    }


    .sidebar-overlay.show {

        display:
            block;

    }


    .main {

        margin-left:
            0;

    }


    .mobile-menu {

        display:
            flex;

        align-items:
            center;

        justify-content:
            center;

    }


    .topbar {

        padding:
            15px 20px;

        gap:
            15px;

    }

}


@media (max-width: 650px) {

    .content {

        padding:
            25px 15px 45px;

    }


    .topbar {

        padding:
            15px;

    }


    .welcome small {

        display:
            none;

    }


    .welcome h2 {

        font-size:
            17px;

    }


    .profile-text {

        display:
            none;

    }


    .profile-hero {

        padding:
            26px 22px;

    }


    .profile-hero h1 {

        font-size:
            26px;

    }


    .form-panel-header {

        padding:
            21px;

    }


    .profile-form {

        padding:
            21px;

    }


    .form-grid {

        grid-template-columns:
            1fr;

    }


    .form-group.full-width {

        grid-column:
            auto;

    }


    .form-actions {

        flex-direction:
            column-reverse;

    }


    .btn-primary,
    .btn-secondary {

        width:
            100%;

    }

}


</style>


</head>


<body>


<!-- =========================================
     RECRUITER SIDEBAR
========================================= -->

<?php require_once "../includes/recruiter_sidebar.php"; ?>


<!-- =========================================
     MAIN
========================================= -->

<main class="main">


<!-- =========================================
     TOPBAR
========================================= -->

<header class="topbar">


<button
    class="mobile-menu"
    id="mobileMenu"
    type="button"
    aria-label="Open menu"
>

<i class="fa-solid fa-bars"></i>

</button>


<div class="welcome">


<small>

<?php echo $greeting; ?>, Recruiter

</small>


<h2>

Company

<span>

Profile

</span>

</h2>


</div>


<div class="top-profile">


<div class="profile-text">


<strong>

<?php echo $recruiterName; ?>

</strong>


<span>

Recruiter Account

</span>


</div>


<div class="avatar">

<?php echo htmlspecialchars($initial); ?>

</div>


</div>


</header>



<!-- =========================================
     CONTENT
========================================= -->

<section class="content">


<!-- =========================================
     HERO
========================================= -->

<div class="profile-hero">


<div class="profile-hero-content">


<div class="profile-badge">

<i class="fa-solid fa-building"></i>

Recruitment Organization

</div>


<h1>

Manage your

<span>

company profile.

</span>

</h1>


<p>

Keep your company information updated so students and
candidates can learn more about your organization and
job opportunities.

</p>


</div>


</div>



<!-- =========================================
     ALERT MESSAGE
========================================= -->

<?php if (!empty($message)): ?>


<div
    class="alert
    <?php
    echo $messageType === "success"
        ? "alert-success"
        : "alert-error";
    ?>"
>


<i
    class="
    fa-solid
    <?php
    echo $messageType === "success"
        ? "fa-circle-check"
        : "fa-circle-exclamation";
    ?>"
></i>


<span>

<?php echo htmlspecialchars($message); ?>

</span>


</div>


<?php endif; ?>



<!-- =========================================
     PROFILE GRID
========================================= -->

<div class="profile-grid">


<!-- =========================================
     COMPANY SUMMARY
========================================= -->

<div class="glass-panel company-card">






<h2>

<?php echo $companyName; ?>

</h2>


<p>

Recruiter Organization

</p>


<div class="company-info-list">


<div class="company-info-item">


<div class="company-info-icon">

<i class="fa-solid fa-user"></i>

</div>


<div class="company-info-text">


<span>

Recruiter

</span>


<strong>

<?php echo $recruiterName; ?>

</strong>


</div>


</div>



<div class="company-info-item">


<div class="company-info-icon">

<i class="fa-solid fa-envelope"></i>

</div>


<div class="company-info-text">


<span>

Email

</span>


<strong>

<?php
echo htmlspecialchars(
    $company["email"]
);
?>

</strong>


</div>


</div>



<div class="company-info-item">


<div class="company-info-icon">

<i class="fa-solid fa-location-dot"></i>

</div>


<div class="company-info-text">


<span>

Location

</span>


<strong>

<?php
echo htmlspecialchars(
    $company["location"]
    ?: "Not added"
);
?>

</strong>


</div>


</div>



<div class="company-info-item">


<div class="company-info-icon">

<i class="fa-solid fa-briefcase"></i>

</div>


<div class="company-info-text">


<span>

Industry

</span>


<strong>

<?php
echo htmlspecialchars(
    $company["industry"]
    ?: "Not added"
);
?>

</strong>


</div>


</div>


</div>


</div>



<!-- =========================================
     EDIT PROFILE FORM
========================================= -->

<div class="glass-panel">


<div class="form-panel-header">


<h3>

Edit Company Information

</h3>


<p>

Update your recruiter and organization details.

</p>


</div>



<form 
    method="POST"
    enctype="multipart/form-data"
    class="profile-form"
>


<!-- =========================================
     PERSONAL INFORMATION
========================================= -->

<div class="form-section">


<div class="form-section-title">


<i class="fa-solid fa-user"></i>


<h4>

Recruiter Information

</h4>


</div>



<div class="form-grid">


<!-- FULL NAME -->

<div class="form-group">


<label>

Full Name

<span class="required">

*

</span>

</label>


<div class="input-wrapper">


<i class="fa-solid fa-user"></i>


<input
    type="text"
    name="full_name"
    class="form-control"
    value="<?php echo htmlspecialchars($company["full_name"]); ?>"
    required
>


</div>


</div>



<!-- EMAIL -->

<div class="form-group">


<label>

Email Address

<span class="required">

*

</span>

</label>


<div class="input-wrapper">


<i class="fa-solid fa-envelope"></i>


<input
    type="email"
    name="email"
    class="form-control"
    value="<?php echo htmlspecialchars($company["email"]); ?>"
    required
>


</div>


</div>


</div>


</div>



<!-- =========================================
     COMPANY INFORMATION
========================================= -->

<div class="form-section">


<div class="form-section-title">


<i class="fa-solid fa-building"></i>


<h4>

Company Information

</h4>


</div>



<div class="form-grid">
   
<!-- COMPANY LOGO -->

<div class="form-group full-width">

    <label>Company Logo</label>


    <div class="logo-upload-area">


        <!-- LOGO PREVIEW -->

        <div class="logo-preview">

            <?php if (!empty($company["company_logo"])): ?>

                <img
                    id="logoPreview"
                    src="../<?php echo htmlspecialchars($company["company_logo"]); ?>"
                    alt="Company Logo"
                >

                <div
                    id="logoPlaceholder"
                    class="logo-placeholder"
                    style="display:none;"
                >

                    <i class="fa-solid fa-building"></i>

                </div>

            <?php else: ?>

                <div
                    id="logoPlaceholder"
                    class="logo-placeholder"
                >

                    <i class="fa-solid fa-building"></i>

                </div>


                <img
                    id="logoPreview"
                    src=""
                    alt="Company Logo"
                    style="display:none;"
                >

            <?php endif; ?>

        </div>


        <!-- UPLOAD TEXT -->

        <div class="logo-upload-content">

            <strong>
                Upload Company Logo
            </strong>


            <span>
                PNG, JPG or WEBP • Recommended square image
            </span>


            <label
                for="company_logo"
                class="logo-upload-button"
            >

                <i class="fa-solid fa-cloud-arrow-up"></i>

                Choose Logo

            </label>


            <input
                type="file"
                id="company_logo"
                name="company_logo"
                accept=".jpg,.jpeg,.png,.webp"
                hidden
            >

        </div>


    </div>

</div>

<!-- COMPANY NAME -->

<div class="form-group">


<label>

Company Name

<span class="required">

*

</span>

</label>


<div class="input-wrapper">


<i class="fa-solid fa-building"></i>


<input
    type="text"
    name="company_name"
    class="form-control"
    value="<?php echo htmlspecialchars($company["company_name"]); ?>"
    required
>


</div>


</div>



<!-- INDUSTRY -->

<div class="form-group">


<label>

Industry

</label>


<div class="input-wrapper">


<i class="fa-solid fa-briefcase"></i>


<input
    type="text"
    name="industry"
    class="form-control"
    placeholder="Example: Information Technology"
    value="<?php echo htmlspecialchars($company["industry"] ?? ""); ?>"
>


</div>


</div>



<!-- LOCATION -->

<div class="form-group">


<label>

Company Location

</label>


<div class="input-wrapper">


<i class="fa-solid fa-location-dot"></i>


<input
    type="text"
    name="location"
    class="form-control"
    placeholder="Example: Pune, Maharashtra"
    value="<?php echo htmlspecialchars($company["location"] ?? ""); ?>"
>


</div>


</div>



<!-- WEBSITE -->

<div class="form-group">


<label>

Company Website

</label>


<div class="input-wrapper">


<i class="fa-solid fa-globe"></i>


<input
    type="text"
    name="website"
    class="form-control"
    placeholder="Example: www.company.com"
    value="<?php echo htmlspecialchars($company["website"] ?? ""); ?>"
>


</div>


</div>


</div>


</div>



<!-- =========================================
     FORM ACTIONS
========================================= -->

<div class="form-actions">


<a
    href="dashboard.php"
    class="btn-secondary"
>


<i class="fa-solid fa-arrow-left"></i>

Back to Dashboard


</a>



<button
    type="submit"
    class="btn-primary"
>


<i class="fa-solid fa-floppy-disk"></i>

Save Changes


</button>


</div>


</form>


</div>


</div>


</section>


</main>



<!-- =========================================
     MOBILE SIDEBAR SCRIPT
========================================= -->

<script>


const mobileMenu =
    document.getElementById(
        "mobileMenu"
    );


const sidebar =
    document.getElementById(
        "sidebar"
    );


const sidebarOverlay =
    document.getElementById(
        "sidebarOverlay"
    );


function openSidebar() {


    if (
        !sidebar ||
        !sidebarOverlay
    ) return;


    sidebar.classList.add(
        "show"
    );


    sidebarOverlay.classList.add(
        "show"
    );

}


function closeSidebar() {


    if (
        !sidebar ||
        !sidebarOverlay
    ) return;


    sidebar.classList.remove(
        "show"
    );


    sidebarOverlay.classList.remove(
        "show"
    );

}


if (mobileMenu) {


    mobileMenu.addEventListener(
        "click",
        function () {


            if (
                sidebar &&
                sidebar.classList.contains(
                    "show"
                )
            ) {


                closeSidebar();


            } else {


                openSidebar();


            }


        }
    );


}


if (sidebarOverlay) {


    sidebarOverlay.addEventListener(
        "click",
        closeSidebar
    );


}


</script>


</body>

</html>