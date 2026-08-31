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
   GET STUDENT DETAILS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        s.id,
        u.full_name,
        u.email
     FROM students s
     INNER JOIN users u
        ON s.user_id = u.id
     WHERE s.user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);


if (!$student) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


$student_id = (int)$student["id"];

$fullName = $student["full_name"] ?? "Student";

$studentName = htmlspecialchars(
    $fullName
);

$firstName = explode(
    " ",
    trim($fullName)
)[0];

$firstName = htmlspecialchars($firstName);

$initial = strtoupper(
    substr(
        trim($fullName),
        0,
        1
    )
);


/* =========================================
   SAVE SKILLS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selectedSkills =
        $_POST["skills"] ?? [];


    /* Make sure input is an array */

    if (!is_array($selectedSkills)) {

        $selectedSkills = [];

    }


    /* Convert values to integers */

    $selectedSkills =
        array_map(
            "intval",
            $selectedSkills
        );


    /* Remove duplicates */

    $selectedSkills =
        array_unique(
            $selectedSkills
        );


    /* Remove invalid values */

    $selectedSkills =
        array_filter(
            $selectedSkills,
            function ($skillId) {

                return $skillId > 0;

            }
        );


    /* Re-index array */

    $selectedSkills =
        array_values(
            $selectedSkills
        );


    mysqli_begin_transaction($conn);


    try {


        /* =========================================
           DELETE OLD SKILLS
        ========================================= */

        $deleteStmt =
            mysqli_prepare(
                $conn,
                "DELETE FROM student_skills
                 WHERE student_id = ?"
            );


        if (!$deleteStmt) {

            throw new Exception(
                "Unable to prepare skill deletion."
            );

        }


        mysqli_stmt_bind_param(
            $deleteStmt,
            "i",
            $student_id
        );


        if (
            !mysqli_stmt_execute(
                $deleteStmt
            )
        ) {

            throw new Exception(
                "Unable to remove old skills."
            );

        }


        /* =========================================
           INSERT NEW SKILLS
        ========================================= */

        if (!empty($selectedSkills)) {


            $checkStmt =
                mysqli_prepare(
                    $conn,
                    "SELECT id
                     FROM skills
                     WHERE id = ?
                     LIMIT 1"
                );


            $insertStmt =
                mysqli_prepare(
                    $conn,
                    "INSERT INTO student_skills
                     (student_id, skill_id)
                     VALUES (?, ?)"
                );


            if (
                !$checkStmt ||
                !$insertStmt
            ) {

                throw new Exception(
                    "Unable to prepare skill update."
                );

            }


            foreach (
                $selectedSkills
                as $skill_id
            ) {


                /* Verify skill exists */

                mysqli_stmt_bind_param(
                    $checkStmt,
                    "i",
                    $skill_id
                );


                mysqli_stmt_execute(
                    $checkStmt
                );


                $checkResult =
                    mysqli_stmt_get_result(
                        $checkStmt
                    );


                if (
                    mysqli_num_rows(
                        $checkResult
                    ) === 0
                ) {

                    continue;

                }


                /* Insert skill */

                mysqli_stmt_bind_param(
                    $insertStmt,
                    "ii",
                    $student_id,
                    $skill_id
                );


                if (
                    !mysqli_stmt_execute(
                        $insertStmt
                    )
                ) {

                    throw new Exception(
                        "Unable to save selected skills."
                    );

                }

            }

        }


        mysqli_commit($conn);


        $message =
            "Your skills have been updated successfully!";

        $messageType =
            "success";


    } catch (Exception $e) {


        mysqli_rollback($conn);


        $message =
            "Unable to update skills. Please try again.";

        $messageType =
            "danger";

    }

}


/* =========================================
   GET ALL SKILLS
========================================= */

$skillsResult =
    mysqli_query(
        $conn,
        "SELECT
            id,
            skill_name
         FROM skills
         ORDER BY skill_name ASC"
    );


/* =========================================
   GET SELECTED SKILLS
========================================= */

$selectedSkillIds = [];


$stmt =
    mysqli_prepare(
        $conn,
        "SELECT skill_id
         FROM student_skills
         WHERE student_id = ?"
    );


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


while (
    $row =
    mysqli_fetch_assoc($result)
) {

    $selectedSkillIds[] =
        (int)$row["skill_id"];

}


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

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    My Skills | Smart Placement Portal
</title>


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


html {

    scroll-behavior: smooth;

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

    color:
        var(--text);

    overflow-x:
        hidden;

}


/* GRID BACKGROUND */

body::before {

    content: "";

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
        55px
        55px;

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
        25px
        16px;

    display:
        flex;

    flex-direction:
        column;

    background:
        rgba(3,7,18,.78);

    backdrop-filter:
        blur(24px);

    border-right:
        1px solid
        var(--border);

    z-index:
        1000;

}


/* =========================================
   BRAND
========================================= */

.brand {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        8px
        10px
        34px;

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
   NAVIGATION
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
        0
        12px;

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
        13px
        14px;

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
        .25s
        ease;

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


/* =========================================
   SIDEBAR BOTTOM
========================================= */

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
        15px
        8px;

}


.logout-link:hover {

    color:
        #fca5a5;

    background:
        rgba(239,68,68,.08);

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
        18px
        38px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    border-bottom:
        1px solid
        var(--border);

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


/* PROFILE */

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


/* =========================================
   CONTENT
========================================= */

.content {

    max-width:
        1550px;

    margin:
        auto;

    padding:
        35px
        38px
        55px;

}


/* =========================================
   HERO
========================================= */

.hero {

    position:
        relative;

    overflow:
        hidden;

    padding:
        32px
        38px;

    margin-bottom:
        28px;

    border-radius:
        24px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.22),
            rgba(124,58,237,.16),
            rgba(34,211,238,.06)
        );

    backdrop-filter:
        blur(20px);

    box-shadow:

        0 25px 60px
        rgba(0,0,0,.18);

}


.hero::before {

    content:
        "";

    position:
        absolute;

    width:
        300px;

    height:
        300px;

    right:
        -100px;

    top:
        -150px;

    border-radius:
        50%;

    background:
        rgba(96,165,250,.18);

    filter:
        blur(45px);

}


.hero-content {

    position:
        relative;

    z-index:
        2;

}


.hero-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    padding:
        7px
        12px;

    margin-bottom:
        17px;

    border-radius:
        30px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

    color:
        #cbd5e1;

    font-size:
        10px;

}


.hero-badge-dot {

    width:
        7px;

    height:
        7px;

    border-radius:
        50%;

    background:
        #22c55e;

    box-shadow:
        0 0 12px #22c55e;

}


.hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        clamp(
            27px,
            3vw,
            38px
        );

    line-height:
        1.15;

    letter-spacing:
        -1px;

    margin-bottom:
        12px;

}


.hero h1 span {

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


.hero p {

    max-width:
        620px;

    color:
        var(--muted);

    font-size:
        12px;

    line-height:
        1.8;

}


.hero-icon {

    position:
        absolute;

    right:
        45px;

    bottom:
        -12px;

    font-size:
        135px;

    color:
        white;

    opacity:
        .045;

    transform:
        rotate(-12deg);

}


/* =========================================
   SECTION HEADER
========================================= */

.section-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    margin:
        0
        0
        17px;

}


.section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

}


.section-title h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        18px;

    font-weight:
        700;

    margin-bottom:
        5px;

}


.section-title span {

    color:
        var(--muted);

    font-size:
        10px;

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
        10px;

    padding:
        14px
        17px;

    margin-bottom:
        20px;

    border-radius:
        14px;

    font-size:
        11px;

    border:
        1px solid;

}


.alert-success {

    color:
        #86efac;

    background:
        rgba(34,197,94,.09);

    border-color:
        rgba(34,197,94,.20);

}


.alert-danger {

    color:
        #fca5a5;

    background:
        rgba(239,68,68,.09);

    border-color:
        rgba(239,68,68,.20);

}


/* =========================================
   SKILLS PANEL
========================================= */

.skills-card {

    border-radius:
        22px;

    border:
        1px solid
        var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

    padding:
        25px;

    overflow:
        hidden;

}


/* =========================================
   SEARCH
========================================= */

.search-box {

    position:
        relative;

    margin-bottom:
        20px;

}


.search-box i {

    position:
        absolute;

    left:
        16px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--muted);

    font-size:
        12px;

}


.search-box input {

    width:
        100%;

    height:
        50px;

    padding:
        0
        18px
        0
        44px;

    border-radius:
        13px;

    border:
        1px solid
        rgba(255,255,255,.10);

    outline:
        none;

    background:
        rgba(255,255,255,.04);

    color:
        white;

    font-family:
        inherit;

    font-size:
        11px;

    transition:
        .25s;

}


.search-box input::placeholder {

    color:
        var(--muted-dark);

}


.search-box input:focus {

    border-color:
        rgba(91,124,255,.65);

    box-shadow:
        0 0 0 4px
        rgba(91,124,255,.08);

}


/* =========================================
   SKILLS SUMMARY
========================================= */

.skills-summary {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        15px
        18px;

    margin-bottom:
        22px;

    border-radius:
        14px;

    border:
        1px solid
        rgba(91,124,255,.15);

    background:
        rgba(91,124,255,.08);

}


.skills-summary-left {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    color:
        #cbd5e1;

    font-size:
        11px;

}


.skills-summary-left i {

    color:
        #8ab4ff;

}


.skills-count {

    font-weight:
        600;

}


.clear-btn {

    border:
        none;

    background:
        transparent;

    color:
        #93c5fd;

    font-family:
        inherit;

    font-size:
        10px;

    font-weight:
        700;

    cursor:
        pointer;

    transition:
        .2s;

}


.clear-btn:hover {

    color:
        white;

}


/* =========================================
   SKILLS GRID
========================================= */

.skills-grid {

    display:
        grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0,1fr)
        );

    gap:
        13px;

}


/* =========================================
   SKILL ITEM
========================================= */

.skill-item {

    position:
        relative;

}


.skill-checkbox {

    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;

}


.skill-label {

    min-height:
        58px;

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        12px
        15px;

    border-radius:
        14px;

    border:
        1px solid
        rgba(255,255,255,.09);

    background:
        rgba(255,255,255,.025);

    cursor:
        pointer;

    transition:
        .25s;

}


.skill-label:hover {

    transform:
        translateY(-2px);

    border-color:
        rgba(96,165,250,.35);

    background:
        rgba(255,255,255,.05);

}


.skill-check {

    width:
        22px;

    height:
        22px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        7px;

    border:
        2px solid
        rgba(148,163,184,.45);

    transition:
        .2s;

}


.skill-check i {

    display:
        none;

    font-size:
        10px;

    color:
        white;

}


.skill-name {

    color:
        #cbd5e1;

    font-size:
        11px;

    font-weight:
        600;

}


/* =========================================
   CHECKED STATE
========================================= */

.skill-checkbox:checked
+ .skill-label {

    border-color:
        rgba(91,124,255,.70);

    background:
        linear-gradient(
            135deg,
            rgba(91,124,255,.17),
            rgba(124,58,237,.13)
        );

    box-shadow:
        0 8px 20px
        rgba(91,124,255,.10);

}


.skill-checkbox:checked
+ .skill-label
.skill-check {

    border-color:
        transparent;

    background:
        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

}


.skill-checkbox:checked
+ .skill-label
.skill-check i {

    display:
        block;

}


.skill-checkbox:checked
+ .skill-label
.skill-name {

    color:
        white;

}


/* =========================================
   ACTION AREA
========================================= */

.action-area {

    display:
        flex;

    justify-content:
        flex-end;

    margin-top:
        28px;

    padding-top:
        22px;

    border-top:
        1px solid
        rgba(255,255,255,.07);

}


.save-btn {

    min-height:
        46px;

    padding:
        0
        22px;

    border:
        none;

    border-radius:
        11px;

    cursor:
        pointer;

    color:
        white;

    font-family:
        inherit;

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


.save-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 18px 35px
        rgba(91,124,255,.32);

}


.save-btn:active {

    transform:
        translateY(0);

}


/* =========================================
   EMPTY STATE
========================================= */

.no-skills {

    grid-column:
        1 / -1;

    text-align:
        center;

    padding:
        60px
        20px;

    color:
        var(--muted);

}


.no-skills i {

    font-size:
        35px;

    margin-bottom:
        15px;

    color:
        #8ab4ff;

}


.no-skills h4 {

    color:
        #e2e8f0;

    font-size:
        13px;

    margin-bottom:
        8px;

}


.no-skills p {

    font-size:
        10px;

}


/* =========================================
   MOBILE OVERLAY
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

@media (max-width: 1100px) {

    .skills-grid {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }

}


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

        gap:
            15px;

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


    .hero {

        padding:
            27px
            23px;

    }


    .hero h1 {

        font-size:
            27px;

    }


    .hero-icon {

        font-size:
            95px;

        right:
            10px;

    }


    .skills-card {

        padding:
            18px;

    }


    .skills-grid {

        grid-template-columns:
            1fr;

    }


    .skills-summary {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .action-area {

        justify-content:
            stretch;

    }


    .save-btn {

        width:
            100%;

    }

}

</style>

</head>


<body>

<?php require_once "../includes/student_sidebar.php"; ?>



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
    aria-label="Open menu"
>

    <i class="fa-solid fa-bars"></i>

</button>


<div class="welcome">


    <small>

        <?php echo $greeting; ?>, Student

    </small>


    <h2>

        Manage your

        <span>

            Skills

        </span>

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
        echo htmlspecialchars($initial);
        ?>

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

<div class="hero">


    <div class="hero-content">


        <div class="hero-badge">

            <span class="hero-badge-dot"></span>

            Build a stronger professional profile

        </div>


        <h1>

            Showcase your

            <br>

            <span>

                skills & expertise.

            </span>

        </h1>


        <p>

            Select the technical and professional skills
            you have. Keeping your skills updated can help
            you receive better job recommendations and
            opportunities.

        </p>


    </div>


    <i
        class="fa-solid fa-code hero-icon"
    ></i>


</div>


<!-- =========================================
     ALERT
========================================= -->

<?php if ($message): ?>


    <div
        class="alert alert-<?php
        echo htmlspecialchars($messageType);
        ?>"
    >

        <i
            class="fa-solid <?php
            echo $messageType === "success"
                ? "fa-circle-check"
                : "fa-circle-exclamation";
            ?>"
        ></i>


        <?php
        echo htmlspecialchars($message);
        ?>


    </div>


<?php endif; ?>


<!-- =========================================
     SECTION HEADER
========================================= -->

<div class="section-header">


    <div class="section-title">

        <div>

            <h3>

                Your Skills

            </h3>


            <span>

                Search and select the skills you currently have

            </span>

        </div>

    </div>


</div>


<!-- =========================================
     SKILLS CARD
========================================= -->

<div class="skills-card">


<form
    method="POST"
    id="skillsForm"
>


<!-- SEARCH -->

<div class="search-box">


    <i
        class="fa-solid fa-magnifying-glass"
    ></i>


    <input
        type="text"
        id="skillSearch"
        placeholder="Search skills..."
        autocomplete="off"
    >


</div>


<!-- SUMMARY -->

<div class="skills-summary">


    <div class="skills-summary-left">


        <i
            class="fa-solid fa-circle-check"
        ></i>


        <span
            class="skills-count"
            id="selectedCount"
        >

            <?php
            echo count($selectedSkillIds);
            ?>

            skills selected

        </span>


    </div>


    <button
        type="button"
        class="clear-btn"
        id="clearSkills"
    >

        <i class="fa-solid fa-xmark"></i>

        Clear All

    </button>


</div>


<!-- SKILLS GRID -->

<div
    class="skills-grid"
    id="skillsGrid"
>


<?php if (
    $skillsResult &&
    mysqli_num_rows($skillsResult) > 0
): ?>


    <?php while (
        $skill =
        mysqli_fetch_assoc($skillsResult)
    ): ?>


        <?php

        $skillId =
            (int)$skill["id"];


        $skillName =
            $skill["skill_name"];


        $isSelected =
            in_array(
                $skillId,
                $selectedSkillIds,
                true
            );

        ?>


        <div
            class="skill-item"
            data-skill="<?php
            echo htmlspecialchars(
                strtolower($skillName),
                ENT_QUOTES,
                "UTF-8"
            );
            ?>"
        >


            <input
                type="checkbox"
                class="skill-checkbox"
                name="skills[]"
                value="<?php
                echo $skillId;
                ?>"
                id="skill-<?php
                echo $skillId;
                ?>"
                <?php
                echo $isSelected
                    ? "checked"
                    : "";
                ?>
            >


            <label
                for="skill-<?php
                echo $skillId;
                ?>"
                class="skill-label"
            >


                <span
                    class="skill-check"
                >

                    <i
                        class="fa-solid fa-check"
                    ></i>

                </span>


                <span
                    class="skill-name"
                >

                    <?php
                    echo htmlspecialchars(
                        $skillName
                    );
                    ?>

                </span>


            </label>


        </div>


    <?php endwhile; ?>


<?php else: ?>


    <div class="no-skills">


        <i
            class="fa-solid fa-code"
        ></i>


        <h4>

            No skills available

        </h4>


        <p>

            Skills will be added by the administrator.

        </p>


    </div>


<?php endif; ?>


</div>


<!-- ACTION -->

<div class="action-area">


    <button
        type="submit"
        class="save-btn"
    >

        <i
            class="fa-solid fa-floppy-disk"
        ></i>

        Save Skills

    </button>


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


function openSidebar() {

    sidebar.classList.add(
        "show"
    );

    sidebarOverlay.classList.add(
        "show"
    );

}


function closeSidebar() {

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


/* =========================================
   SKILLS SEARCH
========================================= */

const searchInput =
    document.getElementById(
        "skillSearch"
    );

const skillItems =
    document.querySelectorAll(
        ".skill-item"
    );

const selectedCount =
    document.getElementById(
        "selectedCount"
    );

const clearButton =
    document.getElementById(
        "clearSkills"
    );


/* =========================================
   UPDATE SELECTED COUNT
========================================= */

function updateCount() {

    const checked =
        document.querySelectorAll(
            ".skill-checkbox:checked"
        ).length;


    if (selectedCount) {

        selectedCount.textContent =
            checked +
            (
                checked === 1
                    ? " skill selected"
                    : " skills selected"
            );

    }

}


/* =========================================
   SEARCH SKILLS
========================================= */

if (searchInput) {

    searchInput.addEventListener(
        "input",
        function () {

            const search =
                this.value
                    .toLowerCase()
                    .trim();


            skillItems.forEach(
                function (item) {

                    const skillName =
                        item.dataset.skill ||
                        "";


                    if (
                        skillName.includes(
                            search
                        )
                    ) {

                        item.style.display =
                            "";

                    } else {

                        item.style.display =
                            "none";

                    }

                }
            );

        }
    );

}


/* =========================================
   UPDATE COUNT
========================================= */

document
    .querySelectorAll(
        ".skill-checkbox"
    )
    .forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                updateCount
            );

        }
    );


/* =========================================
   CLEAR ALL
========================================= */

if (clearButton) {

    clearButton.addEventListener(
        "click",
        function () {

            document
                .querySelectorAll(
                    ".skill-checkbox"
                )
                .forEach(
                    function (checkbox) {

                        checkbox.checked =
                            false;

                    }
                );


            updateCount();

        }
    );

}


/* =========================================
   INITIAL COUNT
========================================= */

updateCount();

</script>


</body>

</html>