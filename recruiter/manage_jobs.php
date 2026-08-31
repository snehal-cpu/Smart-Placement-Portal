<?php

require_once "../includes/auth.php";
requireRole("recruiter");

require_once "../config/database.php";


$user_id = $_SESSION["user_id"];


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
   DELETE JOB
========================================================= */

$message = "";
$messageType = "";


if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_job"])
) {

    $job_id = filter_input(
        INPUT_POST,
        "job_id",
        FILTER_VALIDATE_INT
    );


    if (!$job_id) {

        $message = "Invalid job selected.";
        $messageType = "danger";

    } else {


        /* Verify Job Ownership */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT j.id
             FROM jobs j
             INNER JOIN companies c
                ON j.company_id = c.id
             WHERE j.id = ?
             AND c.user_id = ?
             LIMIT 1"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $job_id,
            $user_id
        );


        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $jobExists = mysqli_fetch_assoc($result);


        if (!$jobExists) {

            $message =
                "You are not authorized to delete this job.";

            $messageType =
                "danger";

        } else {

            mysqli_begin_transaction($conn);


            try {


                /* Delete Job Skills */

                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM job_skills
                     WHERE job_id = ?"
                );


                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $job_id
                );


                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(
                        "Unable to remove job skills."
                    );
                }


                /* Delete Applications */

                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM applications
                     WHERE job_id = ?"
                );


                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $job_id
                );


                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(
                        "Unable to remove applications."
                    );
                }


                /* Delete Job */

                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM jobs
                     WHERE id = ?"
                );


                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $job_id
                );


                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(
                        "Unable to delete the job."
                    );
                }


                mysqli_commit($conn);


                $message =
                    "Job deleted successfully.";

                $messageType =
                    "success";


            } catch (Exception $error) {

                mysqli_rollback($conn);

                $message =
                    $error->getMessage();

                $messageType =
                    "danger";
            }
        }
    }
}


/* =========================================================
   SEARCH
========================================================= */

$search = trim(
    $_GET["search"] ?? ""
);


/* =========================================================
   STATUS FILTER
========================================================= */

$statusFilter = trim(
    $_GET["status"] ?? ""
);


/* =========================================================
   GET JOBS
========================================================= */

$jobs = [];


$sql = "
    SELECT

        j.id,
        j.job_title,
        j.job_type,
        j.location,
        j.salary,
        j.min_cgpa,
        j.eligible_department,
        j.eligible_year,
        j.application_deadline,
        j.vacancies,
        j.status,
        j.description,

        c.company_name,

        (
            SELECT COUNT(*)
            FROM applications a
            WHERE a.job_id = j.id
        ) AS application_count

    FROM jobs j

    INNER JOIN companies c
        ON j.company_id = c.id

    WHERE c.user_id = ?
";


$params = [$user_id];
$types = "i";


/* SEARCH */

if ($search !== "") {

    $sql .= "
        AND (
            j.job_title LIKE ?
            OR j.location LIKE ?
            OR j.job_type LIKE ?
        )
    ";


    $searchValue =
        "%" . $search . "%";


    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}


/* STATUS */

if ($statusFilter !== "") {

    $allowedStatuses = [
        "Open",
        "Draft",
        "Closed"
    ];


    if (
        in_array(
            $statusFilter,
            $allowedStatuses,
            true
        )
    ) {

        $sql .= "
            AND j.status = ?
        ";


        $params[] =
            $statusFilter;

        $types .= "s";
    }
}


$sql .= "
    ORDER BY j.id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );


    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);


    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

        $jobs[] =
            $row;
    }
}


/* =========================================================
   STATISTICS
========================================================= */

$totalJobs = 0;
$openJobs = 0;
$draftJobs = 0;
$totalApplications = 0;


/* TOTAL JOBS */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs j
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE c.user_id = ?"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$totalJobs =
    (int)$row["total"];


/* OPEN JOBS */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs j
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE c.user_id = ?
     AND j.status = 'Open'"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$openJobs =
    (int)$row["total"];


/* DRAFT JOBS */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jobs j
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE c.user_id = ?
     AND j.status = 'Draft'"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$draftJobs =
    (int)$row["total"];


/* TOTAL APPLICATIONS */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     INNER JOIN jobs j
        ON a.job_id = j.id
     INNER JOIN companies c
        ON j.company_id = c.id
     WHERE c.user_id = ?"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$totalApplications =
    (int)$row["total"];


/* =========================================================
   USER INFORMATION
========================================================= */

$userName =
    $_SESSION["name"]
    ?? $_SESSION["user_name"]
    ?? "Recruiter";


$firstLetter =
    strtoupper(
        substr(
            $userName,
            0,
            1
        )
    );

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
    Manage Jobs | Smart Placement Portal
</title>


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


<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --bg: #080f1d;
    --bg-soft: #0d1627;

    --card: #172131;
    --card-hover: #1b2739;

    --border: #26364d;

    --text: #f1f5f9;
    --muted: #94a3b8;

    --primary: #7189ff;
    --primary-light: #8b9cff;

    --purple: #7446d9;
    --cyan: #62d8f2;

    --green: #4ade80;
    --yellow: #f8c74e;
    --danger: #fb7185;

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

    font-family:
        "Inter",
        sans-serif;

    color:
        var(--text);

    background-color:
        var(--bg);

    background-image:

        linear-gradient(
            rgba(45, 60, 82, .13) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(45, 60, 82, .13) 1px,
            transparent 1px
        );

    background-size:
        70px 70px;

}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar {
    width: 8px;
}


::-webkit-scrollbar-track {
    background: var(--bg);
}


::-webkit-scrollbar-thumb {

    background:
        #334155;

    border-radius:
        20px;
}




/* =========================================================
   MAIN
========================================================= */

.main {

    min-height:
        100vh;

    margin-left:
        265px;

}


/* =========================================================
   TOP HEADER
========================================================= */

.top-header {

    min-height:
        105px;

    padding:
        24px
        52px;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    border-bottom:
        1px solid
        rgba(71, 85, 105, .35);

    background:
        rgba(8, 15, 29, .82);

    backdrop-filter:
        blur(16px);

}


.greeting-small {

    color:
        var(--muted);

    font-size:
        13px;

    font-weight:
        500;

    margin-bottom:
        7px;

}


.welcome-title {

    margin: 0;

    font-size:
        27px;

    font-weight:
        800;

    letter-spacing:
        -.8px;

}


.welcome-name {

    background:

        linear-gradient(
            90deg,
            #9b9cff,
            #57d7f7
        );

    -webkit-background-clip:
        text;

    -webkit-text-fill-color:
        transparent;

}


.account-area {

    display: flex;

    align-items: center;

    gap:
        14px;

}


.account-text {

    text-align:
        right;

}


.account-name {

    font-size:
        13px;

    font-weight:
        700;

}


.account-role {

    margin-top:
        4px;

    color:
        var(--muted);

    font-size:
        11px;

}


.avatar {

    width: 52px;
    height: 52px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius:
        17px;

    color: white;

    font-size:
        16px;

    font-weight:
        800;

    background:

        linear-gradient(
            135deg,
            #6475e9,
            #7045d4
        );

}


/* =========================================================
   PAGE
========================================================= */

.page-container {

    max-width:
        1650px;

    margin:
        auto;

    padding:

        42px
        52px
        70px;

}


/* =========================================================
   HERO
========================================================= */

.manage-hero {

    position:
        relative;

    overflow:
        hidden;

    min-height:
        260px;

    padding:
        42px
        48px;

    margin-bottom:
        42px;

    border-radius:
        30px;

    border:
        1px solid
        rgba(104, 124, 170, .35);

    background:

        linear-gradient(
            110deg,
            rgba(67, 78, 132, .72),
            rgba(34, 42, 77, .92) 50%,
            rgba(11, 29, 43, .96)
        );

}


.manage-hero::before {

    content: "";

    position:
        absolute;

    right:
        8%;

    top:
        25px;

    width:
        170px;

    height:
        170px;

    border-radius:
        50%;

    border:
        1px solid
        rgba(120, 144, 255, .13);

    box-shadow:

        0 0 0 35px
        rgba(120, 144, 255, .04),

        0 0 0 70px
        rgba(120, 144, 255, .025);

}


.manage-hero::after {

    content: "";

    position:
        absolute;

    right:
        -80px;

    bottom:
        -110px;

    width:
        330px;

    height:
        330px;

    border-radius:
        50%;

    background:

        radial-gradient(
            circle,
            rgba(90, 111, 255, .20),
            transparent 67%
        );

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
        9px;

    padding:
        10px
        17px;

    border-radius:
        50px;

    border:
        1px solid
        rgba(130, 145, 190, .35);

    background:
        rgba(255, 255, 255, .04);

    color:
        #b8c1d5;

    font-size:
        12px;

    font-weight:
        600;

}


.hero-dot {

    width:
        9px;

    height:
        9px;

    border-radius:
        50%;

    background:
        var(--green);

    box-shadow:

        0 0 12px
        rgba(74, 222, 128, .9);

}


.manage-hero h1 {

    max-width:
        700px;

    margin:

        24px
        0
        12px;

    font-size:
        43px;

    line-height:
        1.15;

    font-weight:
        800;

    letter-spacing:
        -1.5px;

}


.gradient-text {

    background:

        linear-gradient(
            90deg,
            #9b9cff,
            #5fd8f4
        );

    -webkit-background-clip:
        text;

    -webkit-text-fill-color:
        transparent;

}


.manage-hero p {

    max-width:
        660px;

    margin:
        0;

    color:
        #aab5c8;

    font-size:
        14px;

    line-height:
        1.8;

}


/* =========================================================
   BUTTONS
========================================================= */

.hero-actions {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        13px;

    margin-top:
        28px;

}


.primary-btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        9px;

    padding:
        13px
        22px;

    border:
        none;

    border-radius:
        14px;

    color:
        white;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        700;

    background:

        linear-gradient(
            135deg,
            #7189ff,
            #6b38d1
        );

    box-shadow:

        0 10px 28px
        rgba(91, 91, 240, .28);

    transition:
        .25s;

}


.primary-btn:hover {

    color:
        white;

    transform:
        translateY(-2px);

}


.secondary-btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        9px;

    padding:
        13px
        22px;

    border-radius:
        14px;

    border:
        1px solid
        #3a485e;

    color:
        #cbd5e1;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        700;

    background:
        rgba(255, 255, 255, .025);

    transition:
        .2s;

}


.secondary-btn:hover {

    color:
        white;

    background:
        rgba(255, 255, 255, .06);

}


/* =========================================================
   SECTION HEADER
========================================================= */

.section-header {

    margin-bottom:
        22px;

}


.section-header h2 {

    margin:
        0;

    font-size:
        22px;

    font-weight:
        800;

}


.section-header p {

    margin:

        7px
        0
        0;

    color:
        var(--muted);

    font-size:
        13px;

}


/* =========================================================
   STATS
========================================================= */

.stats-grid {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        20px;

    margin-bottom:
        42px;

}


.stat-card {

    min-height:
        185px;

    padding:
        24px;

    border-radius:
        24px;

    border:
        1px solid
        var(--border);

    background:

        linear-gradient(
            145deg,
            #192536,
            #141d2c
        );

    transition:
        .25s;

}


.stat-card:hover {

    transform:
        translateY(-4px);

    border-color:
        #40516c;

}


.stat-top {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

}


.stat-icon {

    width:
        56px;

    height:
        56px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        18px;

    font-size:
        21px;

}


.stat-card:nth-child(1)
.stat-icon {

    color:
        #91b7ff;

    background:
        rgba(92, 125, 225, .17);

}


.stat-card:nth-child(2)
.stat-icon {

    color:
        #67d8ee;

    background:
        rgba(29, 177, 207, .15);

}


.stat-card:nth-child(3)
.stat-icon {

    color:
        #c1a8ff;

    background:
        rgba(126, 94, 211, .18);

}


.stat-card:nth-child(4)
.stat-icon {

    color:
        #ffd45d;

    background:
        rgba(207, 154, 45, .16);

}


.stat-link-icon {

    color:
        #617086;

    font-size:
        15px;

}


.stat-number {

    margin-top:
        27px;

    font-size:
        37px;

    font-weight:
        800;

}


.stat-label {

    margin-top:
        5px;

    color:
        #9ba8ba;

    font-size:
        13px;

}


/* =========================================================
   FILTER CARD
========================================================= */

.filter-card {

    padding:
        22px;

    margin-bottom:
        25px;

    border-radius:
        22px;

    border:
        1px solid
        var(--border);

    background:
        rgba(22, 32, 47, .94);

}


.filter-form {

    display:
        flex;

    align-items:
        center;

    gap:
        14px;

}


.search-box {

    position:
        relative;

    flex:
        1;

}


.search-box i {

    position:
        absolute;

    top:
        50%;

    left:
        17px;

    transform:
        translateY(-50%);

    color:
        #708097;

}


.dark-input,
.dark-select {

    width:
        100%;

    height:
        50px;

    border-radius:
        13px;

    border:
        1px solid
        #344258;

    outline:
        none;

    color:
        #dce5f2;

    background:
        #101a29;

    font-size:
        13px;

}


.dark-input {

    padding:
        0
        16px
        0
        45px;

}


.dark-select {

    max-width:
        180px;

    padding:
        0
        15px;

}


.dark-input::placeholder {

    color:
        #66758a;

}


.dark-input:focus,
.dark-select:focus {

    border-color:
        #687cff;

    box-shadow:

        0 0 0 3px
        rgba(104, 124, 255, .12);

}


.filter-btn {

    height:
        50px;

    padding:
        0
        22px;

    border:
        none;

    border-radius:
        13px;

    color:
        white;

    font-size:
        13px;

    font-weight:
        700;

    background:
        #27344a;

    transition:
        .2s;

}


.filter-btn:hover {

    background:
        #34435d;

}


.clear-btn {

    color:
        #9ba8ba;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        600;

}


/* =========================================================
   JOB LIST
========================================================= */

.jobs-container {

    display:
        flex;

    flex-direction:
        column;

    gap:
        18px;

}


.job-card {

    padding:
        27px;

    border-radius:
        24px;

    border:
        1px solid
        var(--border);

    background:

        linear-gradient(
            145deg,
            #182334,
            #131d2b
        );

    transition:
        .25s;

}


.job-card:hover {

    transform:
        translateY(-2px);

    border-color:
        #42536d;

}


.job-top {

    display:
        flex;

    align-items:
        flex-start;

    justify-content:
        space-between;

    gap:
        20px;

}


.job-title-area {

    display:
        flex;

    align-items:
        center;

    gap:
        17px;

}


.job-icon {

    width:
        58px;

    height:
        58px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        18px;

    color:
        #94b7ff;

    font-size:
        21px;

    background:
        rgba(83, 113, 209, .16);

}


.job-title {

    margin:
        0;

    font-size:
        19px;

    font-weight:
        800;

}


.job-company {

    margin-top:
        7px;

    color:
        #8f9db0;

    font-size:
        12px;

}


/* =========================================================
   STATUS
========================================================= */

.status-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        9px
        14px;

    border-radius:
        30px;

    font-size:
        11px;

    font-weight:
        700;

}


.status-open {

    color:
        #72e8a5;

    background:
        rgba(43, 180, 111, .12);

    border:
        1px solid
        rgba(43, 180, 111, .18);

}


.status-draft {

    color:
        #ffc86b;

    background:
        rgba(231, 156, 40, .12);

    border:
        1px solid
        rgba(231, 156, 40, .18);

}


.status-closed {

    color:
        #a5b1c2;

    background:
        rgba(148, 163, 184, .10);

    border:
        1px solid
        rgba(148, 163, 184, .15);

}


/* =========================================================
   JOB META
========================================================= */

.job-meta {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        10px;

    margin-top:
        23px;

}


.meta-item {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    padding:
        9px
        13px;

    border-radius:
        10px;

    border:
        1px solid
        #2c394c;

    color:
        #9eabbd;

    background:
        rgba(255, 255, 255, .025);

    font-size:
        11px;

    font-weight:
        600;

}


.meta-item i {

    color:
        #7488ff;

}


/* =========================================================
   JOB FOOTER
========================================================= */

.job-bottom {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    margin-top:
        24px;

    padding-top:
        21px;

    border-top:
        1px solid
        #273448;

}


.application-info {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    color:
        #9caabc;

    font-size:
        12px;

    font-weight:
        600;

}


.application-info i {

    color:
        #66d9f4;

}


.job-actions {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

}


.action-btn {

    width:
        40px;

    height:
        40px;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        11px;

    border:
        1px solid
        #334156;

    color:
        #9eacc0;

    background:
        rgba(255, 255, 255, .025);

    text-decoration:
        none;

    transition:
        .2s;

}


.action-btn:hover {

    color:
        white;

    border-color:
        #6579f4;

    background:
        rgba(99, 119, 244, .15);

}


button.action-btn {

    cursor:
        pointer;

}


.action-btn.delete:hover {

    color:
        #ff8b9a;

    border-color:
        rgba(244, 63, 94, .4);

    background:
        rgba(244, 63, 94, .1);

}


/* =========================================================
   ALERT
========================================================= */

.custom-alert {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        15px
        19px;

    margin-bottom:
        25px;

    border-radius:
        15px;

    font-size:
        13px;

    font-weight:
        600;

}


.alert-success {

    color:
        #86efac;

    border:
        1px solid
        rgba(74, 222, 128, .25);

    background:
        rgba(34, 197, 94, .08);

}


.alert-danger {

    color:
        #fda4af;

    border:
        1px solid
        rgba(244, 63, 94, .25);

    background:
        rgba(244, 63, 94, .08);

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    padding:
        75px
        25px;

    text-align:
        center;

    border-radius:
        25px;

    border:
        1px solid
        var(--border);

    background:
        #141e2d;

}


.empty-icon {

    width:
        80px;

    height:
        80px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    margin:
        0
        auto
        22px;

    border-radius:
        24px;

    color:
        #8ca7ff;

    font-size:
        29px;

    background:
        rgba(89, 110, 220, .15);

}


.empty-state h4 {

    font-size:
        20px;

    font-weight:
        800;

}


.empty-state p {

    color:
        var(--muted);

    font-size:
        13px;

    margin-bottom:
        24px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 900px) {

    .sidebar {

        width:
            230px;

    }


    .main {

        margin-left:
            230px;

    }


    .top-header {

        padding:
            24px
            30px;

    }


    .page-container {

        padding:
            30px;

    }

}


@media (max-width: 768px) {

    .sidebar {

        position:
            relative;

        width:
            100%;

        height:
            auto;

        min-height:
            auto;

    }


    .main {

        margin-left:
            0;

    }


    .top-header {

        padding:
            22px
            20px;

    }


    .account-text {

        display:
            none;

    }


    .welcome-title {

        font-size:
            21px;

    }


    .page-container {

        padding:
            25px
            18px
            50px;

    }


    .manage-hero {

        padding:
            32px
            25px;

    }


    .manage-hero h1 {

        font-size:
            31px;

    }


    .stats-grid {

        grid-template-columns:
            1fr;

    }


    .filter-form {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .dark-select {

        max-width:
            none;

    }


    .filter-btn {

        width:
            100%;

    }


    .job-top {

        flex-direction:
            column;

    }


    .job-bottom {

        flex-direction:
            column;

        align-items:
            flex-start;

    }


    .job-actions {

        width:
            100%;

        justify-content:
            flex-end;

    }

}


@media (max-width: 480px) {

    .brand {

        padding-bottom:
            20px;

    }


    .job-title-area {

        align-items:
            flex-start;

    }


    .job-icon {

        width:
            48px;

        height:
            48px;

        font-size:
            18px;

    }


    .job-card {

        padding:
            20px;

    }


    .hero-actions {

        flex-direction:
            column;

    }


    .primary-btn,
    .secondary-btn {

        width:
            100%;

    }

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

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php require_once "../includes/recruiter_sidebar.php"; ?>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


<!-- =====================================================
     TOP HEADER
===================================================== -->

<header class="top-header">


    <div>


        <div class="greeting-small">

            Manage your recruitment opportunities

        </div>


        <h1 class="welcome-title">

            Your

            <span class="welcome-name">
                Jobs
            </span>

            Overview 💼

        </h1>


    </div>


    <div class="account-area">


        <div class="account-text">


            <div class="account-name">

                <?php echo e($userName); ?>

            </div>


            <div class="account-role">

                Recruiter Account

            </div>


        </div>


        <div class="avatar">

            <?php echo e($firstLetter); ?>

        </div>


    </div>


</header>


<!-- =====================================================
     PAGE CONTAINER
===================================================== -->

<div class="page-container">


<!-- =====================================================
     HERO
===================================================== -->

<section class="manage-hero">


    <div class="hero-content">


        <div class="hero-badge">

            <span class="hero-dot"></span>

            Your recruitment workspace

        </div>


        <h1>

            Manage your

            <br>

            <span class="gradient-text">

                job opportunities.

            </span>

        </h1>


        <p>

            Track your active positions, manage recruitment
            opportunities and review applications from talented
            candidates through your Smart Placement Portal.

        </p>


        <div class="hero-actions">


            <a
                href="post_job.php"
                class="primary-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Post a Job

            </a>


            <a
                href="applicants.php"
                class="secondary-btn"
            >

                <i class="fa-solid fa-users"></i>

                View Applications

            </a>


        </div>


    </div>


</section>


<!-- =====================================================
     OVERVIEW
===================================================== -->

<div class="section-header">


    <h2>

        Job Overview

    </h2>


    <p>

        A quick overview of your recruitment activity.

    </p>


</div>


<section class="stats-grid">


    <!-- TOTAL JOBS -->

    <div class="stat-card">


        <div class="stat-top">


            <div class="stat-icon">

                <i class="fa-solid fa-briefcase"></i>

            </div>


            <i
                class="fa-solid fa-arrow-up-right-from-square stat-link-icon"
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
                class="fa-solid fa-arrow-up-right-from-square stat-link-icon"
            ></i>


        </div>


        <div class="stat-number">

            <?php echo $openJobs; ?>

        </div>


        <div class="stat-label">

            Active Jobs

        </div>


    </div>


    <!-- DRAFT JOBS -->

    <div class="stat-card">


        <div class="stat-top">


            <div class="stat-icon">

                <i class="fa-solid fa-file"></i>

            </div>


            <i
                class="fa-solid fa-arrow-up-right-from-square stat-link-icon"
            ></i>


        </div>


        <div class="stat-number">

            <?php echo $draftJobs; ?>

        </div>


        <div class="stat-label">

            Draft Jobs

        </div>


    </div>


    <!-- APPLICATIONS -->

    <div class="stat-card">


        <div class="stat-top">


            <div class="stat-icon">

                <i class="fa-solid fa-users"></i>

            </div>


            <i
                class="fa-solid fa-arrow-up-right-from-square stat-link-icon"
            ></i>


        </div>


        <div class="stat-number">

            <?php echo $totalApplications; ?>

        </div>


        <div class="stat-label">

            Total Applications

        </div>


    </div>


</section>


<!-- =====================================================
     JOB MANAGEMENT HEADER
===================================================== -->

<div class="section-header">


    <h2>

        Manage Jobs

    </h2>


    <p>

        Search, filter and manage all your posted jobs.

    </p>


</div>


<!-- =====================================================
     ALERT
===================================================== -->

<?php if ($message !== ""): ?>


<div
    class="custom-alert alert-<?php echo e($messageType); ?>"
>


    <i
        class="fa-solid <?php

        echo $messageType === "success"
            ? "fa-circle-check"
            : "fa-circle-exclamation";

        ?>"
    ></i>


    <?php echo e($message); ?>


</div>


<?php endif; ?>


<!-- =====================================================
     FILTER
===================================================== -->

<section class="filter-card">


    <form
        method="GET"
        class="filter-form"
    >


        <div class="search-box">


            <i
                class="fa-solid fa-magnifying-glass"
            ></i>


            <input
                type="text"
                name="search"
                class="dark-input"
                placeholder="Search by job title, location or job type..."
                value="<?php echo e($search); ?>"
            >


        </div>


        <select
            name="status"
            class="dark-select"
        >


            <option value="">

                All Status

            </option>


            <option
                value="Open"
                <?php
                echo $statusFilter === "Open"
                    ? "selected"
                    : "";
                ?>
            >

                Open

            </option>


            <option
                value="Draft"
                <?php
                echo $statusFilter === "Draft"
                    ? "selected"
                    : "";
                ?>
            >

                Draft

            </option>


            <option
                value="Closed"
                <?php
                echo $statusFilter === "Closed"
                    ? "selected"
                    : "";
                ?>
            >

                Closed

            </option>


        </select>


        <button
            type="submit"
            class="filter-btn"
        >

            <i class="fa-solid fa-filter"></i>

            Filter

        </button>


        <?php if (
            $search !== "" ||
            $statusFilter !== ""
        ): ?>


        <a
            href="manage_jobs.php"
            class="clear-btn"
        >

            Clear

        </a>


        <?php endif; ?>


    </form>


</section>


<!-- =====================================================
     JOB LIST
===================================================== -->

<?php if (!empty($jobs)): ?>


<section class="jobs-container">


<?php foreach ($jobs as $job): ?>


<?php


$status =
    $job["status"];


$statusClass =
    match ($status) {

        "Open" =>
            "status-open",

        "Draft" =>
            "status-draft",

        default =>
            "status-closed"

    };


$deadlineDate =
    !empty($job["application_deadline"])
        ? date(
            "d M Y",
            strtotime(
                $job["application_deadline"]
            )
        )
        : "Not specified";


?>


<article class="job-card">


<!-- JOB TOP -->

<div class="job-top">


    <div class="job-title-area">


        <div class="job-icon">

            <i class="fa-solid fa-briefcase"></i>

        </div>


        <div>


            <h3 class="job-title">

                <?php
                echo e(
                    $job["job_title"]
                );
                ?>

            </h3>


            <div class="job-company">


                <i
                    class="fa-solid fa-building me-1"
                ></i>


                <?php
                echo e(
                    $job["company_name"]
                );
                ?>


            </div>


        </div>


    </div>


    <span
        class="status-badge <?php
        echo $statusClass;
        ?>"
    >


        <i
            class="fa-solid <?php

            echo $status === "Open"
                ? "fa-circle-check"
                : (
                    $status === "Draft"
                        ? "fa-file"
                        : "fa-circle-xmark"
                );

            ?>"
        ></i>


        <?php echo e($status); ?>


    </span>


</div>


<!-- JOB META -->

<div class="job-meta">


    <span class="meta-item">

        <i
            class="fa-solid fa-location-dot"
        ></i>

        <?php echo e($job["location"]); ?>

    </span>


    <span class="meta-item">

        <i
            class="fa-solid fa-clock"
        ></i>

        <?php echo e($job["job_type"]); ?>

    </span>


    <span class="meta-item">

        <i
            class="fa-solid fa-graduation-cap"
        ></i>

        CGPA ≥

        <?php echo e($job["min_cgpa"]); ?>

    </span>


    <span class="meta-item">

        <i
            class="fa-solid fa-users"
        ></i>

        <?php echo e($job["vacancies"]); ?>

        Vacancies

    </span>


    <span class="meta-item">

        <i
            class="fa-solid fa-calendar"
        ></i>

        Deadline:

        <?php echo e($deadlineDate); ?>

    </span>


    <?php if (!empty($job["salary"])): ?>


    <span class="meta-item">

        <i
            class="fa-solid fa-indian-rupee-sign"
        ></i>

        <?php echo e($job["salary"]); ?>

    </span>


    <?php endif; ?>


</div>


<!-- JOB FOOTER -->

<div class="job-bottom">


    <div class="application-info">


        <i
            class="fa-solid fa-users"
        ></i>


        <span>


            <?php
            echo e(
                $job["application_count"]
            );
            ?>


            application<?php

            echo (
                (int)$job["application_count"] === 1
            )
                ? ""
                : "s";

            ?>


        </span>


    </div>


    <div class="job-actions">


        <!-- VIEW -->

        <a
            href="view_job.php?id=<?php
            echo (int)$job["id"];
            ?>"
            class="action-btn"
            title="View Job"
        >

            <i
                class="fa-solid fa-eye"
            ></i>

        </a>


        <!-- EDIT -->

        <a
            href="edit_job.php?id=<?php
            echo (int)$job["id"];
            ?>"
            class="action-btn"
            title="Edit Job"
        >

            <i
                class="fa-solid fa-pen"
            ></i>

        </a>


        <!-- APPLICANTS -->

        <a
            href="applicants.php?job_id=<?php
            echo (int)$job["id"];
            ?>"
            class="action-btn"
            title="View Applicants"
        >

            <i
                class="fa-solid fa-users"
            ></i>

        </a>


        <!-- DELETE -->

        <form
            method="POST"
            style="display:inline;"
            onsubmit="return confirmDelete();"
        >


            <input
                type="hidden"
                name="job_id"
                value="<?php
                echo (int)$job["id"];
                ?>"
            >


            <button
                type="submit"
                name="delete_job"
                class="action-btn delete"
                title="Delete Job"
            >

                <i
                    class="fa-solid fa-trash"
                ></i>

            </button>


        </form>


    </div>


</div>


</article>


<?php endforeach; ?>


</section>


<?php else: ?>


<!-- =====================================================
     EMPTY STATE
===================================================== -->

<section class="empty-state">


    <div class="empty-icon">

        <i
            class="fa-solid fa-briefcase"
        ></i>

    </div>


    <?php if (
        $search !== "" ||
        $statusFilter !== ""
    ): ?>


    <h4>

        No matching jobs found

    </h4>


    <p>

        Try changing your search or status filter.

    </p>


    <a
        href="manage_jobs.php"
        class="primary-btn"
    >

        <i
            class="fa-solid fa-rotate-left"
        ></i>

        Clear Filters

    </a>


    <?php else: ?>


    <h4>

        No Jobs Posted Yet

    </h4>


    <p>

        Create your first job opportunity and start
        building your team.

    </p>


    <a
        href="post_job.php"
        class="primary-btn"
    >

        <i
            class="fa-solid fa-plus"
        ></i>

        Post Your First Job

    </a>


    <?php endif; ?>


</section>


<?php endif; ?>


</div>


</main>


<script>


function confirmDelete()
{

    return confirm(

        "Are you sure you want to delete this job?\n\n" +

        "This will permanently remove the job and its associated applications."

    );

}


</script>


</body>

</html>