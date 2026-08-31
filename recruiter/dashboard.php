<?php

require_once "../includes/auth.php";
requireRole("recruiter");

require_once "../config/database.php";


/* =========================================
   RECRUITER / COMPANY INFORMATION
========================================= */

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        c.id,
        c.company_name,
        c.industry,
        c.website,
        c.location,
        u.full_name,
        u.email
     FROM companies c
     INNER JOIN users u
        ON c.user_id = u.id
     WHERE c.user_id = ?"
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


$company_id = (int)$company["id"];


/* =========================================
   TOTAL JOBS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs
     WHERE company_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$jobsData = mysqli_fetch_assoc($result);

$totalJobs = (int)$jobsData["total"];


/* =========================================
   ACTIVE JOBS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs
     WHERE company_id = ?
     AND status = 'Open'
     AND (
        application_deadline IS NULL
        OR application_deadline >= CURDATE()
     )"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$activeJobsData = mysqli_fetch_assoc($result);

$activeJobs = (int)$activeJobsData["total"];


/* =========================================
   TOTAL APPLICATIONS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     WHERE j.company_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$applicationsData = mysqli_fetch_assoc($result);

$totalApplications = (int)$applicationsData["total"];


/* =========================================
   SHORTLISTED CANDIDATES
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     WHERE j.company_id = ?
     AND a.status = 'Shortlisted'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$shortlistedData = mysqli_fetch_assoc($result);

$totalShortlisted = (int)$shortlistedData["total"];


/* =========================================
   SELECTED CANDIDATES
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     WHERE j.company_id = ?
     AND a.status = 'Selected'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$selectedData = mysqli_fetch_assoc($result);

$totalSelected = (int)$selectedData["total"];


/* =========================================
   LATEST JOBS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        j.id,
        j.job_title,
        j.location,
        j.salary,
        j.job_type,
        j.status,
        j.application_deadline,
        j.created_at,
        COUNT(a.id) AS application_count
     FROM jobs j
     LEFT JOIN applications a
        ON j.id = a.job_id
     WHERE j.company_id = ?
     GROUP BY
        j.id,
        j.job_title,
        j.location,
        j.salary,
        j.job_type,
        j.status,
        j.application_deadline,
        j.created_at
     ORDER BY j.created_at DESC
     LIMIT 5"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$jobsResult = mysqli_stmt_get_result($stmt);


/* =========================================
   RECENT APPLICATIONS
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        a.id,
        a.status,
        a.applied_at,
        j.id AS job_id,
        j.job_title,
        s.id AS student_id,
        s.department,
        s.course,
        s.cgpa,
        u.full_name,
        u.email
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     INNER JOIN students s
        ON a.student_id = s.id
     INNER JOIN users u
        ON s.user_id = u.id
     WHERE j.company_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 5"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$applicationsResult = mysqli_stmt_get_result($stmt);


/* =========================================
   RECRUITER NAME
========================================= */

$recruiterName = htmlspecialchars(
    $company["full_name"]
);

$firstName = explode(
    " ",
    trim($company["full_name"])
)[0];

$firstName = htmlspecialchars($firstName);


$initial = strtoupper(
    substr(
        trim($company["full_name"]),
        0,
        1
    )
);


/* =========================================
   COMPANY NAME
========================================= */

$companyName = htmlspecialchars(
    $company["company_name"]
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


/* =========================================
   GRID BACKGROUND
========================================= */

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


/* =========================================
   TOP PROFILE
========================================= */

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

    max-width: 1650px;

    margin: auto;

    padding:
        35px
        38px
        55px;
}


/* =========================================
   HERO
========================================= */

.hero {

    position: relative;

    overflow: hidden;

    padding:
        34px
        38px;

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


.hero::before {

    content: "";

    position: absolute;

    width: 300px;
    height: 300px;

    right: -100px;
    top: -150px;

    border-radius: 50%;

    background:
        rgba(96,165,250,.18);

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
        7px
        12px;

    margin-bottom: 17px;

    border-radius: 30px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

    color: #cbd5e1;

    font-size: 10px;
}


.hero-badge-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:
        0 0 12px #22c55e;
}


.hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: clamp(
        27px,
        3vw,
        38px
    );

    line-height: 1.15;

    letter-spacing: -1px;

    margin-bottom: 12px;
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

    max-width: 620px;

    color: var(--muted);

    font-size: 12px;

    line-height: 1.8;
}


.hero-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 12px;

    margin-top: 25px;
}


.btn-primary-custom {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding:
        11px
        18px;

    border-radius: 11px;

    text-decoration: none;

    color: white;

    font-size: 11px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );

    box-shadow:

        0 12px 28px
        rgba(91,124,255,.22);

    transition: .25s;
}


.btn-primary-custom:hover {

    color: white;

    transform:
        translateY(-2px);

    box-shadow:

        0 18px 35px
        rgba(91,124,255,.32);
}


.btn-secondary-custom {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding:
        11px
        18px;

    border-radius: 11px;

    text-decoration: none;

    color: #cbd5e1;

    font-size: 11px;

    font-weight: 600;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);

    transition: .25s;
}


.btn-secondary-custom:hover {

    color: white;

    background:
        rgba(255,255,255,.08);

    transform:
        translateY(-2px);
}


.hero-icon {

    position: absolute;

    right: 50px;

    bottom: -10px;

    font-size: 135px;

    color: white;

    opacity: .045;

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

    gap: 15px;

    margin:
        35px
        0
        17px;
}


.section-title h3 {

    font-family:
        "Outfit",
        sans-serif;

    font-size: 17px;

    margin: 0;

    font-weight: 700;
}


.section-title span {

    color: var(--muted);

    font-size: 10px;
}


.view-all {

    color: #93c5fd;

    text-decoration: none;

    font-size: 10px;

    font-weight: 600;

    display: flex;

    align-items: center;

    gap: 6px;

    transition: .2s;
}


.view-all:hover {

    color: white;

    transform:
        translateX(2px);
}


/* =========================================
   FIVE STAT CARDS
========================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(
            5,
            minmax(0,1fr)
        );

    gap: 17px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    padding: 20px;

    min-width: 0;

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

    margin-bottom: 20px;
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
        rgba(34,211,238,.10);

    color: #67e8f9;
}


.stat-card:nth-child(3) .stat-icon {

    background:
        rgba(139,92,246,.13);

    color: #c4b5fd;
}


.stat-card:nth-child(4) .stat-icon {

    background:
        rgba(245,158,11,.12);

    color: #fcd34d;
}


.stat-card:nth-child(5) .stat-icon {

    background:
        rgba(34,197,94,.10);

    color: #86efac;
}


.stat-arrow {

    color: #475569;

    font-size: 12px;
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

    white-space: nowrap;
}


/* =========================================
   DASHBOARD GRID
========================================= */

.dashboard-grid {

    display: grid;

    grid-template-columns:
        minmax(0,1.45fr)
        minmax(330px,.9fr);

    gap: 22px;

    align-items: start;
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

    align-items: center;

    gap: 15px;

    padding:
        16px;

    border-radius: 15px;

    transition: .25s;
}


.job-card:hover {

    background:
        rgba(255,255,255,.045);
}


.job-icon {

    width: 47px;
    height: 47px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.20),
            rgba(124,58,237,.15)
        );

    color: #a5b4fc;

    font-size: 17px;
}


.job-info h4 {

    font-size: 12px;

    font-weight: 700;

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

    color: var(--muted-dark);

    font-size: 9px;
}


.job-meta span {

    display: flex;

    align-items: center;

    gap: 4px;
}


.job-meta i {

    color: #60a5fa;
}


.job-button {

    padding:
        9px
        13px;

    border-radius: 9px;

    text-decoration: none;

    font-size: 10px;

    font-weight: 600;

    color: #cbd5e1;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);

    transition: .25s;
}


.job-button:hover {

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
   RECRUITER PROFILE PANEL
========================================= */

.company-panel {

    padding: 22px;
}


.company-summary {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 20px;
}


.company-avatar {

    width: 52px;
    height: 52px;

    border-radius: 16px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--primary-light)
        );
}


.company-summary h4 {

    font-size: 12px;

    margin-bottom: 4px;
}


.company-summary p {

    margin: 0;

    font-size: 9px;

    color: var(--muted);
}


.company-detail {

    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding:
        11px
        0;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}


.company-detail:last-child {

    border-bottom: none;
}


.company-detail span {

    color: var(--muted-dark);

    font-size: 9px;
}


.company-detail strong {

    color: #cbd5e1;

    font-size: 9px;

    font-weight: 600;

    text-align: right;

    max-width: 60%;
}


/* =========================================
   ACTIVITY
========================================= */

.activity-list {

    padding:
        7px
        12px
        12px;
}


.activity-item {

    display: flex;

    gap: 11px;

    padding:
        13px
        5px;

    border-bottom:
        1px solid
        rgba(255,255,255,.055);
}


.activity-item:last-child {

    border-bottom: none;
}


.activity-icon {

    min-width: 38px;

    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background:
        rgba(91,124,255,.12);

    color: #8ab4ff;

    font-size: 12px;
}


.activity-content {

    flex: 1;

    min-width: 0;
}


.activity-content strong {

    display: block;

    color: #dbeafe;

    font-size: 10px;

    margin-bottom: 4px;
}


.activity-content span {

    display: block;

    color: var(--muted);

    font-size: 9px;

    line-height: 1.5;
}


.application-job {

    margin-top: 4px;

    color: var(--muted-dark) !important;
}


/* =========================================
   STATUS
========================================= */

.status {

    display: inline-flex;

    align-items: center;

    padding:
        4px
        8px;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 600;

    margin-top: 6px;
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

    text-align: center;

    padding:
        55px
        20px;

    color: var(--muted);
}


.empty-state i {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    width: 55px;
    height: 55px;

    margin-bottom: 14px;

    border-radius: 17px;

    background:
        rgba(91,124,255,.10);

    color: #8ab4ff;

    font-size: 20px;
}


.empty-state h4 {

    color: #e2e8f0;

    font-size: 12px;

    margin-bottom: 7px;
}


.empty-state p {

    font-size: 10px;

    margin: 0;
}




/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1450px) {

    .stats-grid {

        grid-template-columns:
            repeat(5, minmax(150px,1fr));

        overflow-x: auto;

        padding-bottom: 5px;
    }

    .stat-card {

        min-width: 150px;
    }

}


@media (max-width: 1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(3,1fr);

        overflow: visible;
    }

    .stat-card {

        min-width: 0;
    }

}


@media (max-width: 1000px) {

    .dashboard-grid {

        grid-template-columns:
            1fr;
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


    .welcome {

        flex: 1;
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


    .hero {

        padding:
            27px
            23px;
    }


    .hero h1 {

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


    .stat-label {

        white-space: normal;
    }


    .job-card {

        grid-template-columns:
            auto
            1fr;
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


    .btn-primary-custom,
    .btn-secondary-custom {

        width: 100%;
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
    aria-label="Open menu"
>

<i class="fa-solid fa-bars"></i>

</button>


<div class="welcome">

<small>

<?php echo $greeting; ?>, Recruiter

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

<div class="hero">


<div class="hero-content">


<div class="hero-badge">

<span class="hero-badge-dot"></span>

Your recruitment portal is ready

</div>


<h1>

Build your team with

<br>

<span>

the right talent.

</span>

</h1>


<p>

Manage job opportunities, review candidate applications,
shortlist promising talent, and build a stronger team through
your Smart Placement Portal.

</p>


<div class="hero-actions">


<a
    href="post_job.php"
    class="btn-primary-custom"
>

<i class="fa-solid fa-plus"></i>

Post a Job

</a>


<a
    href="applications.php"
    class="btn-secondary-custom"
>

<i class="fa-solid fa-users"></i>

View Applications

</a>


</div>


</div>


<i
    class="fa-solid fa-building hero-icon"
></i>


</div>



<!-- =========================================
     STATISTICS
========================================= -->

<div class="section-header">

<div class="section-title">

<div>

<h3>

Recruitment Overview

</h3>

<span>

A quick overview of your hiring activity

</span>

</div>

</div>

</div>



<div class="stats-grid">


<!-- TOTAL JOBS -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-briefcase"></i>

</div>

<i
    class="fa-solid fa-arrow-up-right-from-square stat-arrow"
></i>

</div>


<div class="stat-number">

<?php echo $totalJobs; ?>

</div>


<div class="stat-label">

Total Jobs

</div>

</div>



<!-- ACTIVE JOBS -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-circle-check"></i>

</div>

<i
    class="fa-solid fa-arrow-up-right-from-square stat-arrow"
></i>

</div>


<div class="stat-number">

<?php echo $activeJobs; ?>

</div>


<div class="stat-label">

Active Jobs

</div>

</div>



<!-- APPLICATIONS -->

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon">

<i class="fa-solid fa-file-lines"></i>

</div>

<i
    class="fa-solid fa-arrow-up-right-from-square stat-arrow"
></i>

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

<i
    class="fa-solid fa-arrow-up-right-from-square stat-arrow"
></i>

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

<i class="fa-solid fa-user-check"></i>

</div>

<i
    class="fa-solid fa-arrow-up-right-from-square stat-arrow"
></i>

</div>


<div class="stat-number">

<?php echo $totalSelected; ?>

</div>


<div class="stat-label">

Selected Candidates

</div>

</div>


</div>



<!-- =========================================
     DASHBOARD GRID
========================================= -->

<div class="dashboard-grid">


<!-- =========================================
     LEFT COLUMN
========================================= -->

<div>


<div class="section-header">

<div class="section-title">

<div>

<h3>

Your Job Postings

</h3>

<span>

Manage your recently posted opportunities

</span>

</div>

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


<?php if ($jobsResult && mysqli_num_rows($jobsResult) > 0): ?>


<div class="job-list">


<?php while ($job = mysqli_fetch_assoc($jobsResult)): ?>


<div class="job-card">


<div class="job-icon">

<i class="fa-solid fa-briefcase"></i>

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
    $job["job_type"]
    ?: "Job type not specified"
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

<i class="fa-solid fa-users"></i>

<?php
echo (int)$job["application_count"];
?>

Applications

</span>


<span>

<i class="fa-solid fa-circle"></i>

<?php
echo htmlspecialchars(
    $job["status"]
);
?>

</span>


</div>


</div>


<a
    href="job_details.php?id=<?php echo (int)$job["id"]; ?>"
    class="job-button"
>

Manage

</a>


</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<div class="empty-state">

<i class="fa-solid fa-briefcase"></i>


<h4>

No job postings yet

</h4>


<p>

Start building your team by posting your first job.

</p>


</div>


<?php endif; ?>


</div>


</div>



<!-- =========================================
     RIGHT COLUMN
========================================= -->

<div>


<!-- =========================================
     COMPANY PROFILE
========================================= -->

<div class="section-header">

<div class="section-title">

<div>

<h3>

Company Profile

</h3>

<span>

Your organization information

</span>

</div>

</div>


<a
    href="profile.php"
    class="view-all"
>

Edit

<i class="fa-solid fa-pen"></i>

</a>


</div>



<div class="glass-panel company-panel">


<div class="company-summary">


<div class="company-avatar">

<i class="fa-solid fa-building"></i>

</div>


<div>

<h4>

<?php echo $companyName; ?>

</h4>


<p>

<?php echo $recruiterName; ?>

</p>


</div>


</div>



<div class="company-detail">

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



<div class="company-detail">

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



<div class="company-detail">

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



<div class="company-detail">

<span>

Website

</span>


<strong>

<?php
echo htmlspecialchars(
    $company["website"]
    ?: "Not added"
);
?>

</strong>

</div>


</div>



<!-- =========================================
     RECENT APPLICATIONS
========================================= -->

<div class="section-header">

<div class="section-title">

<div>

<h3>

Recent Candidates

</h3>

<span>

Latest applications received

</span>

</div>

</div>


<a
    href="applications.php"
    class="view-all"
>

View All

<i class="fa-solid fa-arrow-right"></i>

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

<i class="fa-solid fa-user"></i>

</div>


<div class="activity-content">


<strong>

<?php
echo htmlspecialchars(
    $application["full_name"]
);
?>

</strong>


<span>

<?php
echo htmlspecialchars(
    $application["department"]
    ?: "Department not specified"
);
?>

</span>


<span class="application-job">

Applied for:

<?php
echo htmlspecialchars(
    $application["job_title"]
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


<i class="fa-solid fa-users"></i>


<h4>

No applications yet

</h4>


<p>

Candidate applications will appear here.

</p>


</div>


<?php endif; ?>


</div>


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

    if (!sidebar || !sidebarOverlay) return;

    sidebar.classList.add(
        "show"
    );

    sidebarOverlay.classList.add(
        "show"
    );
}


function closeSidebar() {

    if (!sidebar || !sidebarOverlay) return;

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