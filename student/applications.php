<?php

require_once "../includes/auth.php";
requireRole("student");

require_once "../config/database.php";


/* =========================================
   STUDENT INFORMATION
========================================= */

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        s.id,
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


/* =========================================
   SAFETY CHECK
========================================= */

if (!$student) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


$student_id = (int)$student["id"];


/* =========================================
   APPLICATION COUNTS
========================================= */

$totalApplications = 0;
$totalApplied = 0;
$totalShortlisted = 0;
$totalSelected = 0;
$totalRejected = 0;


/* =========================================
   GET APPLICATION STATISTICS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Applied' THEN 1 ELSE 0 END) AS applied,
        SUM(CASE WHEN status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted,
        SUM(CASE WHEN status = 'Selected' THEN 1 ELSE 0 END) AS selected_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
     FROM applications
     WHERE student_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$statsResult = mysqli_stmt_get_result($stmt);

$stats = mysqli_fetch_assoc($statsResult);


if ($stats) {

    $totalApplications =
        (int)($stats["total"] ?? 0);

    $totalApplied =
        (int)($stats["applied"] ?? 0);

    $totalShortlisted =
        (int)($stats["shortlisted"] ?? 0);

    $totalSelected =
        (int)($stats["selected_count"] ?? 0);

    $totalRejected =
        (int)($stats["rejected"] ?? 0);
}


/* =========================================
   GET APPLICATIONS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        a.id,
        a.status,
        a.applied_at,

        j.id AS job_id,
        j.job_title,
        j.location,
        j.salary,
        j.job_type,

        c.company_name

     FROM applications a

     INNER JOIN jobs j
        ON a.job_id = j.id

     INNER JOIN companies c
        ON j.company_id = c.id

     WHERE a.student_id = ?

     ORDER BY a.applied_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$applicationsResult =
    mysqli_stmt_get_result($stmt);


/* =========================================
   STUDENT DISPLAY INFORMATION
========================================= */

$studentName =
    htmlspecialchars(
        $student["full_name"] ?? "Student"
    );

$firstNameParts =
    explode(
        " ",
        trim(
            $student["full_name"] ?? "Student"
        )
    );

$firstName =
    htmlspecialchars(
        $firstNameParts[0]
    );

$initial =
    strtoupper(
        substr(
            trim(
                $student["full_name"] ?? "S"
            ),
            0,
            1
        )
    );


/* =========================================
   TIME BASED GREETING
========================================= */

$hour = (int)date("H");

if ($hour < 12) {

    $greeting = "Good morning";

} elseif ($hour < 17) {

    $greeting = "Good afternoon";

} else {

    $greeting = "Good evening";
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
    Applications | Smart Placement Portal
</title>


<!-- FONTS -->

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

    color: var(--text);

    overflow-x: hidden;
}


/* GRID BACKGROUND */

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


/* =========================================
   BRAND
========================================= */

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

    letter-spacing: -.3px;
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


/* =========================================
   SIDEBAR BOTTOM
========================================= */

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


.mobile-menu {

    display: none;

    width: 42px;
    height: 42px;

    border: none;

    border-radius: 12px;

    background:
        rgba(255,255,255,.06);

    color: white;

    cursor: pointer;
}


.welcome small {

    display: block;

    color: var(--muted);

    font-size: 10px;

    margin-bottom: 5px;
}


.welcome h2 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 22px;

    font-weight: 700;

    letter-spacing: -.5px;
}


.welcome h2 span {

    background:

        linear-gradient(
            90deg,
            #60a5fa,
            #a78bfa,
            #22d3ee
        );

    -webkit-background-clip: text;

    -webkit-text-fill-color: transparent;
}


/* TOP PROFILE */

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

    font-weight: 600;
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

    font-size: 14px;

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

    max-width: 1550px;

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
        36px;

    margin-bottom: 28px;

    border-radius: 24px;

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


.page-hero::before {

    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    right: -80px;
    top: -150px;

    border-radius: 50%;

    background:
        rgba(96,165,250,.18);

    filter:
        blur(45px);
}


.page-hero-content {

    position: relative;

    z-index: 2;
}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        7px
        12px;

    margin-bottom: 15px;

    border-radius: 30px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

    color: #cbd5e1;

    font-size: 10px;
}


.hero-badge i {

    color: #93c5fd;
}


.page-hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        clamp(
            27px,
            3vw,
            38px
        );

    letter-spacing: -1px;

    margin-bottom: 10px;
}


.page-hero h1 span {

    background:

        linear-gradient(
            90deg,
            #60a5fa,
            #a78bfa,
            #22d3ee
        );

    -webkit-background-clip: text;

    -webkit-text-fill-color: transparent;
}


.page-hero p {

    max-width: 600px;

    color: var(--muted);

    font-size: 12px;

    line-height: 1.8;
}


.hero-icon {

    position: absolute;

    right: 45px;

    bottom: -10px;

    font-size: 130px;

    color: white;

    opacity: .045;

    transform:
        rotate(-10deg);
}


/* =========================================
   STATS
========================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );

    gap: 17px;

    margin-bottom: 32px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    padding: 21px;

    border-radius: 18px;

    border:
        1px solid
        var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(15px);

    transition:
        .25s;
}


.stat-card:hover {

    transform:
        translateY(-5px);

    background:
        var(--card-hover);

    border-color:
        rgba(96,165,250,.25);

    box-shadow:

        0 20px 45px
        rgba(0,0,0,.18);
}


.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    margin-bottom: 18px;
}


.stat-icon {

    width: 43px;
    height: 43px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 13px;

    font-size: 15px;

    background:
        rgba(91,124,255,.13);

    color: #8ab4ff;
}


.stat-card:nth-child(2) .stat-icon {

    background:
        rgba(139,92,246,.13);

    color: #c4b5fd;
}


.stat-card:nth-child(3) .stat-icon {

    background:
        rgba(34,197,94,.10);

    color: #86efac;
}


.stat-card:nth-child(4) .stat-icon {

    background:
        rgba(239,68,68,.10);

    color: #fca5a5;
}


.stat-number {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 30px;

    font-weight: 700;

    margin-bottom: 4px;
}


.stat-label {

    color: var(--muted);

    font-size: 10px;
}


/* =========================================
   SECTION HEADER
========================================= */

.section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 17px;
}


.section-title h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 18px;

    margin-bottom: 5px;

    font-weight: 700;
}


.section-title span {

    color: var(--muted);

    font-size: 10px;
}


/* =========================================
   GLASS PANEL
========================================= */

.glass-panel {

    border-radius: 20px;

    border:
        1px solid
        var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

    overflow: hidden;
}


/* =========================================
   APPLICATION LIST
========================================= */

.application-list {

    padding: 10px;
}


.application-card {

    display: grid;

    grid-template-columns:
        auto
        1fr
        auto;

    gap: 18px;

    align-items: center;

    padding:
        18px;

    border-radius: 16px;

    transition:
        .25s;
}


.application-card:hover {

    background:
        rgba(255,255,255,.045);
}


.company-icon {

    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.20),
            rgba(124,58,237,.15)
        );

    color: #a5b4fc;

    font-size: 18px;
}


.application-info h4 {

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 5px;
}


.application-info p {

    color: var(--muted);

    font-size: 10px;

    margin-bottom: 10px;
}


.application-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 11px;

    color: var(--muted-dark);

    font-size: 9px;
}


.application-meta span {

    display: flex;

    align-items: center;

    gap: 5px;
}


.application-meta i {

    color: #60a5fa;
}


/* =========================================
   STATUS
========================================= */

.application-status {

    min-width: 105px;

    text-align: right;
}


.status {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        6px
        10px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 600;

    margin-bottom: 8px;
}


.status-applied {

    color: #93c5fd;

    background:
        rgba(59,130,246,.10);
}


.status-shortlisted {

    color: #c4b5fd;

    background:
        rgba(139,92,246,.12);
}


.status-selected {

    color: #86efac;

    background:
        rgba(34,197,94,.10);
}


.status-rejected {

    color: #fca5a5;

    background:
        rgba(239,68,68,.10);
}


.applied-date {

    color: var(--muted-dark);

    font-size: 9px;
}


.view-job-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    padding:
        8px
        12px;

    border-radius: 9px;

    text-decoration: none;

    color: #cbd5e1;

    font-size: 9px;

    font-weight: 600;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);

    transition:
        .25s;
}


.view-job-btn:hover {

    color: white;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    border-color:
        transparent;
}


/* =========================================
   EMPTY STATE
========================================= */

.empty-state {

    text-align: center;

    padding:
        70px
        20px;

    color: var(--muted);
}


.empty-state i {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    width: 60px;
    height: 60px;

    margin-bottom: 15px;

    border-radius: 18px;

    background:
        rgba(91,124,255,.10);

    color: #8ab4ff;

    font-size: 22px;
}


.empty-state h4 {

    color: #e2e8f0;

    font-size: 14px;

    margin-bottom: 8px;
}


.empty-state p {

    font-size: 10px;

    margin-bottom: 20px;
}


.explore-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        10px
        16px;

    border-radius: 10px;

    text-decoration: none;

    color: white;

    font-size: 10px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );
}


/* =========================================
   MOBILE OVERLAY
========================================= */

.sidebar-overlay {

    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0,0,0,.65);

    backdrop-filter:
        blur(3px);

    z-index: 999;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1100px) {

    .stats-grid {

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

        width: 265px;
    }


    .sidebar.show {

        transform:
            translateX(0);
    }


    .sidebar-overlay.show {

        display: block;
    }


    .main {

        margin-left: 0;
    }


    .mobile-menu {

        display: flex;

        align-items: center;
        justify-content: center;
    }


    .topbar {

        gap: 15px;

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

        display: none;
    }


    .welcome h2 {

        font-size: 17px;
    }


    .profile-text {

        display: none;
    }


    .page-hero {

        padding:
            27px
            23px;
    }


    .page-hero h1 {

        font-size: 27px;
    }


    .hero-icon {

        font-size: 100px;

        right: 10px;
    }


    .stats-grid {

        grid-template-columns:
            1fr
            1fr;

        gap: 12px;
    }


    .stat-card {

        padding: 16px;
    }


    .stat-number {

        font-size: 25px;
    }


    .application-card {

        grid-template-columns:
            auto
            1fr;
    }


    .application-status {

        grid-column:
            1 / -1;

        text-align: left;

        display: flex;

        align-items: center;

        gap: 10px;
    }


    .status {

        margin-bottom: 0;
    }

}


@media (max-width: 420px) {

    .stats-grid {

        grid-template-columns:
            1fr;
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

<?php echo htmlspecialchars($greeting); ?>, Student

</small>


<h2>

Your

<span>
    Applications
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

<div class="page-hero">


<div class="page-hero-content">


<div class="hero-badge">

<i class="fa-solid fa-file-lines"></i>

Track your placement journey

</div>


<h1>

Manage your

<span>
    career applications.
</span>

</h1>


<p>

Track every job you have applied for, monitor your
application status, and stay updated on your placement
progress in one place.

</p>


</div>


<i
    class="fa-solid fa-paper-plane hero-icon"
></i>


</div>


<!-- =========================================
     STATISTICS
========================================= -->

<div class="stats-grid">


<!-- TOTAL -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-file-lines"></i>

</div>

</div>


<div class="stat-number">

<?php echo $totalApplications; ?>

</div>


<div class="stat-label">

Total Applications

</div>

</div>


<!-- SHORTLISTED -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-star"></i>

</div>

</div>


<div class="stat-number">

<?php echo $totalShortlisted; ?>

</div>


<div class="stat-label">

Shortlisted

</div>

</div>


<!-- SELECTED -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-circle-check"></i>

</div>

</div>


<div class="stat-number">

<?php echo $totalSelected; ?>

</div>


<div class="stat-label">

Selected

</div>

</div>


<!-- REJECTED -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-circle-xmark"></i>

</div>

</div>


<div class="stat-number">

<?php echo $totalRejected; ?>

</div>


<div class="stat-label">

Rejected

</div>

</div>


</div>


<!-- =========================================
     APPLICATION SECTION
========================================= -->

<div class="section-header">


<div class="section-title">

<h3>
    Application History
</h3>

<span>
    View and track all your job applications
</span>

</div>


</div>


<div class="glass-panel">


<?php if (
    $applicationsResult &&
    mysqli_num_rows($applicationsResult) > 0
): ?>


<div class="application-list">


<?php while (
    $application =
    mysqli_fetch_assoc($applicationsResult)
): ?>


<?php

$status =
    strtolower(
        trim(
            $application["status"]
        )
    );

$statusClass =
    "status-applied";


if ($status === "shortlisted") {

    $statusClass =
        "status-shortlisted";

} elseif ($status === "selected") {

    $statusClass =
        "status-selected";

} elseif ($status === "rejected") {

    $statusClass =
        "status-rejected";
}

?>


<div class="application-card">


<!-- COMPANY ICON -->

<div class="company-icon">

<i class="fa-solid fa-building"></i>

</div>


<!-- APPLICATION INFORMATION -->

<div class="application-info">


<h4>

<?php
echo htmlspecialchars(
    $application["job_title"]
);
?>

</h4>


<p>

<?php
echo htmlspecialchars(
    $application["company_name"]
);
?>

</p>


<div class="application-meta">


<span>

<i class="fa-solid fa-location-dot"></i>

<?php
echo htmlspecialchars(
    $application["location"]
    ?: "Location not specified"
);
?>

</span>


<span>

<i class="fa-solid fa-briefcase"></i>

<?php
echo htmlspecialchars(
    $application["job_type"]
    ?: "Not specified"
);
?>

</span>


<?php if (!empty($application["salary"])): ?>

<span>

<i class="fa-solid fa-indian-rupee-sign"></i>

<?php
echo htmlspecialchars(
    $application["salary"]
);
?>

</span>

<?php endif; ?>


</div>


</div>


<!-- STATUS -->

<div class="application-status">


<div
    class="status <?php echo $statusClass; ?>"
>

<?php
echo htmlspecialchars(
    $application["status"]
);
?>

</div>


<div class="applied-date">

Applied:
<?php

if (!empty($application["applied_at"])) {

    echo htmlspecialchars(
        date(
            "d M Y",
            strtotime(
                $application["applied_at"]
            )
        )
    );

} else {

    echo "Not available";
}

?>

</div>


<a
    href="job_details.php?id=<?php echo (int)$application["job_id"]; ?>"
    class="view-job-btn"
>

<i class="fa-solid fa-arrow-up-right-from-square"></i>

View Job

</a>


</div>


</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<div class="empty-state">


<i class="fa-solid fa-file-circle-xmark"></i>


<h4>

No applications yet

</h4>


<p>

You have not applied to any jobs yet.
Explore available opportunities and start
building your career journey.

</p>


<a
    href="jobs.php"
    class="explore-btn"
>

<i class="fa-solid fa-briefcase"></i>

Explore Jobs

</a>


</div>


<?php endif; ?>


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

</script>


</body>

</html>