<?php

require_once "../includes/auth.php";
requireRole("recruiter");

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
   SESSION
========================================================= */

$user_id = $_SESSION["user_id"];


/* =========================================================
   GET JOB ID
========================================================= */

$job_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


if (!$job_id) {

    header("Location: manage_jobs.php");
    exit;
}


/* =========================================================
   FETCH JOB
========================================================= */

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        j.id,
        j.company_id,
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
        c.company_email,
        c.phone,
        c.website,
        c.location AS company_location,
        c.industry,
        c.description AS company_description,
        c.logo

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


$result =
    mysqli_stmt_get_result($stmt);


$job =
    mysqli_fetch_assoc($result);


if (!$job) {

    header("Location: manage_jobs.php");
    exit;
}


/* =========================================================
   DEADLINE
========================================================= */

$deadline_text = "Not specified";
$deadline_class = "";

if (!empty($job["application_deadline"])) {

    $deadline_timestamp =
        strtotime(
            $job["application_deadline"]
        );

    $deadline_text =
        date(
            "d M Y",
            $deadline_timestamp
        );


    if (
        $deadline_timestamp <
        strtotime(date("Y-m-d"))
    ) {

        $deadline_class = "expired";

    } elseif (
        $deadline_timestamp <=
        strtotime("+7 days")
    ) {

        $deadline_class = "soon";
    }
}


/* =========================================================
   JOB TYPE ICON
========================================================= */

$job_type_icon = "fa-briefcase";

if ($job["job_type"] === "Internship") {

    $job_type_icon =
        "fa-user-graduate";

} elseif ($job["job_type"] === "Part Time") {

    $job_type_icon =
        "fa-clock";
}


/* =========================================================
   STATUS CLASS
========================================================= */

$status_class = strtolower(
    $job["status"]
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
    href="../assets/css/recruiter-theme.css"
    rel="stylesheet"
>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    View Job | Smart Placement Portal
</title>


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


<!-- Google Font -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: "Inter", sans-serif;

    background: #f8fafc;

    color: #0f172a;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background: #0f172a;

    color: white;

    padding: 25px 18px;

    z-index: 1000;
}


.logo {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px 12px 30px;

    font-size: 19px;

    font-weight: 800;
}


.logo-icon {

    width: 40px;
    height: 40px;

    border-radius: 12px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: linear-gradient(
        135deg,
        #2563eb,
        #4f46e5
    );
}


.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 15px;

    margin-bottom: 5px;

    border-radius: 10px;

    color: #94a3b8;

    text-decoration: none;

    transition: .2s;
}


.nav-link:hover {

    color: white;

    background: #1e293b;
}


.nav-link.active {

    color: white;

    background: linear-gradient(
        135deg,
        #2563eb,
        #4f46e5
    );
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 250px;

    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 75px;

    background: white;

    border-bottom: 1px solid #e2e8f0;

    padding: 0 35px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.topbar-title {

    font-size: 18px;

    font-weight: 700;
}


.back-link {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #64748b;

    text-decoration: none;

    font-size: 12px;

    font-weight: 600;
}


.back-link:hover {

    color: #2563eb;
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width: 1100px;

    padding: 35px;

    margin: auto;
}


/* =========================================================
   JOB HEADER
========================================================= */

.job-header {

    background: linear-gradient(
        135deg,
        #0f172a,
        #1e3a8a
    );

    color: white;

    border-radius: 18px;

    padding: 32px;

    margin-bottom: 22px;

    position: relative;

    overflow: hidden;
}


.job-header::after {

    content: "";

    position: absolute;

    width: 230px;
    height: 230px;

    border-radius: 50%;

    background: rgba(255,255,255,.05);

    right: -70px;
    top: -100px;
}


.job-header-content {

    position: relative;

    z-index: 2;
}


.company-name {

    color: #bfdbfe;

    font-size: 12px;

    font-weight: 600;

    margin-bottom: 10px;
}


.job-title {

    font-size: 29px;

    font-weight: 800;

    margin-bottom: 18px;
}


.job-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;
}


.meta-pill {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 8px 12px;

    border-radius: 20px;

    background: rgba(
        255,
        255,
        255,
        .10
    );

    border: 1px solid rgba(
        255,
        255,
        255,
        .12
    );

    font-size: 10px;

    color: #e2e8f0;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge {

    position: absolute;

    right: 30px;
    top: 30px;

    z-index: 5;

    padding: 7px 13px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;
}


.status-badge.open {

    background: #dcfce7;

    color: #166534;
}


.status-badge.closed {

    background: #fee2e2;

    color: #991b1b;
}


.status-badge.draft {

    background: #fef3c7;

    color: #92400e;
}


/* =========================================================
   GRID
========================================================= */

.layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        330px;

    gap: 22px;
}


/* =========================================================
   CARD
========================================================= */

.card-box {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 15px;

    margin-bottom: 20px;

    overflow: hidden;
}


.card-header {

    padding: 19px 22px;

    border-bottom: 1px solid #eef2f7;

    font-size: 13px;

    font-weight: 800;

    display: flex;

    align-items: center;

    gap: 9px;
}


.card-header i {

    color: #2563eb;
}


.card-body {

    padding: 22px;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.description {

    color: #475569;

    font-size: 12px;

    line-height: 1.8;

    white-space: pre-line;
}


/* =========================================================
   DETAIL GRID
========================================================= */

.detail-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;
}


.detail-item {

    padding: 15px;

    background: #f8fafc;

    border: 1px solid #eef2f7;

    border-radius: 11px;
}


.detail-label {

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: #94a3b8;

    font-weight: 700;

    margin-bottom: 6px;
}


.detail-value {

    font-size: 12px;

    color: #1e293b;

    font-weight: 700;

    word-break: break-word;
}


/* =========================================================
   ELIGIBILITY
========================================================= */

.eligibility-item {

    display: flex;

    align-items: flex-start;

    gap: 13px;

    padding: 13px 0;

    border-bottom: 1px solid #f1f5f9;
}


.eligibility-item:last-child {

    border-bottom: none;

    padding-bottom: 0;
}


.eligibility-icon {

    width: 35px;
    height: 35px;

    flex-shrink: 0;

    border-radius: 9px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #eff6ff;

    color: #2563eb;

    font-size: 12px;
}


.eligibility-label {

    font-size: 9px;

    color: #94a3b8;

    text-transform: uppercase;

    font-weight: 700;

    margin-bottom: 4px;
}


.eligibility-value {

    font-size: 12px;

    font-weight: 700;

    color: #334155;
}


/* =========================================================
   DEADLINE
========================================================= */

.deadline-box {

    padding: 17px;

    border-radius: 12px;

    background: #eff6ff;

    border: 1px solid #dbeafe;

    margin-bottom: 18px;
}


.deadline-box.soon {

    background: #fff7ed;

    border-color: #fed7aa;
}


.deadline-box.expired {

    background: #fef2f2;

    border-color: #fecaca;
}


.deadline-label {

    font-size: 9px;

    text-transform: uppercase;

    color: #64748b;

    font-weight: 700;

    margin-bottom: 5px;
}


.deadline-date {

    font-size: 16px;

    font-weight: 800;

    color: #1e3a8a;
}


.deadline-box.soon .deadline-date {

    color: #c2410c;
}


.deadline-box.expired .deadline-date {

    color: #b91c1c;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.action-buttons {

    display: flex;

    flex-direction: column;

    gap: 9px;
}


.action-btn {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 11px 15px;

    border-radius: 9px;

    font-size: 11px;

    font-weight: 700;

    text-decoration: none;

    transition: .2s;
}


.btn-edit {

    background: #2563eb;

    color: white;
}


.btn-edit:hover {

    background: #1d4ed8;

    color: white;
}


.btn-applicants {

    background: #eff6ff;

    color: #1d4ed8;

    border: 1px solid #dbeafe;
}


.btn-applicants:hover {

    background: #dbeafe;

    color: #1e40af;
}


.btn-back {

    background: white;

    color: #64748b;

    border: 1px solid #dbe2ea;
}


.btn-back:hover {

    background: #f8fafc;

    color: #334155;
}


/* =========================================================
   COMPANY
========================================================= */

.company-box {

    text-align: center;
}


.company-logo {

    width: 65px;
    height: 65px;

    border-radius: 15px;

    margin: 0 auto 13px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #eff6ff;

    color: #2563eb;

    font-size: 24px;

    font-weight: 800;

    overflow: hidden;
}


.company-logo img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}


.company-title {

    font-size: 15px;

    font-weight: 800;

    margin-bottom: 5px;
}


.company-industry {

    font-size: 10px;

    color: #64748b;

    margin-bottom: 17px;
}


.company-contact {

    text-align: left;

    padding-top: 15px;

    border-top: 1px solid #eef2f7;
}


.contact-item {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 12px;

    font-size: 10px;

    color: #64748b;
}


.contact-item i {

    width: 17px;

    color: #2563eb;

    margin-top: 2px;
}


.contact-item a {

    color: #475569;

    text-decoration: none;

    word-break: break-word;
}


.contact-item a:hover {

    color: #2563eb;
}


/* =========================================================
   COMPANY DESCRIPTION
========================================================= */

.company-description {

    font-size: 10px;

    color: #64748b;

    line-height: 1.7;

    text-align: left;

    margin-top: 15px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 950px) {

    .layout {

        grid-template-columns: 1fr;
    }

}


@media (max-width: 800px) {

    .sidebar {

        width: 70px;

        padding: 20px 10px;
    }


    .logo span,
    .nav-link span {

        display: none;
    }


    .logo {

        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }


    .nav-link {

        justify-content: center;
    }


    .main {

        margin-left: 70px;
    }

}


@media (max-width: 600px) {

    .content {

        padding: 20px 15px;
    }


    .topbar {

        padding: 0 15px;
    }


    .job-header {

        padding: 25px 20px;
    }


    .job-title {

        font-size: 23px;

        padding-right: 50px;
    }


    .status-badge {

        right: 20px;

        top: 20px;
    }


    .detail-grid {

        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">

        <div class="logo-icon">

            <i class="fa-solid fa-building"></i>

        </div>

        <span>
            Smart Placement
        </span>

    </div>


    <nav>


        <a
            href="dashboard.php"
            class="nav-link"
        >

            <i class="fa-solid fa-chart-line"></i>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="company_profile.php"
            class="nav-link"
        >

            <i class="fa-solid fa-building"></i>

            <span>
                Company Profile
            </span>

        </a>


        <a
            href="post_job.php"
            class="nav-link"
        >

            <i class="fa-solid fa-plus"></i>

            <span>
                Post Job
            </span>

        </a>


        <a
            href="manage_jobs.php"
            class="nav-link active"
        >

            <i class="fa-solid fa-briefcase"></i>

            <span>
                Manage Jobs
            </span>

        </a>


        <a
            href="applicants.php?id=<?php echo $job_id; ?>"
            class="nav-link"
        >

            <i class="fa-solid fa-users"></i>

            <span>
                Applicants
            </span>

        </a>


        <a
            href="../logout.php"
            class="nav-link"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </nav>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">

        <div class="topbar-title">

            Job Details

        </div>


        <a
            href="manage_jobs.php"
            class="back-link"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Jobs

        </a>

    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- =================================================
             JOB HEADER
        ================================================= -->

        <div class="job-header">


            <div class="job-header-content">


                <div class="company-name">

                    <?php
                    echo e(
                        $job["company_name"]
                    );
                    ?>

                </div>


                <div class="job-title">

                    <?php
                    echo e(
                        $job["job_title"]
                    );
                    ?>

                </div>


                <div class="job-meta">


                    <div class="meta-pill">

                        <i
                            class="fa-solid <?php
                            echo $job_type_icon;
                            ?>"
                        ></i>

                        <?php
                        echo e(
                            $job["job_type"]
                        );
                        ?>

                    </div>


                    <?php if (!empty($job["location"])): ?>

                        <div class="meta-pill">

                            <i
                                class="fa-solid fa-location-dot"
                            ></i>

                            <?php
                            echo e(
                                $job["location"]
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($job["salary"])): ?>

                        <div class="meta-pill">

                            <i
                                class="fa-solid fa-indian-rupee-sign"
                            ></i>

                            <?php
                            echo e(
                                $job["salary"]
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <div class="meta-pill">

                        <i
                            class="fa-solid fa-users"
                        ></i>

                        <?php
                        echo e(
                            $job["vacancies"]
                        );
                        ?>

                        Vacancies

                    </div>


                </div>


            </div>


            <div
                class="status-badge
                <?php echo e($status_class); ?>"
            >

                <?php

                if ($job["status"] === "Open") {

                    echo "● Open";

                } elseif (
                    $job["status"] === "Closed"
                ) {

                    echo "● Closed";

                } else {

                    echo "● Draft";
                }

                ?>

            </div>


        </div>


        <!-- =================================================
             MAIN LAYOUT
        ================================================= -->

        <div class="layout">


            <!-- =================================================
                 LEFT COLUMN
            ================================================= -->

            <div>


                <!-- DESCRIPTION -->

                <div class="card-box">


                    <div class="card-header">

                        <i
                            class="fa-solid fa-align-left"
                        ></i>

                        Job Description

                    </div>


                    <div class="card-body">

                        <div class="description">

                            <?php

                            echo e(
                                $job["description"]
                            );

                            ?>

                        </div>

                    </div>

                </div>


                <!-- JOB DETAILS -->

                <div class="card-box">


                    <div class="card-header">

                        <i
                            class="fa-solid fa-circle-info"
                        ></i>

                        Job Information

                    </div>


                    <div class="card-body">


                        <div class="detail-grid">


                            <div class="detail-item">

                                <div class="detail-label">
                                    Job Type
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo e(
                                        $job["job_type"]
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Location
                                </div>

                                <div class="detail-value">

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


                            <div class="detail-item">

                                <div class="detail-label">
                                    Salary / Stipend
                                </div>

                                <div class="detail-value">

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


                            <div class="detail-item">

                                <div class="detail-label">
                                    Vacancies
                                </div>

                                <div class="detail-value">

                                    <?php
                                    echo e(
                                        $job["vacancies"]
                                    );
                                    ?>

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Minimum CGPA
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo number_format(
                                        (float)$job["min_cgpa"],
                                        2
                                    );

                                    ?>

                                    / 10

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Posted On
                                </div>

                                <div class="detail-value">

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $job["created_at"]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- ELIGIBILITY -->

                <div class="card-box">


                    <div class="card-header">

                        <i
                            class="fa-solid fa-graduation-cap"
                        ></i>

                        Eligibility Criteria

                    </div>


                    <div class="card-body">


                        <div class="eligibility-item">


                            <div class="eligibility-icon">

                                <i
                                    class="fa-solid fa-star"
                                ></i>

                            </div>


                            <div>

                                <div class="eligibility-label">

                                    Minimum CGPA

                                </div>

                                <div class="eligibility-value">

                                    <?php

                                    echo number_format(
                                        (float)$job["min_cgpa"],
                                        2
                                    );

                                    ?>

                                    / 10

                                </div>

                            </div>


                        </div>


                        <div class="eligibility-item">


                            <div class="eligibility-icon">

                                <i
                                    class="fa-solid fa-building-columns"
                                ></i>

                            </div>


                            <div>

                                <div class="eligibility-label">

                                    Eligible Department

                                </div>

                                <div class="eligibility-value">

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

                                </div>

                            </div>


                        </div>


                        <div class="eligibility-item">


                            <div class="eligibility-icon">

                                <i
                                    class="fa-solid fa-calendar"
                                ></i>

                            </div>


                            <div>

                                <div class="eligibility-label">

                                    Eligible Year

                                </div>

                                <div class="eligibility-value">

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


                        </div>


                    </div>

                </div>


            </div>


            <!-- =================================================
                 RIGHT COLUMN
            ================================================= -->

            <div>


                <!-- DEADLINE -->

                <div
                    class="deadline-box
                    <?php echo e($deadline_class); ?>"
                >

                    <div class="deadline-label">

                        Application Deadline

                    </div>


                    <div class="deadline-date">

                        <?php
                        echo e(
                            $deadline_text
                        );
                        ?>

                    </div>


                    <?php if (
                        $deadline_class === "soon"
                    ): ?>

                        <small>

                            <i
                                class="fa-solid fa-clock"
                            ></i>

                            Deadline is approaching

                        </small>

                    <?php elseif (
                        $deadline_class === "expired"
                    ): ?>

                        <small>

                            <i
                                class="fa-solid fa-circle-exclamation"
                            ></i>

                            Deadline has passed

                        </small>

                    <?php endif; ?>

                </div>


                <!-- ACTIONS -->

                <div class="card-box">


                    <div class="card-header">

                        <i
                            class="fa-solid fa-bolt"
                        ></i>

                        Quick Actions

                    </div>


                    <div class="card-body">


                        <div class="action-buttons">


                            <a
                                href="edit_job.php?id=<?php
                                echo $job_id;
                                ?>"
                                class="action-btn btn-edit"
                            >

                                <i
                                    class="fa-solid fa-pen"
                                ></i>

                                Edit Job

                            </a>


                            <a
                                href="applicants.php?id=<?php
                                echo $job_id;
                                ?>"
                                class="action-btn btn-applicants"
                            >

                                <i
                                    class="fa-solid fa-users"
                                ></i>

                                View Applicants

                            </a>


                            <a
                                href="manage_jobs.php"
                                class="action-btn btn-back"
                            >

                                <i
                                    class="fa-solid fa-arrow-left"
                                ></i>

                                Back to Jobs

                            </a>


                        </div>

                    </div>

                </div>


                <!-- COMPANY -->

                <div class="card-box">


                    <div class="card-header">

                        <i
                            class="fa-solid fa-building"
                        ></i>

                        Company

                    </div>


                    <div class="card-body company-box">


                        <div class="company-logo">


                            <?php

                            if (
                                !empty(
                                    $job["logo"]
                                )
                            ):

                            ?>

                                <img
                                    src="<?php
                                    echo e(
                                        $job["logo"]
                                    );
                                    ?>"
                                    alt="Company Logo"
                                >

                            <?php

                            else:

                                echo strtoupper(
                                    substr(
                                        $job[
                                            "company_name"
                                        ],
                                        0,
                                        1
                                    )
                                );

                            endif;

                            ?>

                        </div>


                        <div class="company-title">

                            <?php

                            echo e(
                                $job["company_name"]
                            );

                            ?>

                        </div>


                        <?php if (
                            !empty(
                                $job["industry"]
                            )
                        ): ?>

                            <div class="company-industry">

                                <?php

                                echo e(
                                    $job["industry"]
                                );

                                ?>

                            </div>

                        <?php endif; ?>


                        <div class="company-contact">


                            <?php if (
                                !empty(
                                    $job["company_email"]
                                )
                            ): ?>

                                <div class="contact-item">

                                    <i
                                        class="fa-solid fa-envelope"
                                    ></i>

                                    <a
                                        href="mailto:<?php
                                        echo e(
                                            $job[
                                                "company_email"
                                            ]
                                        );
                                        ?>"
                                    >

                                        <?php

                                        echo e(
                                            $job[
                                                "company_email"
                                            ]
                                        );

                                        ?>

                                    </a>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $job["phone"]
                                )
                            ): ?>

                                <div class="contact-item">

                                    <i
                                        class="fa-solid fa-phone"
                                    ></i>

                                    <span>

                                        <?php

                                        echo e(
                                            $job["phone"]
                                        );

                                        ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $job["website"]
                                )
                            ): ?>

                                <div class="contact-item">

                                    <i
                                        class="fa-solid fa-globe"
                                    ></i>

                                    <a
                                        href="<?php
                                        echo e(
                                            $job[
                                                "website"
                                            ]
                                        );
                                        ?>"
                                        target="_blank"
                                    >

                                        <?php

                                        echo e(
                                            $job[
                                                "website"
                                            ]
                                        );

                                        ?>

                                    </a>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $job[
                                        "company_location"
                                    ]
                                )
                            ): ?>

                                <div class="contact-item">

                                    <i
                                        class="fa-solid fa-location-dot"
                                    ></i>

                                    <span>

                                        <?php

                                        echo e(
                                            $job[
                                                "company_location"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                        </div>


                        <?php if (
                            !empty(
                                $job[
                                    "company_description"
                                ]
                            )
                        ): ?>

                            <div class="company-description">

                                <?php

                                echo e(
                                    $job[
                                        "company_description"
                                    ]
                                );

                                ?>

                            </div>

                        <?php endif; ?>


                    </div>

                </div>


            </div>


        </div>


    </section>


</main>


</body>

</html>