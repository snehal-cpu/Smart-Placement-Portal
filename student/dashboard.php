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
        s.enrollment_no,
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


/* =========================================
   SAFETY CHECK
========================================= */

if (!$student) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


$student_id = $student["id"];


/* =========================================
   APPLICATION COUNT
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$applications = mysqli_fetch_assoc($result);

$totalApplications = (int)$applications["total"];


/* =========================================
   SHORTLISTED COUNT
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id = ?
     AND status = 'Shortlisted'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$shortlisted = mysqli_fetch_assoc($result);

$totalShortlisted = (int)$shortlisted["total"];


/* =========================================
   SELECTED COUNT
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id = ?
     AND status = 'Selected'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$selected = mysqli_fetch_assoc($result);

$totalSelected = (int)$selected["total"];


/* =========================================
   AVAILABLE JOB COUNT
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs
     WHERE status = 'Open'
     AND (
        application_deadline IS NULL
        OR application_deadline >= CURDATE()
     )"
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$jobsCountData = mysqli_fetch_assoc($result);

$totalJobs = (int)$jobsCountData["total"];


/* =========================================
   LATEST AVAILABLE JOBS
========================================= */

$jobsResult = mysqli_query(
    $conn,
    "SELECT
        j.id,
        j.job_title,
        j.location,
        j.salary,
        j.job_type,
        j.application_deadline,
        c.company_name
     FROM jobs j
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE j.status = 'Open'
     AND (
        j.application_deadline IS NULL
        OR j.application_deadline >= CURDATE()
     )
     ORDER BY j.created_at DESC
     LIMIT 5"
);


/* =========================================
   RECENT APPLICATIONS
========================================= */

$applicationsResult = mysqli_query(
    $conn,
    "SELECT
        a.status,
        a.applied_at,
        j.job_title,
        c.company_name
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE a.student_id = " . (int)$student_id . "
     ORDER BY a.applied_at DESC
     LIMIT 5"
);


/* =========================================
   STUDENT NAME
========================================= */

$studentName = htmlspecialchars(
    $student["full_name"]
);

$firstName = explode(
    " ",
    trim($student["full_name"])
)[0];

$firstName = htmlspecialchars($firstName);

$initial = strtoupper(
    substr(
        trim($student["full_name"]),
        0,
        1
    )
);


/* =========================================
   GREETING
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
    Student Dashboard | Smart Placement Portal
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

    --bg: #030712;

    --bg-secondary: #07111f;

    --primary: #6366f1;

    --purple: #8b5cf6;

    --cyan: #22d3ee;

    --text: #f8fafc;

    --muted: #94a3b8;

    --border:
        rgba(255,255,255,.09);

    --card:
        rgba(255,255,255,.045);

}


/* =========================================
   RESET
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

    color: var(--text);

    background:

        radial-gradient(
            circle at 15% 15%,
            rgba(99,102,241,.18),
            transparent 28%
        ),

        radial-gradient(
            circle at 85% 80%,
            rgba(139,92,246,.15),
            transparent 28%
        ),

        linear-gradient(
            135deg,
            #030712,
            #07111f,
            #0b1020
        );

    overflow-x: hidden;

}


/* GRID BACKGROUND */

body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    z-index: -1;

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
        18px 38px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom:
        1px solid var(--border);

    background:
        rgba(3,7,18,.35);

    backdrop-filter:
        blur(20px);

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

    font-size: 23px;

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


/* PROFILE */

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

    width: 44px;

    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );

    box-shadow:
        0 10px 25px
        rgba(99,102,241,.25);

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
        60px;

}


/* =========================================
   HERO
========================================= */

.hero {

    position: relative;

    overflow: hidden;

    padding:
        38px;

    border-radius: 24px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:

        linear-gradient(
            135deg,
            rgba(99,102,241,.22),
            rgba(139,92,246,.16),
            rgba(34,211,238,.05)
        );

    box-shadow:
        0 25px 60px
        rgba(0,0,0,.20);

}


.hero::after {

    content: "";

    position: absolute;

    width: 300px;

    height: 300px;

    border-radius: 50%;

    right: -100px;

    top: -150px;

    background:
        rgba(99,102,241,.18);

    filter:
        blur(45px);

}


.hero-content {

    position: relative;

    z-index: 2;

}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        7px 12px;

    margin-bottom: 17px;

    border-radius: 30px;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid
        rgba(255,255,255,.09);

    color:
        #cbd5e1;

    font-size: 10px;

}


.hero-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

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
        clamp(28px,3vw,40px);

    line-height: 1.15;

    letter-spacing: -1px;

    margin-bottom: 13px;

}


.hero h1 span {

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


.hero p {

    max-width: 600px;

    color: var(--muted);

    font-size: 12px;

    line-height: 1.8;

}


.hero-actions {

    display: flex;

    gap: 12px;

    margin-top: 25px;

    flex-wrap: wrap;

}


.btn-primary {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        11px 18px;

    border-radius: 11px;

    text-decoration: none;

    color: white;

    font-size: 11px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );

    box-shadow:
        0 12px 28px
        rgba(99,102,241,.25);

}


.btn-secondary {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        11px 18px;

    border-radius: 11px;

    text-decoration: none;

    color: #cbd5e1;

    font-size: 11px;

    font-weight: 600;

    border:
        1px solid var(--border);

    background:
        rgba(255,255,255,.04);

}


.hero-icon {

    position: absolute;

    right: 50px;

    bottom: -10px;

    font-size: 140px;

    opacity: .05;

    transform:
        rotate(-12deg);

}


/* =========================================
   SECTION HEADER
========================================= */

.section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin:
        34px 0 16px;

}


.section-header h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 17px;

}


.section-header span {

    display: block;

    margin-top: 4px;

    color: var(--muted);

    font-size: 10px;

}


.view-all {

    color: #93c5fd;

    text-decoration: none;

    font-size: 10px;

    font-weight: 600;

}


/* =========================================
   STATISTICS
========================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 17px;

}


.stat-card {

    padding: 21px;

    border-radius: 18px;

    border:
        1px solid var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

    transition:
        .25s;

}


.stat-card:hover {

    transform:
        translateY(-5px);

    border-color:
        rgba(99,102,241,.28);

    box-shadow:
        0 20px 45px
        rgba(0,0,0,.18);

}


.stat-top {

    display: flex;

    justify-content: space-between;

    margin-bottom: 20px;

}


.stat-icon {

    width: 44px;

    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    color: #a5b4fc;

    background:
        rgba(99,102,241,.14);

}


.stat-card:nth-child(2)
.stat-icon {

    color: #c4b5fd;

    background:
        rgba(139,92,246,.13);

}


.stat-card:nth-child(3)
.stat-icon {

    color: #86efac;

    background:
        rgba(34,197,94,.10);

}


.stat-card:nth-child(4)
.stat-icon {

    color: #67e8f9;

    background:
        rgba(34,211,238,.10);

}


.stat-number {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 31px;

    font-weight: 700;

}


.stat-label {

    margin-top: 4px;

    color: var(--muted);

    font-size: 10px;

}


/* =========================================
   DASHBOARD GRID
========================================= */

.dashboard-grid {

    display: grid;

    grid-template-columns:
        minmax(0,1.6fr)
        minmax(300px,.9fr);

    gap: 22px;

}


/* =========================================
   GLASS PANEL
========================================= */

.glass-panel {

    border-radius: 20px;

    overflow: hidden;

    border:
        1px solid var(--border);

    background:
        var(--card);

    backdrop-filter:
        blur(18px);

}


/* =========================================
   JOB LIST
========================================= */

.job-list {

    padding: 10px;

}


.job-card {

    display: grid;

    grid-template-columns:
        auto
        1fr
        auto;

    gap: 15px;

    align-items: center;

    padding: 16px;

    border-radius: 15px;

}


.job-card:hover {

    background:
        rgba(255,255,255,.04);

}


.company-icon {

    width: 47px;

    height: 47px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    color: #a5b4fc;

    background:

        linear-gradient(
            135deg,
            rgba(99,102,241,.20),
            rgba(139,92,246,.16)
        );

}


.job-info h4 {

    font-size: 12px;

    margin-bottom: 5px;

}


.job-info p {

    color: var(--muted);

    font-size: 10px;

    margin-bottom: 8px;

}


.job-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    color: #64748b;

    font-size: 9px;

}


.job-meta i {

    color: #60a5fa;

}


.job-button {

    padding:
        9px 13px;

    border-radius: 9px;

    text-decoration: none;

    color: #cbd5e1;

    font-size: 10px;

    border:
        1px solid var(--border);

}


.job-button:hover {

    color: white;

    background:
        var(--primary);

}


/* =========================================
   PROFILE
========================================= */

.profile-panel {

    padding: 22px;

}


.profile-summary {

    display: flex;

    gap: 13px;

    align-items: center;

    margin-bottom: 18px;

}


.large-avatar {

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 16px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );

}


.profile-summary h4 {

    font-size: 12px;

}


.profile-summary p {

    margin-top: 4px;

    font-size: 9px;

    color: var(--muted);

}


.profile-detail {

    display: flex;

    justify-content: space-between;

    padding:
        11px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);

}


.profile-detail span {

    color: #64748b;

    font-size: 9px;

}


.profile-detail strong {

    color: #cbd5e1;

    font-size: 9px;

}


/* =========================================
   ACTIVITY
========================================= */

.activity-list {

    padding: 10px 15px;

}


.activity-item {

    display: flex;

    gap: 11px;

    padding:
        14px 4px;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);

}


.activity-item:last-child {

    border-bottom: none;

}


.activity-icon {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    color: #a5b4fc;

    background:
        rgba(99,102,241,.12);

}


.activity-content {

    flex: 1;

}


.activity-content strong {

    display: block;

    font-size: 10px;

}


.activity-content span {

    display: block;

    margin-top: 4px;

    color: var(--muted);

    font-size: 9px;

}


.status {

    display: inline-block;

    margin-top: 7px;

    padding:
        4px 8px;

    border-radius: 20px;

    font-size: 8px;

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


/* =========================================
   EMPTY STATE
========================================= */

.empty-state {

    padding:
        55px 20px;

    text-align: center;

    color: var(--muted);

}


.empty-state i {

    font-size: 25px;

    margin-bottom: 12px;

}


.empty-state h4 {

    color: white;

    font-size: 12px;

    margin-bottom: 7px;

}


.empty-state p {

    font-size: 10px;

}


/* =========================================
   SIDEBAR OVERLAY
========================================= */

.sidebar-overlay {

    display: none;

}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1150px) {

    .stats-grid {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media (max-width: 1000px) {

    .dashboard-grid {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 850px) {

    .main {

        margin-left: 0;

    }

    .mobile-menu {

        display: flex;

        align-items: center;

        justify-content: center;

    }

    .sidebar-overlay.show {

        display: block;

        position: fixed;

        inset: 0;

        background:
            rgba(0,0,0,.65);

        z-index: 999;

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

    .profile-text {

        display: none;

    }

    .stats-grid {

        grid-template-columns:
            repeat(2,1fr);

        gap: 12px;

    }

    .hero {

        padding: 27px 23px;

    }

    .hero-icon {

        font-size: 95px;

        right: 10px;

    }

    .job-card {

        grid-template-columns:
            auto 1fr;

    }

    .job-button {

        grid-column:
            1 / -1;

        text-align: center;

    }

}


@media (max-width: 420px) {

    .stats-grid {

        grid-template-columns:
            1fr;

    }

    .hero-actions {

        flex-direction:
            column;

    }

    .btn-primary,
    .btn-secondary {

        justify-content: center;

    }

}

</style>

</head>


<body>
<link
    rel="stylesheet"
    href="../assets/css/student_sidebar.css"
>

<!-- SIDEBAR -->

<?php require_once "../includes/student_sidebar.php"; ?>


<!-- MOBILE OVERLAY -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- MAIN -->

<main class="main">


<!-- TOPBAR -->

<header class="topbar">


<button
    class="mobile-menu"
    id="mobileMenu"
>

<i class="fa-solid fa-bars"></i>

</button>


<div class="welcome">

<small>

<?php echo $greeting; ?>, Student

</small>


<h2>

Welcome back,

<span>

<?php echo $firstName; ?>

</span>

👋

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


<!-- CONTENT -->

<section class="content">


<!-- HERO -->

<div class="hero">

<div class="hero-content">


<div class="hero-badge">

<span class="hero-dot"></span>

Placement Portal is ready for you

</div>


<h1>

Take the next step toward

<br>

<span>

your dream career.

</span>

</h1>


<p>

Discover new opportunities, track your applications,
and build your professional profile for a successful
placement journey.

</p>


<div class="hero-actions">


<a
    href="jobs.php"
    class="btn-primary"
>

<i class="fa-solid fa-briefcase"></i>

Explore Jobs

</a>


<a
    href="profile.php"
    class="btn-secondary"
>

<i class="fa-solid fa-user-pen"></i>

Update Profile

</a>


</div>


</div>


<i
    class="fa-solid fa-graduation-cap hero-icon"
></i>


</div>



<!-- PROGRESS -->

<div class="section-header">

<div>

<h3>Your Progress</h3>

<span>

A quick overview of your placement activity

</span>

</div>

</div>


<div class="stats-grid">


<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-paper-plane"></i>

</div>

</div>

<div class="stat-number">

<?php echo $totalApplications; ?>

</div>

<div class="stat-label">

Total Applications

</div>

</div>



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



<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-briefcase"></i>

</div>

</div>

<div class="stat-number">

<?php echo $totalJobs; ?>

</div>

<div class="stat-label">

Open Opportunities

</div>

</div>


</div>



<!-- DASHBOARD GRID -->

<div class="dashboard-grid">


<!-- LEFT -->

<div>


<div class="section-header">

<div>

<h3>Latest Opportunities</h3>

<span>

Recently posted placement opportunities

</span>

</div>


<a
    href="jobs.php"
    class="view-all"
>

View All
<i class="fa-solid fa-arrow-right"></i>

</a>


</div>


<div class="glass-panel">


<?php if (
    $jobsResult &&
    mysqli_num_rows($jobsResult) > 0
): ?>


<div class="job-list">


<?php while (
    $job = mysqli_fetch_assoc($jobsResult)
): ?>


<div class="job-card">


<div class="company-icon">

<i class="fa-solid fa-building"></i>

</div>


<div class="job-info">


<h4>

<?php
echo htmlspecialchars(
    $job["job_title"]
);
?>

</h4>


<p>

<?php
echo htmlspecialchars(
    $job["company_name"]
);
?>

</p>


<div class="job-meta">


<span>

<i class="fa-solid fa-location-dot"></i>

<?php
echo htmlspecialchars(
    $job["location"]
    ?: "Location not specified"
);
?>

</span>


<span>

<i class="fa-solid fa-briefcase"></i>

<?php
echo htmlspecialchars(
    $job["job_type"]
    ?: "Not specified"
);
?>

</span>


<?php if (!empty($job["salary"])): ?>

<span>

<i class="fa-solid fa-indian-rupee-sign"></i>

<?php
echo htmlspecialchars(
    $job["salary"]
);
?>

</span>

<?php endif; ?>


</div>


</div>


<a
    href="job_details.php?id=<?php echo (int)$job["id"]; ?>"
    class="job-button"
>

View Job

</a>


</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<div class="empty-state">

<i class="fa-solid fa-briefcase"></i>

<h4>

No opportunities available yet

</h4>

<p>

New placement opportunities will appear here.

</p>

</div>


<?php endif; ?>


</div>


</div>



<!-- RIGHT -->

<div>


<!-- PROFILE -->

<div class="section-header">

<div>

<h3>Your Profile</h3>

<span>

Keep your information updated

</span>

</div>


<a
    href="profile.php"
    class="view-all"
>

Edit

</a>

</div>


<div class="glass-panel profile-panel">


<div class="profile-summary">


<div class="large-avatar">

<?php echo htmlspecialchars($initial); ?>

</div>


<div>

<h4>

<?php echo $studentName; ?>

</h4>

<p>

<?php
echo htmlspecialchars(
    $student["email"]
);
?>

</p>

</div>


</div>


<div class="profile-detail">

<span>Department</span>

<strong>

<?php
echo htmlspecialchars(
    $student["department"]
    ?: "Not added"
);
?>

</strong>

</div>


<div class="profile-detail">

<span>Course</span>

<strong>

<?php
echo htmlspecialchars(
    $student["course"]
    ?: "Not added"
);
?>

</strong>

</div>


<div class="profile-detail">

<span>CGPA</span>

<strong>

<?php
echo htmlspecialchars(
    $student["cgpa"]
    ?: "Not added"
);
?>

</strong>

</div>


<div class="profile-detail">

<span>Graduation</span>

<strong>

<?php
echo htmlspecialchars(
    $student["graduation_year"]
    ?: "Not added"
);
?>

</strong>

</div>


</div>



<!-- RECENT ACTIVITY -->

<div class="section-header">

<div>

<h3>Recent Activity</h3>

<span>

Your latest applications

</span>

</div>


<a
    href="applications.php"
    class="view-all"
>

View All

</a>

</div>


<div class="glass-panel">


<?php if (
    $applicationsResult &&
    mysqli_num_rows($applicationsResult) > 0
): ?>


<div class="activity-list">


<?php while (
    $application =
    mysqli_fetch_assoc($applicationsResult)
): ?>


<?php

$statusClass = "status-applied";

if (
    strtolower($application["status"])
    === "shortlisted"
) {

    $statusClass =
        "status-shortlisted";

} elseif (
    strtolower($application["status"])
    === "selected"
) {

    $statusClass =
        "status-selected";

} elseif (
    strtolower($application["status"])
    === "rejected"
) {

    $statusClass =
        "status-rejected";
}

?>


<div class="activity-item">


<div class="activity-icon">

<i class="fa-solid fa-file-lines"></i>

</div>


<div class="activity-content">


<strong>

<?php
echo htmlspecialchars(
    $application["job_title"]
);
?>

</strong>


<span>

<?php
echo htmlspecialchars(
    $application["company_name"]
);
?>

</span>


<div
    class="status <?php echo $statusClass; ?>"
>

<?php
echo htmlspecialchars(
    $application["status"]
);
?>

</div>


</div>


</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<div class="empty-state">

<i class="fa-solid fa-clock"></i>

<h4>

No activity yet

</h4>

<p>

Start exploring jobs and applying.

</p>

</div>


<?php endif; ?>


</div>


</div>


</div>


</section>


</main>


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


if (
    mobileMenu &&
    sidebar &&
    sidebarOverlay
) {

    mobileMenu.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle(
                "show"
            );

            sidebarOverlay.classList.toggle(
                "show"
            );

        }
    );


    sidebarOverlay.addEventListener(
        "click",
        function () {

            sidebar.classList.remove(
                "show"
            );

            sidebarOverlay.classList.remove(
                "show"
            );

        }
    );

}


</script>


</body>

</html>