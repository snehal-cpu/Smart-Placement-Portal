<?php

require_once "../includes/auth.php";
requireRole("recruiter");

require_once "../config/database.php";


/* =========================================================
   HELPER FUNCTION
========================================================= */

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   GET RECRUITER COMPANY
========================================================= */

$user_id = (int)($_SESSION["user_id"] ?? 0);


$stmt = mysqli_prepare(
    $conn,
    "SELECT
        c.id,
        c.company_name,
        u.full_name
     FROM companies c
     INNER JOIN users u
        ON c.user_id = u.id
     WHERE c.user_id = ?
     LIMIT 1"
);


if (!$stmt) {

    die("Database error.");

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result(
    $stmt
);


$company = mysqli_fetch_assoc(
    $result
);


mysqli_stmt_close(
    $stmt
);


/* =========================================================
   SAFETY CHECK
========================================================= */

if (!$company) {

    header(
        "Location: profile.php?setup=required"
    );

    exit;
}


$company_id = (int)$company["id"];


/* =========================================================
   SEARCH AND FILTER
========================================================= */

$search = trim(
    $_GET["search"] ?? ""
);


$statusFilter = trim(
    $_GET["status"] ?? ""
);


/* =========================================================
   BUILD QUERY
========================================================= */

$sql = "
    SELECT
        a.id AS application_id,
        a.status,
        a.applied_at,

        j.id AS job_id,
        j.job_title,
        j.job_type,

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
";


$types = "i";

$params = [
    $company_id
];


if ($search !== "") {

    $sql .= "
        AND (
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR j.job_title LIKE ?
            OR s.department LIKE ?
            OR s.course LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";


    $types .= "sssss";


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;
}


/* =========================================================
   STATUS FILTER
========================================================= */

$allowedStatuses = [

    "Applied",
    "Shortlisted",
    "Selected",
    "Rejected"

];


if (
    $statusFilter !== ""
    &&
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND LOWER(a.status) = LOWER(?)
    ";


    $types .= "s";


    $params[] =
        $statusFilter;
}


/* =========================================================
   ORDER
========================================================= */

$sql .= "

    ORDER BY
        a.applied_at DESC

";


/* =========================================================
   PREPARE QUERY
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    die(
        "Unable to load applicants."
    );

}


/* =========================================================
   DYNAMIC BIND PARAMETERS
========================================================= */

mysqli_stmt_bind_param(
    $stmt,
    $types,
    ...$params
);


mysqli_stmt_execute(
    $stmt
);


$applicantsResult =
    mysqli_stmt_get_result(
        $stmt
);


/* =========================================================
   STATISTICS
========================================================= */

$totalApplicants = 0;
$appliedCount = 0;
$shortlistedCount = 0;
$selectedCount = 0;


/*
   Store results because
   we also need statistics.
*/

$applicants = [];


while (
    $row =
    mysqli_fetch_assoc(
        $applicantsResult
    )
) {

    $applicants[] =
        $row;


    $totalApplicants++;


    $currentStatus =
        strtolower(
            trim(
                $row["status"]
            )
        );


    if (
        $currentStatus ===
        "applied"
    ) {

        $appliedCount++;

    } elseif (

        $currentStatus ===
        "shortlisted"

    ) {

        $shortlistedCount++;

    } elseif (

        $currentStatus ===
        "selected"

    ) {

        $selectedCount++;
    }
}


mysqli_stmt_close(
    $stmt
);


/* =========================================================
   PAGE INFORMATION
========================================================= */

$companyName =
    e(
        $company["company_name"]
    );


$recruiterName =
    e(
        $company["full_name"]
    );


$initial = strtoupper(
    substr(
        trim(
            $company["full_name"]
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

<title>
    Applicants | Smart Placement Portal
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


/* =========================================================
   ROOT
========================================================= */

:root {

    --bg-dark:
        #030712;

    --bg-secondary:
        #07111f;

    --primary:
        #5b7cff;

    --purple:
        #7c3aed;

    --cyan:
        #22d3ee;

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

}


/* =========================================================
   GLOBAL
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing:
        border-box;
}


body {

    min-height:
        100vh;

    font-family:
        "DM Sans",
        sans-serif;

    color:
        var(--text);

    background:

        radial-gradient(
            circle at 15% 15%,
            rgba(91,124,255,.15),
            transparent 28%
        ),

        radial-gradient(
            circle at 85% 85%,
            rgba(124,58,237,.14),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #030712,
            #07111f
        );

    overflow-x:
        hidden;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:
        265px;

    min-height:
        100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

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
        rgba(3,7,18,.35);

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


.page-title small {

    display:
        block;

    color:
        var(--muted);

    font-size:
        10px;

    margin-bottom:
        5px;
}


.page-title h2 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        22px;

    font-weight:
        700;
}


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
}


.profile-text span {

    color:
        var(--muted);

    font-size:
        9px;
}


.avatar {

    width:
        43px;

    height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        14px;

    font-weight:
        700;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1700px;

    margin:
        auto;

    padding:
        35px 38px 55px;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    position:
        relative;

    overflow:
        hidden;

    padding:
        30px 34px;

    border:
        1px solid var(--border);

    border-radius:
        22px;

    margin-bottom:
        25px;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.18),
            rgba(124,58,237,.12)
        );
}


.hero::after {

    content:
        "";

    position:
        absolute;

    width:
        250px;

    height:
        250px;

    right:
        -100px;

    top:
        -100px;

    border-radius:
        50%;

    background:
        rgba(34,211,238,.12);

    filter:
        blur(45px);
}


.hero-content {

    position:
        relative;

    z-index:
        2;
}


.hero h1 {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        28px;

    margin-bottom:
        8px;
}


.hero p {

    color:
        var(--muted);

    font-size:
        11px;

    line-height:
        1.7;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats-grid {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        16px;

    margin-bottom:
        28px;
}


.stat-card {

    padding:
        20px;

    border:
        1px solid var(--border);

    border-radius:
        18px;

    background:
        var(--card);

    backdrop-filter:
        blur(15px);

    transition:
        .25s;
}


.stat-card:hover {

    transform:
        translateY(-4px);

    background:
        rgba(255,255,255,.065);
}


.stat-icon {

    width:
        42px;

    height:
        42px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        13px;

    margin-bottom:
        16px;

    background:
        rgba(91,124,255,.13);

    color:
        #8ab4ff;
}


.stat-number {

    font-family:
        "Outfit",
        sans-serif;

    font-size:
        28px;

    font-weight:
        700;
}


.stat-label {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        10px;
}


/* =========================================================
   FILTER PANEL
========================================================= */

.filter-panel {

    padding:
        18px;

    margin-bottom:
        22px;

    border:
        1px solid var(--border);

    border-radius:
        18px;

    background:
        var(--card);
}


.filter-form {

    display:
        flex;

    gap:
        12px;

    flex-wrap:
        wrap;
}


.search-box {

    flex:
        1;

    min-width:
        230px;

    position:
        relative;
}


.search-box i {

    position:
        absolute;

    left:
        14px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--muted-dark);
}


.search-box input {

    width:
        100%;

    padding:
        12px 14px 12px 40px;

    border:
        1px solid var(--border);

    border-radius:
        11px;

    outline:
        none;

    color:
        white;

    font-size:
        11px;

    background:
        rgba(255,255,255,.04);
}


.filter-form select {

    padding:
        12px 15px;

    border:
        1px solid var(--border);

    border-radius:
        11px;

    color:
        white;

    outline:
        none;

    font-size:
        11px;

    background:
        #101827;
}


.filter-button {

    padding:
        11px 18px;

    border:
        none;

    border-radius:
        11px;

    color:
        white;

    cursor:
        pointer;

    font-size:
        11px;

    font-weight:
        600;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );
}


/* =========================================================
   TABLE
========================================================= */

.table-panel {

    border:
        1px solid var(--border);

    border-radius:
        20px;

    overflow:
        hidden;

    background:
        var(--card);
}


.table-wrapper {

    overflow-x:
        auto;
}


table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        900px;
}


thead {

    background:
        rgba(255,255,255,.035);
}


th {

    padding:
        16px;

    text-align:
        left;

    color:
        var(--muted);

    font-size:
        9px;

    letter-spacing:
        .5px;

    text-transform:
        uppercase;

    font-weight:
        700;
}


td {

    padding:
        16px;

    border-top:
        1px solid rgba(255,255,255,.055);

    font-size:
        10px;
}


tbody tr {

    transition:
        .2s;
}


tbody tr:hover {

    background:
        rgba(255,255,255,.035);
}


/* =========================================================
   CANDIDATE
========================================================= */

.candidate {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;
}


.candidate-avatar {

    width:
        38px;

    height:
        38px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        12px;

    font-weight:
        700;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.85),
            rgba(124,58,237,.85)
        );
}


.candidate strong {

    display:
        block;

    margin-bottom:
        4px;
}


.candidate span {

    color:
        var(--muted);

    font-size:
        9px;
}


/* =========================================================
   STATUS
========================================================= */

.status {

    display:
        inline-flex;

    padding:
        5px 9px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        600;
}


.status-applied {

    color:
        #93c5fd;

    background:
        rgba(59,130,246,.12);
}


.status-shortlisted {

    color:
        #c4b5fd;

    background:
        rgba(139,92,246,.13);
}


.status-selected {

    color:
        #86efac;

    background:
        rgba(34,197,94,.12);
}


.status-rejected {

    color:
        #fca5a5;

    background:
        rgba(239,68,68,.12);
}


/* =========================================================
   ACTION BUTTON
========================================================= */

.view-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        6px;

    padding:
        8px 11px;

    border-radius:
        9px;

    color:
        #cbd5e1;

    text-decoration:
        none;

    font-size:
        9px;

    border:
        1px solid var(--border);

    transition:
        .2s;
}


.view-button:hover {

    color:
        white;

    background:
        rgba(91,124,255,.16);
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    text-align:
        center;

    padding:
        70px 20px;

    color:
        var(--muted);
}


.empty-state i {

    font-size:
        35px;

    margin-bottom:
        15px;

    color:
        #64748b;
}


.empty-state h3 {

    color:
        #e2e8f0;

    font-size:
        14px;

    margin-bottom:
        8px;
}


.empty-state p {

    font-size:
        10px;
}


/* =========================================================
   MOBILE OVERLAY
========================================================= */

.sidebar-overlay {

    display:
        none;

    position:
        fixed;

    inset:
        0;

    background:
        rgba(0,0,0,.65);

    z-index:
        999;
}


@media (max-width: 1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 850px) {

    .sidebar {

        transform:
            translateX(-100%);

        transition:
            .3s;
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
    }

}


@media (max-width: 600px) {

    .content {

        padding:
            25px 15px 45px;
    }


    .topbar {

        padding:
            15px;
    }


    .profile-text {

        display:
            none;
    }


    .stats-grid {

        grid-template-columns:
            1fr;
    }


    .hero {

        padding:
            25px 22px;
    }


    .filter-form {

        flex-direction:
            column;
    }


    .search-box {

        width:
            100%;
    }


    .filter-button {

        width:
            100%;
    }

}

/* =========================================================
   SIDEBAR
========================================================= */

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
        rgba(3,7,18,.88);

    backdrop-filter:
        blur(24px);

    border-right:
        1px solid
        var(--border);

    z-index: 1000;
}


/* =========================================================
   BRAND
========================================================= */

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
            var(--purple)
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

    color: white;
}


.brand-text span {

    margin-top: 2px;

    font-size: 9px;

    color: var(--muted);
}


/* =========================================================
   NAVIGATION
========================================================= */

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


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

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


/* =========================================================
   MOBILE SIDEBAR OVERLAY
========================================================= */

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


/* =========================================================
   MOBILE
========================================================= */

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

        display: block;
    }


    .main {

        margin-left: 0;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php require_once "../includes/recruiter_sidebar.php"; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


<!-- =========================================================
     TOPBAR
========================================================= -->

<header class="topbar">


<button
    class="mobile-menu"
    id="mobileMenu"
    aria-label="Open menu"
>

<i class="fa-solid fa-bars"></i>

</button>


<div class="page-title">

<small>

Recruitment Management

</small>


<h2>

Applicants

</h2>

</div>


<div class="top-profile">


<div class="profile-text">

<strong>

<?php echo $recruiterName; ?>

</strong>


<span>

<?php echo $companyName; ?>

</span>

</div>


<div class="avatar">

<?php echo e($initial); ?>

</div>


</div>


</header>


<!-- =========================================================
     CONTENT
========================================================= -->

<section class="content">


<!-- HERO -->

<div class="hero">

<div class="hero-content">

<h1>

Manage Your Applicants

</h1>


<p>

Review candidates who have applied to your job opportunities,
track their application status, and identify the best talent
for your organization.

</p>

</div>

</div>


<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="stats-grid">


<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-users"></i>

</div>


<div class="stat-number">

<?php echo $totalApplicants; ?>

</div>


<div class="stat-label">

Total Applicants

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-file-circle-check"></i>

</div>


<div class="stat-number">

<?php echo $appliedCount; ?>

</div>


<div class="stat-label">

New Applications

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-star"></i>

</div>


<div class="stat-number">

<?php echo $shortlistedCount; ?>

</div>


<div class="stat-label">

Shortlisted

</div>

</div>



<div class="stat-card">

<div class="stat-icon">

<i class="fa-solid fa-user-check"></i>

</div>


<div class="stat-number">

<?php echo $selectedCount; ?>

</div>


<div class="stat-label">

Selected

</div>

</div>


</div>


<!-- =========================================================
     FILTER
========================================================= -->

<div class="filter-panel">


<form
    method="GET"
    class="filter-form"
>


<div class="search-box">

<i class="fa-solid fa-magnifying-glass"></i>


<input
    type="text"
    name="search"
    placeholder="Search by student, email, job, department..."
    value="<?php echo e($search); ?>"
>

</div>


<select name="status">


<option value="">

All Status

</option>


<?php foreach ($allowedStatuses as $status): ?>


<option
    value="<?php echo e($status); ?>"
    <?php
        echo
        $statusFilter === $status
        ? "selected"
        : "";
    ?>
>

<?php echo e($status); ?>

</option>


<?php endforeach; ?>


</select>


<button
    type="submit"
    class="filter-button"
>

<i class="fa-solid fa-filter"></i>

Filter

</button>


</form>


</div>


<!-- =========================================================
     APPLICANTS TABLE
========================================================= -->

<div class="table-panel">


<?php if (count($applicants) > 0): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>

Candidate

</th>


<th>

Job Applied

</th>


<th>

Department

</th>


<th>

Course

</th>


<th>

CGPA

</th>


<th>

Applied Date

</th>


<th>

Status

</th>


<th>

Action

</th>

</tr>

</thead>


<tbody>


<?php foreach ($applicants as $applicant): ?>


<?php


$currentStatus =
    strtolower(
        trim(
            $applicant["status"]
        )
    );


$statusClass =
    "status-applied";


if (
    $currentStatus ===
    "shortlisted"
) {

    $statusClass =
        "status-shortlisted";

} elseif (

    $currentStatus ===
    "selected"

) {

    $statusClass =
        "status-selected";

} elseif (

    $currentStatus ===
    "rejected"

) {

    $statusClass =
        "status-rejected";
}


$candidateInitial =
    strtoupper(
        substr(
            trim(
                $applicant["full_name"]
            ),
            0,
            1
        )
    );


?>


<tr>


<td>

<div class="candidate">


<div class="candidate-avatar">

<?php echo e($candidateInitial); ?>

</div>


<div>

<strong>

<?php
echo e(
    $applicant["full_name"]
);
?>

</strong>


<span>

<?php
echo e(
    $applicant["email"]
);
?>

</span>

</div>


</div>

</td>



<td>

<?php
echo e(
    $applicant["job_title"]
);
?>

</td>



<td>

<?php
echo e(
    $applicant["department"]
    ?: "—"
);
?>

</td>



<td>

<?php
echo e(
    $applicant["course"]
    ?: "—"
);
?>

</td>



<td>

<?php
echo e(
    $applicant["cgpa"]
    ?: "—"
);
?>

</td>



<td>

<?php

if (
    !empty(
        $applicant["applied_at"]
    )
) {

    echo date(
        "d M Y",
        strtotime(
            $applicant["applied_at"]
        )
    );

} else {

    echo "—";

}

?>

</td>



<td>

<span
    class="
        status
        <?php echo $statusClass; ?>
    "
>

<?php
echo e(
    $applicant["status"]
);
?>

</span>

</td>



<td>

<a
    href="applicant_details.php?id=<?php echo (int)$applicant["application_id"]; ?>"
    class="view-button"
>

<i class="fa-solid fa-eye"></i>

View

</a>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty-state">


<i class="fa-solid fa-users"></i>


<h3>

No applicants found

</h3>


<p>

Applications from students will appear here.

</p>


</div>


<?php endif; ?>


</div>


</section>


</main>


<!-- =========================================================
     MOBILE SIDEBAR SCRIPT
========================================================= -->

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
        !sidebar
        ||
        !sidebarOverlay
    ) {
        return;
    }

    sidebar.classList.add(
        "show"
    );

    sidebarOverlay.classList.add(
        "show"
    );
}


function closeSidebar() {

    if (
        !sidebar
        ||
        !sidebarOverlay
    ) {
        return;
    }

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