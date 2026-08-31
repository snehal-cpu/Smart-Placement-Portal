<?php

require_once "../includes/auth.php";
requireRole("student");

require_once "../config/database.php";


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   STUDENT DETAILS
========================================================= */

$user_id = $_SESSION["user_id"];

$student = null;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        s.id,
        s.department,
        s.year,
        s.cgpa,
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

mysqli_stmt_close($stmt);


/* =========================================================
   SEARCH & FILTER
========================================================= */

$search = trim($_GET["search"] ?? "");
$job_type = trim($_GET["job_type"] ?? "");
$location = trim($_GET["location"] ?? "");
$department = trim($_GET["department"] ?? "");


/* =========================================================
   FETCH JOBS
========================================================= */

$sql = "
    SELECT
        j.id,
        j.job_title,
        j.description,
        j.job_type,
        j.location,
        j.salary,
        j.min_cgpa,
        j.eligible_department,
        j.eligible_year,
        j.application_deadline,
        j.vacancies,
        j.status,
        j.created_at,

        c.company_name,
        c.industry,
        c.logo

    FROM jobs j

    INNER JOIN companies c
        ON j.company_id = c.id

    WHERE j.status = 'Open'
";


$params = [];
$types = "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            j.job_title LIKE ?
            OR c.company_name LIKE ?
            OR j.location LIKE ?
            OR j.description LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";
}


/* =========================================================
   JOB TYPE
========================================================= */

if ($job_type !== "") {

    $sql .= " AND j.job_type = ? ";

    $params[] = $job_type;
    $types .= "s";
}


/* =========================================================
   LOCATION
========================================================= */

if ($location !== "") {

    $sql .= " AND j.location LIKE ? ";

    $params[] = "%" . $location . "%";
    $types .= "s";
}


/* =========================================================
   DEPARTMENT
========================================================= */

if ($department !== "") {

    $sql .= "
        AND (
            j.eligible_department = ?
            OR j.eligible_department = 'All Departments'
            OR j.eligible_department = ''
            OR j.eligible_department IS NULL
        )
    ";

    $params[] = $department;
    $types .= "s";
}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY j.created_at DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$jobs = [];

while ($row = mysqli_fetch_assoc($result)) {

    $jobs[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   APPLICATIONS ALREADY MADE
========================================================= */

$applied_jobs = [];

if ($student) {

    $student_id = (int)$student["id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT job_id
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

    while ($row = mysqli_fetch_assoc($result)) {

        $applied_jobs[] = (int)$row["job_id"];
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   JOB TYPES
========================================================= */

$job_types = [
    "Full Time",
    "Part Time",
    "Internship"
];


/* =========================================================
   STUDENT NAME
========================================================= */

$student_name =
    $student["full_name"] ?? "Student";

$initial =
    strtoupper(
        substr(
            trim($student_name),
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

<title>Browse Jobs | Smart Placement Portal</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Font Awesome -->

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>


<!-- Google Fonts -->

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --bg: #08111f;
    --bg-soft: #0d1728;

    --card: rgba(15, 27, 47, 0.78);
    --card-hover: rgba(20, 35, 59, 0.95);

    --border: rgba(255, 255, 255, 0.08);

    --text: #f8fafc;
    --muted: #94a3b8;

    --blue: #3b82f6;
    --purple: #8b5cf6;

    --green: #22c55e;
}


/* =========================================================
   GLOBAL
========================================================= */

* {

    box-sizing: border-box;
}


html {

    scroll-behavior: smooth;
}


body {

    margin: 0;

    min-height: 100vh;

    font-family: "DM Sans", sans-serif;

    color: var(--text);

    background:
        radial-gradient(
            circle at top left,
            rgba(59, 130, 246, 0.13),
            transparent 32%
        ),
        radial-gradient(
            circle at 85% 20%,
            rgba(139, 92, 246, 0.13),
            transparent 30%
        ),
        var(--bg);
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: 260px;

    height: 100vh;

    padding: 26px 16px;

    background:
        linear-gradient(
            180deg,
            rgba(10, 19, 34, 0.98),
            rgba(7, 14, 26, 0.98)
        );

    border-right:
        1px solid var(--border);

    z-index: 1000;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 8px 12px 34px;
}


.brand-icon {

    width: 44px;
    height: 44px;

    border-radius: 14px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 18px;

    color: white;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    box-shadow:
        0 10px 25px
        rgba(59, 130, 246, 0.25);
}


.brand-text {

    display: flex;

    flex-direction: column;
}


.brand-title {

    font-family: "Outfit", sans-serif;

    font-size: 17px;

    font-weight: 700;
}


.brand-subtitle {

    margin-top: 2px;

    font-size: 10px;

    color: var(--muted);

    letter-spacing: .5px;
}


/* =========================================================
   NAVIGATION
========================================================= */

.nav-section {

    margin-bottom: 20px;
}


.nav-label {

    padding: 0 14px;

    margin-bottom: 10px;

    color: #64748b;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1px;

    text-transform: uppercase;
}


.nav-link {

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 13px 14px;

    margin-bottom: 5px;

    border-radius: 12px;

    color: var(--muted);

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    transition: .25s ease;
}


.nav-link i {

    width: 20px;

    text-align: center;

    font-size: 15px;
}


.nav-link:hover {

    color: white;

    background:
        rgba(255, 255, 255, 0.05);
}


.nav-link.active {

    color: white;

    background:
        linear-gradient(
            135deg,
            rgba(59, 130, 246, 0.9),
            rgba(139, 92, 246, 0.85)
        );

    box-shadow:
        0 10px 25px
        rgba(59, 130, 246, 0.18);
}


.logout-link {

    position: absolute;

    bottom: 24px;

    left: 16px;
    right: 16px;

    color: #fda4af;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 260px;

    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 78px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 38px;

    background:
        rgba(8, 17, 31, 0.72);

    backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid var(--border);

    position: sticky;

    top: 0;

    z-index: 100;
}


.page-title {

    font-family: "Outfit", sans-serif;

    font-size: 20px;

    font-weight: 700;
}


.page-subtitle {

    margin-top: 3px;

    color: var(--muted);

    font-size: 12px;
}


.profile {

    display: flex;

    align-items: center;

    gap: 11px;
}


.profile-info {

    text-align: right;
}


.profile-name {

    font-size: 13px;

    font-weight: 700;
}


.profile-role {

    margin-top: 2px;

    color: var(--muted);

    font-size: 10px;
}


.avatar {

    width: 42px;
    height: 42px;

    border-radius: 14px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-family: "Outfit", sans-serif;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );

    box-shadow:
        0 8px 22px
        rgba(59, 130, 246, 0.25);
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width: 1450px;

    margin: auto;

    padding: 34px 38px 50px;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    position: relative;

    overflow: hidden;

    padding: 34px;

    margin-bottom: 24px;

    border-radius: 24px;

    border:
        1px solid rgba(255, 255, 255, 0.1);

    background:
        linear-gradient(
            135deg,
            rgba(30, 64, 175, 0.9),
            rgba(76, 29, 149, 0.88)
        );

    box-shadow:
        0 20px 50px
        rgba(0, 0, 0, 0.22);
}


.hero::before {

    content: "";

    position: absolute;

    width: 330px;
    height: 330px;

    border-radius: 50%;

    right: -100px;
    top: -160px;

    background:
        rgba(255, 255, 255, 0.08);
}


.hero::after {

    content: "";

    position: absolute;

    width: 170px;
    height: 170px;

    border-radius: 50%;

    right: 170px;
    bottom: -110px;

    background:
        rgba(255, 255, 255, 0.05);
}


.hero-content {

    position: relative;

    z-index: 2;

    max-width: 650px;
}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 12px;

    margin-bottom: 14px;

    border-radius: 30px;

    background:
        rgba(255, 255, 255, 0.12);

    border:
        1px solid rgba(255, 255, 255, 0.15);

    font-size: 11px;

    font-weight: 600;
}


.hero h1 {

    margin: 0 0 10px;

    font-family: "Outfit", sans-serif;

    font-size: 30px;

    font-weight: 800;
}


.hero p {

    margin: 0;

    max-width: 570px;

    color:
        rgba(255, 255, 255, 0.75);

    font-size: 13px;

    line-height: 1.7;
}


/* =========================================================
   FILTER CARD
========================================================= */

.filter-card {

    padding: 20px;

    margin-bottom: 28px;

    border-radius: 20px;

    background: var(--card);

    border:
        1px solid var(--border);

    backdrop-filter:
        blur(16px);
}


.filter-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}


.filter-heading h3 {

    margin: 0;

    font-family: "Outfit", sans-serif;

    font-size: 15px;

    font-weight: 700;
}


.filter-heading p {

    margin: 3px 0 0;

    color: var(--muted);

    font-size: 11px;
}


.filter-form {

    display: grid;

    grid-template-columns:
        1.7fr
        1fr
        1fr
        1fr
        auto;

    gap: 12px;
}


.input-group-custom {

    position: relative;
}


.input-group-custom i {

    position: absolute;

    top: 50%;
    left: 14px;

    transform:
        translateY(-50%);

    color: #64748b;

    font-size: 12px;

    z-index: 2;
}


.input-group-custom input,
.input-group-custom select {

    width: 100%;

    height: 46px;

    border-radius: 12px;

    border:
        1px solid rgba(255, 255, 255, 0.08);

    outline: none;

    color: white;

    font-family: "DM Sans", sans-serif;

    font-size: 12px;

    background:
        rgba(255, 255, 255, 0.045);

    transition: .2s ease;
}


.input-group-custom input {

    padding:
        0 14px 0 40px;
}


.input-group-custom select {

    padding:
        0 12px;
}


.input-group-custom option {

    color: white;

    background: #111c30;
}


.input-group-custom input::placeholder {

    color: #64748b;
}


.input-group-custom input:focus,
.input-group-custom select:focus {

    border-color:
        rgba(59, 130, 246, 0.8);

    box-shadow:
        0 0 0 4px
        rgba(59, 130, 246, 0.08);
}


.search-btn {

    height: 46px;

    padding: 0 20px;

    border: none;

    border-radius: 12px;

    color: white;

    font-family: "DM Sans", sans-serif;

    font-size: 12px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    transition: .25s ease;
}


.search-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(59, 130, 246, 0.25);
}


.clear-link {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-top: 14px;

    color: #94a3b8;

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;
}


.clear-link:hover {

    color: white;
}


/* =========================================================
   RESULT HEADER
========================================================= */

.result-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 18px;
}


.result-title {

    font-family: "Outfit", sans-serif;

    font-size: 20px;

    font-weight: 700;
}


.result-count {

    padding: 7px 12px;

    border-radius: 30px;

    color: #a5b4fc;

    font-size: 11px;

    font-weight: 600;

    background:
        rgba(99, 102, 241, 0.12);

    border:
        1px solid rgba(99, 102, 241, 0.16);
}


/* =========================================================
   JOB GRID
========================================================= */

.job-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 20px;
}


/* =========================================================
   JOB CARD
========================================================= */

.job-card {

    position: relative;

    padding: 22px;

    border-radius: 20px;

    background: var(--card);

    border:
        1px solid var(--border);

    backdrop-filter:
        blur(16px);

    transition:
        transform .25s ease,
        border-color .25s ease,
        background .25s ease;
}


.job-card:hover {

    transform:
        translateY(-5px);

    background:
        var(--card-hover);

    border-color:
        rgba(96, 165, 250, 0.25);
}


.job-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 18px;
}


.company-logo {

    width: 52px;
    height: 52px;

    flex-shrink: 0;

    overflow: hidden;

    border-radius: 16px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-family: "Outfit", sans-serif;

    font-size: 17px;

    font-weight: 700;

    color: #93c5fd;

    background:
        linear-gradient(
            135deg,
            rgba(59, 130, 246, 0.18),
            rgba(139, 92, 246, 0.18)
        );

    border:
        1px solid rgba(255, 255, 255, 0.08);
}


.company-logo img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}


.job-type {

    padding: 7px 11px;

    border-radius: 30px;

    color: #93c5fd;

    font-size: 10px;

    font-weight: 700;

    background:
        rgba(59, 130, 246, 0.1);

    border:
        1px solid rgba(59, 130, 246, 0.15);
}


.job-title {

    font-family: "Outfit", sans-serif;

    font-size: 18px;

    font-weight: 700;

    line-height: 1.35;

    margin-bottom: 6px;
}


.company-name {

    color: var(--muted);

    font-size: 12px;

    font-weight: 500;

    margin-bottom: 20px;
}


/* =========================================================
   JOB DETAILS
========================================================= */

.job-details {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 15px;

    padding: 16px 0;

    border-top:
        1px solid var(--border);

    border-bottom:
        1px solid var(--border);
}


.detail {

    display: flex;

    align-items: flex-start;

    gap: 9px;

    color: var(--muted);

    font-size: 11px;
}


.detail i {

    margin-top: 3px;

    width: 16px;

    color: #60a5fa;
}


.detail strong {

    display: block;

    margin-bottom: 3px;

    color: #e2e8f0;

    font-size: 10px;

    font-weight: 700;
}


/* =========================================================
   ELIGIBILITY
========================================================= */

.eligibility {

    margin: 16px 0;

    padding: 14px;

    border-radius: 14px;

    background:
        rgba(255, 255, 255, 0.035);

    border:
        1px solid rgba(255, 255, 255, 0.06);
}


.eligibility-title {

    margin-bottom: 7px;

    color: #64748b;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .8px;

    text-transform: uppercase;
}


.eligibility-value {

    color: #cbd5e1;

    font-size: 11px;

    line-height: 1.8;
}


.eligibility-value strong {

    color: white;

    font-weight: 600;
}


/* =========================================================
   CARD FOOTER
========================================================= */

.card-footer-custom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding-top: 4px;
}


.deadline {

    color: #64748b;

    font-size: 10px;
}


.deadline strong {

    display: block;

    margin-top: 4px;

    color: #e2e8f0;

    font-size: 11px;
}


.view-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    min-width: 110px;

    padding: 10px 14px;

    border-radius: 11px;

    color: white;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            var(--blue),
            var(--purple)
        );

    transition: .2s ease;
}


.view-btn:hover {

    color: white;

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 22px
        rgba(59, 130, 246, 0.22);
}


.applied-btn {

    color: #86efac;

    cursor: default;

    background:
        rgba(34, 197, 94, 0.1);

    border:
        1px solid rgba(34, 197, 94, 0.18);
}


.applied-btn:hover {

    color: #86efac;

    transform: none;

    box-shadow: none;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    padding: 80px 20px;

    text-align: center;

    border-radius: 22px;

    background: var(--card);

    border:
        1px solid var(--border);
}


.empty-icon {

    width: 70px;
    height: 70px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin:
        0 auto 18px;

    border-radius: 22px;

    color: #60a5fa;

    font-size: 25px;

    background:
        rgba(59, 130, 246, 0.1);
}


.empty-title {

    margin-bottom: 6px;

    font-family: "Outfit", sans-serif;

    font-size: 18px;

    font-weight: 700;
}


.empty-text {

    color: var(--muted);

    font-size: 12px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .job-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .filter-form {

        grid-template-columns:
            repeat(3, 1fr);
    }

}


@media (max-width: 900px) {

    .sidebar {

        width: 82px;

        padding:
            22px 10px;
    }


    .brand {

        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }


    .brand-text,
    .nav-link span,
    .nav-label {

        display: none;
    }


    .nav-link {

        justify-content: center;

        padding: 14px;
    }


    .main {

        margin-left: 82px;
    }


    .logout-link {

        left: 10px;
        right: 10px;
    }

}


@media (max-width: 700px) {

    .topbar {

        padding:
            0 18px;
    }


    .content {

        padding:
            24px 18px 40px;
    }


    .profile-info {

        display: none;
    }


    .filter-form {

        grid-template-columns:
            1fr 1fr;
    }


    .hero h1 {

        font-size: 25px;
    }

}


@media (max-width: 550px) {

    .sidebar {

        width: 64px;
    }


    .main {

        margin-left: 64px;
    }


    .content {

        padding:
            18px 12px 35px;
    }


    .topbar {

        padding:
            0 14px;
    }


    .page-title {

        font-size: 17px;
    }


    .page-subtitle {

        display: none;
    }


    .hero {

        padding: 24px 20px;

        border-radius: 20px;
    }


    .hero h1 {

        font-size: 22px;
    }


    .filter-form {

        grid-template-columns:
            1fr;
    }


    .job-grid {

        grid-template-columns:
            1fr;
    }


    .result-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 10px;
    }


    .card-footer-custom {

        align-items: flex-start;

        flex-direction: column;
    }


    .view-btn {

        width: 100%;
    }

}
/* ========================================
   FIX STUDENT SIDEBAR BOOTSTRAP CONFLICT
======================================== */

.sidebar a.brand {
    text-decoration: none !important;
    color: inherit !important;
}

.sidebar a.brand:hover,
.sidebar a.brand:focus,
.sidebar a.brand:active,
.sidebar a.brand:visited {
    text-decoration: none !important;
    color: inherit !important;
}

.sidebar .brand-text,
.sidebar .brand-title,
.sidebar .brand-subtitle {
    text-decoration: none !important;
}

.sidebar .brand-title {
    color: #ffffff !important;
}

.sidebar .brand-subtitle {
    color: #94a3b8 !important;
}

</style>

</head>


<body>
<?php require_once "../includes/student_sidebar.php"; ?>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <div class="page-title">
                Browse Jobs
            </div>

            <div class="page-subtitle">
                Find your next placement opportunity
            </div>

        </div>


        <div class="profile">


            <div class="profile-info">

                <div class="profile-name">

                    <?php echo e($student_name); ?>

                </div>


                <div class="profile-role">
                    Student
                </div>

            </div>


            <div class="avatar">

                <?php echo e($initial); ?>

            </div>


        </div>


    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- HERO -->

        <div class="hero">


            <div class="hero-content">


                <div class="hero-badge">

                    <i class="fa-solid fa-sparkles"></i>

                    Placement Opportunities

                </div>


                <h1>
                    Find Your Next Opportunity
                </h1>


                <p>

                    Explore jobs and internships from
                    companies hiring through your college
                    placement portal.

                </p>


            </div>


        </div>


        <!-- FILTER -->

        <div class="filter-card">


            <div class="filter-heading">

                <div>

                    <h3>
                        Search Opportunities
                    </h3>

                    <p>
                        Filter jobs based on your preferences.
                    </p>

                </div>

            </div>


            <form
                method="GET"
                class="filter-form"
            >


                <!-- SEARCH -->

                <div class="input-group-custom">

                    <i
                        class="fa-solid fa-magnifying-glass"
                    ></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search jobs or companies"
                        value="<?php echo e($search); ?>"
                    >

                </div>


                <!-- JOB TYPE -->

                <div class="input-group-custom">

                    <select name="job_type">

                        <option value="">
                            All Job Types
                        </option>


                        <?php foreach ($job_types as $type): ?>

                            <option
                                value="<?php echo e($type); ?>"
                                <?php
                                echo $job_type === $type
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php echo e($type); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- LOCATION -->

                <div class="input-group-custom">

                    <i
                        class="fa-solid fa-location-dot"
                    ></i>

                    <input
                        type="text"
                        name="location"
                        placeholder="Location"
                        value="<?php echo e($location); ?>"
                    >

                </div>


                <!-- DEPARTMENT -->

                <div class="input-group-custom">

                    <select name="department">

                        <option value="">
                            All Departments
                        </option>

                        <option
                            value="Computer"
                            <?php echo $department === "Computer" ? "selected" : ""; ?>
                        >
                            Computer
                        </option>

                        <option
                            value="IT"
                            <?php echo $department === "IT" ? "selected" : ""; ?>
                        >
                            IT
                        </option>

                        <option
                            value="ENTC"
                            <?php echo $department === "ENTC" ? "selected" : ""; ?>
                        >
                            ENTC
                        </option>

                        <option
                            value="Mechanical"
                            <?php echo $department === "Mechanical" ? "selected" : ""; ?>
                        >
                            Mechanical
                        </option>

                        <option
                            value="Civil"
                            <?php echo $department === "Civil" ? "selected" : ""; ?>
                        >
                            Civil
                        </option>

                        <option
                            value="Electrical"
                            <?php echo $department === "Electrical" ? "selected" : ""; ?>
                        >
                            Electrical
                        </option>

                    </select>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="search-btn"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Search

                </button>


            </form>


            <?php if (
                $search !== "" ||
                $job_type !== "" ||
                $location !== "" ||
                $department !== ""
            ): ?>


                <a
                    href="jobs.php"
                    class="clear-link"
                >

                    <i class="fa-solid fa-xmark"></i>

                    Clear all filters

                </a>


            <?php endif; ?>


        </div>


        <!-- RESULT HEADER -->

        <div class="result-header">


            <div class="result-title">

                Latest Opportunities

            </div>


            <div class="result-count">

                <?php echo count($jobs); ?>

                opportunity<?php
                echo count($jobs) !== 1
                    ? "ies"
                    : "";
                ?>

            </div>


        </div>


        <!-- JOB GRID -->

        <?php if (count($jobs) > 0): ?>


            <div class="job-grid">


                <?php foreach ($jobs as $job): ?>


                    <?php

                    $company_name =
                        $job["company_name"];

                    $company_initial =
                        strtoupper(
                            substr(
                                trim($company_name),
                                0,
                                1
                            )
                        );


                    $is_applied =
                        in_array(
                            (int)$job["id"],
                            $applied_jobs,
                            true
                        );


                    $deadline =
                        "Not specified";


                    if (
                        !empty(
                            $job[
                                "application_deadline"
                            ]
                        )
                    ) {

                        $deadline =
                            date(
                                "d M Y",
                                strtotime(
                                    $job[
                                        "application_deadline"
                                    ]
                                )
                            );
                    }

                    ?>


                    <div class="job-card">


                        <!-- JOB TOP -->

                        <div class="job-top">


                            <div class="company-logo">


                                <?php if (
                                    !empty(
                                        $job["logo"]
                                    )
                                ): ?>


                                    <img
                                        src="<?php
                                        echo e(
                                            $job["logo"]
                                        );
                                        ?>"
                                        alt="Company Logo"
                                    >


                                <?php else: ?>


                                    <?php
                                    echo e(
                                        $company_initial
                                    );
                                    ?>


                                <?php endif; ?>


                            </div>


                            <div class="job-type">

                                <?php
                                echo e(
                                    $job["job_type"]
                                );
                                ?>

                            </div>


                        </div>


                        <!-- TITLE -->

                        <div class="job-title">

                            <?php
                            echo e(
                                $job["job_title"]
                            );
                            ?>

                        </div>


                        <div class="company-name">

                            <?php
                            echo e(
                                $company_name
                            );
                            ?>


                            <?php if (
                                !empty(
                                    $job["industry"]
                                )
                            ): ?>

                                ·

                                <?php
                                echo e(
                                    $job["industry"]
                                );
                                ?>

                            <?php endif; ?>


                        </div>


                        <!-- DETAILS -->

                        <div class="job-details">


                            <div class="detail">

                                <i
                                    class="fa-solid fa-location-dot"
                                ></i>

                                <div>

                                    <strong>
                                        Location
                                    </strong>

                                    <?php

                                    echo !empty(
                                        $job["location"]
                                    )
                                        ? e(
                                            $job["location"]
                                        )
                                        : "Not specified";

                                    ?>

                                </div>

                            </div>


                            <div class="detail">

                                <i
                                    class="fa-solid fa-indian-rupee-sign"
                                ></i>

                                <div>

                                    <strong>
                                        Salary
                                    </strong>

                                    <?php

                                    echo !empty(
                                        $job["salary"]
                                    )
                                        ? e(
                                            $job["salary"]
                                        )
                                        : "Not specified";

                                    ?>

                                </div>

                            </div>


                            <div class="detail">

                                <i
                                    class="fa-solid fa-users"
                                ></i>

                                <div>

                                    <strong>
                                        Vacancies
                                    </strong>

                                    <?php
                                    echo e(
                                        $job["vacancies"]
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail">

                                <i
                                    class="fa-solid fa-star"
                                ></i>

                                <div>

                                    <strong>
                                        Min CGPA
                                    </strong>

                                    <?php

                                    echo number_format(
                                        (float)
                                        $job["min_cgpa"],
                                        2
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>


                        <!-- ELIGIBILITY -->

                        <div class="eligibility">


                            <div class="eligibility-title">

                                Eligibility

                            </div>


                            <div class="eligibility-value">


                                <strong>
                                    Department:
                                </strong>


                                <?php

                                echo !empty(
                                    $job[
                                        "eligible_department"
                                    ]
                                )
                                    ? e(
                                        $job[
                                            "eligible_department"
                                        ]
                                    )
                                    : "All Departments";

                                ?>


                                <br>


                                <strong>
                                    Year:
                                </strong>


                                <?php

                                echo !empty(
                                    $job[
                                        "eligible_year"
                                    ]
                                )
                                    ? e(
                                        $job[
                                            "eligible_year"
                                        ]
                                    )
                                    : "All Years";

                                ?>


                            </div>


                        </div>


                        <!-- FOOTER -->

                        <div class="card-footer-custom">


                            <div class="deadline">

                                Application Deadline


                                <strong>

                                    <?php
                                    echo e(
                                        $deadline
                                    );
                                    ?>

                                </strong>

                            </div>


                            <?php if ($is_applied): ?>


                                <span
                                    class="view-btn applied-btn"
                                >

                                    <i
                                        class="fa-solid fa-check"
                                    ></i>

                                    Applied

                                </span>


                            <?php else: ?>


                                <a
                                    href="view_job.php?id=<?php
                                    echo (int)$job["id"];
                                    ?>"
                                    class="view-btn"
                                >

                                    View Job

                                    <i
                                        class="fa-solid fa-arrow-right"
                                    ></i>

                                </a>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-state">


                <div class="empty-icon">

                    <i
                        class="fa-solid fa-briefcase"
                    ></i>

                </div>


                <div class="empty-title">

                    No Jobs Found

                </div>


                <div class="empty-text">

                    Try changing your search
                    or filter criteria.

                </div>


            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>