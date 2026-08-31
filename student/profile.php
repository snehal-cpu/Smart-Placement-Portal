<?php

require_once "../includes/auth.php";
requireRole("student");

require_once "../config/database.php";


/* =========================================
   STUDENT INFORMATION
========================================= */

$user_id = $_SESSION["user_id"];

$message = "";
$messageType = "";


/* =========================================
   GET STUDENT PROFILE
========================================= */

function getStudentProfile($conn, $user_id)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            s.id,
            s.enrollment_no,
            s.phone,
            s.department,
            s.course,
            s.year,
            s.cgpa,
            s.graduation_year,
            s.resume,
            u.full_name,
            u.email
         FROM students s
         INNER JOIN users u
            ON s.user_id = u.id
         WHERE s.user_id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $student = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $student;
}


$student = getStudentProfile(
    $conn,
    $user_id
);


/* =========================================
   SAFETY CHECK
========================================= */

if (!$student) {

    session_destroy();

    header(
        "Location: ../login.php"
    );

    exit;
}


$student_id = (int)$student["id"];


/* =========================================
   UPDATE PROFILE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================
       GET FORM DATA
    ===================================== */

    $enrollment_no =
        trim(
            $_POST["enrollment_no"] ?? ""
        );

    $phone =
        trim(
            $_POST["phone"] ?? ""
        );

    $department =
        trim(
            $_POST["department"] ?? ""
        );

    $course =
        trim(
            $_POST["course"] ?? ""
        );

    $year =
        isset($_POST["year"])
            ? (int)$_POST["year"]
            : 0;

    $cgpa =
        isset($_POST["cgpa"])
            ? (float)$_POST["cgpa"]
            : -1;

    $graduation_year =
        isset($_POST["graduation_year"])
            ? (int)$_POST["graduation_year"]
            : 0;


    /* =====================================
       VALIDATION
    ===================================== */

    if (
        !empty($phone)
        &&
        !preg_match(
            '/^[0-9]{10}$/',
            $phone
        )
    ) {

        $message =
            "Mobile number must contain exactly 10 digits.";

        $messageType =
            "danger";


    } elseif (
        $cgpa < 0
        ||
        $cgpa > 10
    ) {

        $message =
            "CGPA must be between 0.00 and 10.00.";

        $messageType =
            "danger";


    } elseif (
        $year < 1
        ||
        $year > 4
    ) {

        $message =
            "Please select a valid current year.";

        $messageType =
            "danger";


    } elseif (
        $graduation_year < 2024
        ||
        $graduation_year > 2035
    ) {

        $message =
            "Please enter a valid graduation year.";

        $messageType =
            "danger";


    } else {


        /* =====================================
           UPDATE PROFILE INFORMATION
        ===================================== */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE students
             SET
                enrollment_no = ?,
                phone = ?,
                department = ?,
                course = ?,
                year = ?,
                cgpa = ?,
                graduation_year = ?
             WHERE id = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ssssiddi",
            $enrollment_no,
            $phone,
            $department,
            $course,
            $year,
            $cgpa,
            $graduation_year,
            $student_id
        );


        if (
            mysqli_stmt_execute(
                $stmt
            )
        ) {

            $message =
                "Profile updated successfully!";

            $messageType =
                "success";

        } else {

            $message =
                "Unable to update profile.";

            $messageType =
                "danger";
        }


        mysqli_stmt_close(
            $stmt
        );


        /* =====================================
           RESUME UPLOAD
        ===================================== */

        if (
            isset($_FILES["resume"])
            &&
            $_FILES["resume"]["error"]
                !== UPLOAD_ERR_NO_FILE
        ) {


            $file =
                $_FILES["resume"];


            /* FILE UPLOAD ERROR */

            if (
                $file["error"]
                !== UPLOAD_ERR_OK
            ) {

                $message =
                    "There was an error uploading your resume.";

                $messageType =
                    "danger";


            } else {


                $allowed_extensions =
                    [
                        "pdf",
                        "doc",
                        "docx"
                    ];


                $extension =
                    strtolower(
                        pathinfo(
                            $file["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                /* CHECK FILE EXTENSION */

                if (
                    !in_array(
                        $extension,
                        $allowed_extensions,
                        true
                    )
                ) {

                    $message =
                        "Only PDF, DOC and DOCX files are allowed.";

                    $messageType =
                        "danger";


                /* CHECK FILE SIZE */

                } elseif (
                    $file["size"]
                    >
                    5 * 1024 * 1024
                ) {

                    $message =
                        "Resume must be less than 5 MB.";

                    $messageType =
                        "danger";


                } else {


                    $upload_dir =
                        "../uploads/resumes/";


                    /* CREATE FOLDER */

                    if (
                        !is_dir(
                            $upload_dir
                        )
                    ) {

                        mkdir(
                            $upload_dir,
                            0777,
                            true
                        );
                    }


                    /* UNIQUE FILENAME */

                    $new_filename =
                        "resume_"
                        .
                        $student_id
                        .
                        "_"
                        .
                        time()
                        .
                        "."
                        .
                        $extension;


                    $destination =
                        $upload_dir
                        .
                        $new_filename;


                    /* MOVE FILE */

                    if (
                        move_uploaded_file(
                            $file["tmp_name"],
                            $destination
                        )
                    ) {


                        /* DELETE OLD RESUME */

                        if (
                            !empty(
                                $student["resume"]
                            )
                        ) {

                            $old_file =
                                $upload_dir
                                .
                                $student["resume"];


                            if (
                                file_exists(
                                    $old_file
                                )
                            ) {

                                unlink(
                                    $old_file
                                );
                            }
                        }


                        /* SAVE NEW FILE */

                        $stmt =
                            mysqli_prepare(
                                $conn,
                                "UPDATE students
                                 SET resume = ?
                                 WHERE id = ?"
                            );


                        mysqli_stmt_bind_param(
                            $stmt,
                            "si",
                            $new_filename,
                            $student_id
                        );


                        mysqli_stmt_execute(
                            $stmt
                        );


                        mysqli_stmt_close(
                            $stmt
                        );


                        $message =
                            "Profile and resume updated successfully.";

                        $messageType =
                            "success";


                    } else {

                        $message =
                            "Failed to upload resume.";

                        $messageType =
                            "danger";
                    }
                }
            }
        }
    }


    /* =====================================
       REFRESH PROFILE DATA
    ===================================== */

    $student =
        getStudentProfile(
            $conn,
            $user_id
        );
}


/* =========================================
   STUDENT DETAILS
========================================= */

$studentName =
    htmlspecialchars(
        $student["full_name"]
    );


$firstName =
    explode(
        " ",
        trim(
            $student["full_name"]
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
                $student["full_name"]
            ),
            0,
            1
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Profile | Smart Placement Portal</title>


<!-- GOOGLE FONTS -->

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


<!-- FONT AWESOME -->

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>


<style>

/* =========================================
   ROOT
========================================= */

:root {

    --bg-dark: #030712;
    --bg-dark-secondary: #07111f;
    --bg-dark-light: #0b1020;

    --primary: #5b7cff;
    --primary-light: #7c3aed;

    --cyan: #22d3ee;

    --text: #f8fafc;
    --muted: #94a3b8;
    --muted-dark: #64748b;

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

    margin: 0;
    padding: 0;

    box-sizing: border-box;
}


body {

    min-height: 100vh;

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

    color: var(--text);

    overflow-x: hidden;
}


body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

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
        55px
        55px;

    z-index: -1;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: 265px;

    height: 100vh;

    padding:
        25px
        16px;

    display: flex;

    flex-direction: column;

    background:
        rgba(3,7,18,.78);

    backdrop-filter:
        blur(24px);

    border-right:
        1px solid
        var(--border);

    z-index: 1000;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        8px
        10px
        34px;

    text-decoration: none;

    color: white;
}


.brand-icon {

    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

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

    display: flex;

    flex-direction: column;
}


.brand-text strong {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 15px;
}


.brand-text span {

    margin-top: 2px;

    font-size: 9px;

    color: var(--muted);
}


/* =========================================
   NAVIGATION
========================================= */

.nav-section-title {

    color: var(--muted-dark);

    font-size: 9px;

    letter-spacing: 1.3px;

    text-transform: uppercase;

    font-weight: 700;

    padding:
        0
        12px;

    margin-bottom: 10px;
}


.sidebar-nav {

    display: flex;

    flex-direction: column;

    gap: 5px;
}


.nav-link {

    display: flex;

    align-items: center;

    gap: 13px;

    padding:
        13px
        14px;

    border-radius: 12px;

    color: var(--muted);

    text-decoration: none;

    font-size: 12px;

    font-weight: 500;

    transition:
        .25s
        ease;
}


.nav-link i {

    width: 19px;

    text-align: center;

    font-size: 14px;
}


.nav-link:hover {

    color: white;

    background:
        rgba(255,255,255,.055);

    transform:
        translateX(3px);
}


.nav-link.active {

    color: white;

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

    margin-top: auto;
}


.sidebar-divider {

    height: 1px;

    background:
        var(--border);

    margin:
        15px
        8px;
}


.logout-link:hover {

    color: #fca5a5;

    background:
        rgba(239,68,68,.08);
}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 265px;

    min-height: 100vh;
}


/* =========================================
   TOPBAR
========================================= */

.topbar {

    min-height: 82px;

    padding:
        18px
        38px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom:
        1px solid
        var(--border);

    background:
        rgba(3,7,18,.30);

    backdrop-filter:
        blur(18px);
}


.topbar-title small {

    display: block;

    color: var(--muted);

    font-size: 10px;

    margin-bottom: 5px;
}


.topbar-title h2 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 22px;

    font-weight: 700;
}


.top-profile {

    display: flex;

    align-items: center;

    gap: 12px;
}


.profile-text {

    text-align: right;
}


.profile-text strong {

    display: block;

    font-size: 11px;
}


.profile-text span {

    font-size: 9px;

    color: var(--muted);
}


.avatar {

    width: 43px;
    height: 43px;

    border-radius: 14px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );
}


/* =========================================
   CONTENT
========================================= */

.content {

    max-width: 1250px;

    margin: auto;

    padding:
        35px
        38px
        55px;
}


/* =========================================
   PAGE HERO
========================================= */

.page-hero {

    position: relative;

    overflow: hidden;

    padding:
        32px
        35px;

    margin-bottom: 25px;

    border-radius: 22px;

    border:
        1px solid
        rgba(255,255,255,.10);

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


.page-hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 28px;

    margin-bottom: 8px;
}


.page-hero p {

    color: var(--muted);

    font-size: 11px;
}


.hero-icon {

    position: absolute;

    right: 45px;

    bottom: -25px;

    font-size: 120px;

    opacity: .05;
}


/* =========================================
   PROFILE CARD
========================================= */

.glass-card {

    border:

        1px solid
        var(--border);

    border-radius: 22px;

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

    overflow: hidden;
}


.profile-header {

    display: flex;

    align-items: center;

    gap: 16px;

    padding:
        28px;

    border-bottom:
        1px solid
        var(--border);
}


.large-avatar {

    width: 65px;
    height: 65px;

    border-radius: 18px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 22px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 12px 30px
        rgba(91,124,255,.22);
}


.profile-header h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 19px;

    margin-bottom: 5px;
}


.profile-header p {

    color: var(--muted);

    font-size: 10px;
}


/* =========================================
   FORM
========================================= */

.form-container {

    padding:
        28px;
}


.section-heading {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 20px;

    font-family:
        "Outfit",
        sans-serif;

    font-size: 16px;

    font-weight: 700;
}


.section-heading i {

    color:
        #8ab4ff;
}


.form-label {

    color:
        #cbd5e1;

    font-size:
        11px;

    font-weight:
        600;

    margin-bottom:
        8px;
}


.form-control,
.form-select {

    min-height:
        46px;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(255,255,255,.10);

    color:
        white;

    border-radius:
        11px;

    font-size:
        11px;
}


.form-control::placeholder {

    color:
        var(--muted-dark);
}


.form-control:focus,
.form-select:focus {

    color:
        white;

    background:
        rgba(255,255,255,.06);

    border-color:
        rgba(91,124,255,.70);

    box-shadow:
        0 0 0 3px
        rgba(91,124,255,.10);
}


.form-select option {

    background:
        #0b1020;

    color:
        white;
}


.form-control[type="file"] {

    padding:
        12px;
}


.form-control[type="file"]::file-selector-button {

    margin-right:
        12px;

    padding:
        7px
        12px;

    border:
        none;

    border-radius:
        7px;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );
}


hr {

    border-color:
        var(--border);

    opacity:
        1;
}


/* =========================================
   RESUME CARD
========================================= */

.resume-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding:
        16px;

    margin-top:
        14px;

    border-radius:
        14px;

    border:
        1px solid
        rgba(255,255,255,.08);

    background:
        rgba(255,255,255,.035);
}


.resume-info {

    display: flex;

    align-items: center;

    gap: 12px;
}


.resume-icon {

    width: 43px;
    height: 43px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius:
        12px;

    background:
        rgba(239,68,68,.10);

    color:
        #fca5a5;
}


.resume-title {

    font-size:
        11px;

    font-weight:
        700;
}


.resume-name {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        9px;

    word-break:
        break-word;
}


.resume-actions {

    display: flex;

    gap: 8px;
}


.btn-small {

    padding:
        8px
        12px;

    border-radius:
        9px;

    text-decoration:
        none;

    font-size:
        10px;

    color:
        #cbd5e1;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);
}


.btn-small:hover {

    color:
        white;

    background:
        rgba(255,255,255,.08);
}


.btn-save {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    border:
        none;

    padding:
        12px
        22px;

    border-radius:
        11px;

    color:
        white;

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


.btn-save:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 18px 35px
        rgba(91,124,255,.30);
}


/* =========================================
   ALERT
========================================= */

.alert {

    border-radius:
        13px;

    font-size:
        11px;

    border:
        1px solid;
}


.alert-success {

    color:
        #bbf7d0;

    background:
        rgba(34,197,94,.10);

    border-color:
        rgba(34,197,94,.20);
}


.alert-danger {

    color:
        #fecaca;

    background:
        rgba(239,68,68,.10);

    border-color:
        rgba(239,68,68,.20);
}


.text-muted {

    color:
        var(--muted)
        !important;

    font-size:
        9px;
}


/* =========================================
   MOBILE
========================================= */

.mobile-menu {

    display:
        none;
}


.sidebar-overlay {

    display:
        none;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 850px) {

    .sidebar {

        transform:
            translateX(-100%);

        transition:
            .3s ease;
    }


    .sidebar.show {

        transform:
            translateX(0);
    }


    .sidebar-overlay.show {

        display:
            block;

        position:
            fixed;

        inset:
            0;

        background:
            rgba(0,0,0,.65);

        z-index:
            999;
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
    }


    .topbar {

        padding:
            15px
            20px;
    }
}


@media (max-width: 650px) {

    .content {

        padding:
            25px
            15px
            45px;
    }


    .profile-text {

        display:
            none;
    }


    .form-container {

        padding:
            20px;
    }


    .profile-header {

        padding:
            22px;
    }


    .resume-card {

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .resume-actions {

        width:
            100%;
    }


    .btn-small {

        flex:
            1;

        text-align:
            center;
    }
}
/* =========================================
   PROFILE FORM LAYOUT FIX
========================================= */

.profile-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
    width: 100%;
}

.form-group {
    min-width: 0;
    width: 100%;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    margin-bottom: 9px;

    color: #cbd5e1;

    font-size: 11px;
    font-weight: 600;
}

.form-control,
.form-select {
    display: block;

    width: 100% !important;

    height: 48px;

    padding: 0 14px;

    color: #e2e8f0;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius: 12px;

    outline: none;

    font-family:
        "DM Sans",
        sans-serif;

    font-size: 12px;

    transition:
        .25s ease;
}

.form-control::placeholder {
    color: #64748b;
}

.form-control:focus,
.form-select:focus {
    color: #f8fafc;

    background:
        rgba(255,255,255,.07);

    border-color:
        #5b7cff;

    box-shadow:
        0 0 0 4px
        rgba(91,124,255,.12);

    outline: none;
}


/* SELECT OPTIONS */

.form-select option {
    background: #111827;
    color: #ffffff;
}


/* =========================================
   SECTION DIVIDER
========================================= */

.profile-divider {
    border: none;

    border-top:
        1px solid
        rgba(255,255,255,.09);

    margin:
        30px
        0;
}


/* =========================================
   SECTION HEADING
========================================= */

.section-heading {
    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 24px;

    color: #f1f5f9;

    font-family:
        "Outfit",
        sans-serif;

    font-size: 18px;

    font-weight: 700;
}

.section-heading i {
    color: #8ab4ff;
}


/* =========================================
   RESUME UPLOAD
========================================= */

.resume-upload-wrapper {
    width: 100%;
}

.resume-file-input {
    width: 100%;

    padding: 7px;

    color: #94a3b8;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius: 14px;

    font-size: 11px;
}

.resume-file-input::file-selector-button {
    border: none;

    padding:
        10px
        16px;

    margin-right: 14px;

    border-radius: 9px;

    color: white;

    font-weight: 600;

    cursor: pointer;

    background:
        linear-gradient(
            135deg,
            #5b7cff,
            #7c3aed
        );
}

.resume-help-text {
    display: block;

    margin-top: 10px;

    color: #64748b;

    font-size: 10px;
}


/* =========================================
   CURRENT RESUME
========================================= */

.current-resume {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    width: 100%;

    margin-top: 20px;

    padding: 18px;

    border-radius: 16px;

    border:
        1px solid
        rgba(255,255,255,.09);

    background:
        rgba(255,255,255,.035);
}

.resume-info {
    display: flex;

    align-items: center;

    gap: 14px;
}

.resume-icon {
    width: 45px;
    height: 45px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 13px;

    color: #fca5a5;

    background:
        rgba(239,68,68,.10);
}

.resume-title {
    color: #e2e8f0;

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 4px;
}

.resume-name {
    max-width: 300px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;

    color: #94a3b8;

    font-size: 10px;
}

.resume-actions {
    display: flex;

    gap: 9px;
}


/* =========================================
   SAVE BUTTON
========================================= */

.profile-save-wrapper {
    display: flex;

    justify-content: flex-end;

    margin-top: 30px;
}

.btn-save {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    min-height: 48px;

    padding:
        0
        24px;

    border: none;

    border-radius: 12px;

    cursor: pointer;

    color: white;

    font-family:
        "DM Sans",
        sans-serif;

    font-size: 12px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #5b7cff,
            #7c3aed
        );

    box-shadow:
        0 12px 28px
        rgba(91,124,255,.22);

    transition:
        .25s ease;
}

.btn-save:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 18px 35px
        rgba(91,124,255,.32);
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 700px) {

    .profile-form-grid {
        grid-template-columns: 1fr;
    }

    .current-resume {
        align-items: flex-start;

        flex-direction: column;
    }

    .resume-actions {
        width: 100%;
    }

    .resume-actions a {
        flex: 1;

        text-align: center;
    }

    .profile-save-wrapper {
        justify-content: stretch;
    }

    .btn-save {
        width: 100%;
    }
}
</style>

</head>


<body>
<?php require_once "../includes/student_sidebar.php"; ?>

<!-- MOBILE OVERLAY -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>



<!-- =========================================
     MAIN
========================================= -->

<main class="main">


<!-- TOPBAR -->

<header class="topbar">


<button
    class="mobile-menu"
    id="mobileMenu"
>

<i class="fa-solid fa-bars"></i>

</button>


<div class="topbar-title">

<small>
Student Account
</small>

<h2>
My Profile
</h2>

</div>


<div class="top-profile">


<div class="profile-text">

<strong>

<?php echo $studentName; ?>

</strong>

<span>
Student Account
</span>

</div>


<div class="avatar">

<?php
echo htmlspecialchars(
    $initial
);
?>

</div>


</div>


</header>


<!-- CONTENT -->

<section class="content">


<!-- PAGE HERO -->

<div class="page-hero">

<h1>
Build a stronger
<span style="color:#a78bfa;">
professional profile.
</span>
</h1>


<p>
Keep your academic information and resume updated to improve your placement opportunities.
</p>


<i
    class="fa-solid fa-user-pen hero-icon"
></i>


</div>


<!-- ALERT -->

<?php if (!empty($message)): ?>

<div
    class="alert alert-<?php echo $messageType; ?>"
>

<?php
echo htmlspecialchars(
    $message
);
?>

</div>

<?php endif; ?>


<!-- PROFILE CARD -->

<div class="glass-card">


<!-- PROFILE HEADER -->

<div class="profile-header">


<div class="large-avatar">

<?php
echo htmlspecialchars(
    $initial
);
?>

</div>


<div>

<h3>

<?php
echo $studentName;
?>

</h3>


<p>

<?php
echo htmlspecialchars(
    $student["email"]
);
?>

</p>

</div>


</div>


<!-- FORM -->

<form
    method="POST"
    enctype="multipart/form-data"
>


<div class="form-container">


<!-- ACADEMIC INFORMATION -->

<div class="section-heading">

<i class="fa-solid fa-graduation-cap"></i>

Academic Information

</div>


<div class="profile-form-grid">


<!-- ENROLLMENT -->

<div class="form-group">

<label class="form-label">

Enrollment Number

</label>


<input
    type="text"
    name="enrollment_no"
    class="form-control"
    value="<?php echo htmlspecialchars($student["enrollment_no"] ?? ""); ?>"
    placeholder="Enter enrollment number"
>

</div>


<!-- PHONE -->

<div class="form-group">

<label class="form-label">

Phone Number

</label>


<input
    type="tel"
    name="phone"
    class="form-control"
    value="<?php echo htmlspecialchars($student["phone"] ?? ""); ?>"
    placeholder="Enter 10-digit mobile number"
    maxlength="10"
    pattern="[0-9]{10}"
    inputmode="numeric"
>

</div>


<!-- DEPARTMENT -->

<div class="form-group">

<label class="form-label">

Department

</label>


<select
    name="department"
    class="form-select"
>


<option value="">
Select Department
</option>


<option
    value="Computer Engineering"
    <?php if (($student["department"] ?? "") === "Computer Engineering") echo "selected"; ?>
>
Computer Engineering
</option>


<option
    value="Information Technology"
    <?php if (($student["department"] ?? "") === "Information Technology") echo "selected"; ?>
>
Information Technology
</option>


<option
    value="ENTC"
    <?php if (($student["department"] ?? "") === "ENTC") echo "selected"; ?>
>
Electronics & Telecommunication
</option>


<option
    value="Mechanical Engineering"
    <?php if (($student["department"] ?? "") === "Mechanical Engineering") echo "selected"; ?>
>
Mechanical Engineering
</option>


<option
    value="Civil Engineering"
    <?php if (($student["department"] ?? "") === "Civil Engineering") echo "selected"; ?>
>
Civil Engineering
</option>


</select>

</div>


<!-- COURSE -->

<div class="form-group">

<label class="form-label">

Course

</label>


<input
    type="text"
    name="course"
    class="form-control"
    value="<?php echo htmlspecialchars($student["course"] ?? ""); ?>"
    placeholder="e.g. B.E. Computer Engineering"
>

</div>


<!-- YEAR -->

<div class="form-group">

<label class="form-label">

Current Year

</label>


<select
    name="year"
    class="form-select"
>


<option value="">
Select Year
</option>


<option
    value="1"
    <?php if (($student["year"] ?? 0) == 1) echo "selected"; ?>
>
First Year
</option>


<option
    value="2"
    <?php if (($student["year"] ?? 0) == 2) echo "selected"; ?>
>
Second Year
</option>


<option
    value="3"
    <?php if (($student["year"] ?? 0) == 3) echo "selected"; ?>
>
Third Year
</option>


<option
    value="4"
    <?php if (($student["year"] ?? 0) == 4) echo "selected"; ?>
>
Final Year
</option>


</select>

</div>


<!-- CGPA -->

<div class="col-md-4">

<label class="form-label">

CGPA

</label>


<input
    type="number"
    name="cgpa"
    class="form-control"
    value="<?php echo htmlspecialchars($student["cgpa"] ?? ""); ?>"
    step="0.01"
    min="0"
    max="10"
    placeholder="e.g. 8.50"
>

</div>


<!-- GRADUATION -->

<div class="col-md-4">

<label class="form-label">

Graduation Year

</label>


<input
    type="number"
    name="graduation_year"
    class="form-control"
    value="<?php echo htmlspecialchars($student["graduation_year"] ?? ""); ?>"
    min="2024"
    max="2035"
    placeholder="e.g. 2029"
>

</div>


</div>


<hr class="my-5">


<!-- RESUME -->

<div class="section-heading">

<i class="fa-solid fa-file-lines"></i>

Resume

</div>


<div class="mb-3">

<label class="form-label">

Upload Resume

</label>


<input
    type="file"
    name="resume"
    class="form-control"
    accept=".pdf,.doc,.docx"
>


<small class="text-muted">

PDF, DOC or DOCX · Maximum size 5 MB

</small>

</div>


<?php if (!empty($student["resume"])): ?>


<div class="resume-card">


<div class="resume-info">


<div class="resume-icon">

<i class="fa-solid fa-file-pdf"></i>

</div>


<div>


<div class="resume-title">

Current Resume

</div>


<div class="resume-name">

<?php
echo htmlspecialchars(
    $student["resume"]
);
?>

</div>


</div>


</div>


<div class="resume-actions">


<a
    href="../uploads/resumes/<?php echo rawurlencode($student["resume"]); ?>"
    target="_blank"
    class="btn-small"
>

<i class="fa-solid fa-eye"></i>

View

</a>


<a
    href="../uploads/resumes/<?php echo rawurlencode($student["resume"]); ?>"
    download
    class="btn-small"
>

<i class="fa-solid fa-download"></i>

Download

</a>


</div>


</div>


<?php else: ?>


<div class="resume-card">

<div class="resume-info">

<div class="resume-icon">

<i class="fa-solid fa-file-circle-question"></i>

</div>


<div>

<div class="resume-title">

No resume uploaded

</div>


<div class="resume-name">

Upload your latest resume to complete your profile.

</div>

</div>

</div>

</div>


<?php endif; ?>


<!-- SAVE -->

<div class="text-end mt-5">


<button
    type="submit"
    class="btn-save"
>

<i class="fa-solid fa-floppy-disk"></i>

Save Profile

</button>


</div>


</div>


</form>


</div>


</section>


</main>


<script>


/* =========================================
   MOBILE SIDEBAR
========================================= */

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


function openSidebar()
{

    sidebar.classList.add(
        "show"
    );


    sidebarOverlay.classList.add(
        "show"
    );
}


function closeSidebar()
{

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
        function ()
        {

            if (
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