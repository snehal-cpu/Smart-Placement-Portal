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
   GET JOB ID
========================================================= */

$job_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$job_id) {
    header("Location: jobs.php");
    exit;
}


/* =========================================================
   CURRENT STUDENT
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


if (!$student) {
    die("Student profile not found.");
}


$student_id = (int)$student["id"];


/* =========================================================
   FETCH JOB
========================================================= */

$stmt = mysqli_prepare(
    $conn,

    "SELECT
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

        c.id AS company_id,
        c.company_name,
        c.industry,
        c.description AS company_description,
        c.website,
        c.logo

     FROM jobs j

     INNER JOIN companies c
        ON j.company_id = c.id

     WHERE j.id = ?

     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $job_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$job = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$job) {
    header("Location: jobs.php");
    exit;
}


/* =========================================================
   CHECK APPLICATION
========================================================= */

$application = null;

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        status,
        applied_at,
        updated_at
     FROM applications
     WHERE student_id = ?
       AND job_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $student_id,
    $job_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$application = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   APPLY FOR JOB
========================================================= */

$message = "";
$message_type = "";


if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["apply_job"])
) {

    /* Prevent duplicate applications */

    if ($application) {

        $message =
            "You have already applied for this job.";

        $message_type = "warning";

    } else {

        /* Check job status */

        if ($job["status"] !== "Open") {

            $message =
                "This job is currently closed.";

            $message_type = "danger";

        } else {

            /* Check deadline */

            $deadline_passed = false;

            if (
                !empty(
                    $job["application_deadline"]
                )
            ) {

                $deadline_passed =
                    strtotime(
                        $job[
                            "application_deadline"
                        ]
                    ) < time();
            }


            if ($deadline_passed) {

                $message =
                    "The application deadline has passed.";

                $message_type = "danger";

            } else {

                /* Check CGPA */

                $student_cgpa =
                    (float)$student["cgpa"];

                $required_cgpa =
                    (float)$job["min_cgpa"];


                if (
                    $student_cgpa <
                    $required_cgpa
                ) {

                    $message =
                        "You are not eligible for this job because your CGPA is below the required CGPA.";

                    $message_type = "danger";

                } else {

                    /* Check department */

                    $eligible_department =
                        trim(
                            (string)
                            $job[
                                "eligible_department"
                            ]
                        );


                    $department_allowed = true;


                    if (
                        $eligible_department !== "" &&
                        strcasecmp(
                            $eligible_department,
                            "All Departments"
                        ) !== 0
                    ) {

                        $department_allowed =
                            strcasecmp(
                                trim(
                                    $student[
                                        "department"
                                    ]
                                ),
                                $eligible_department
                            ) === 0;
                    }


                    if (
                        !$department_allowed
                    ) {

                        $message =
                            "You are not eligible for this job based on your department.";

                        $message_type = "danger";

                    } else {

                        /* Insert application */

                        $insert = mysqli_prepare(
                            $conn,

                            "INSERT INTO applications
                            (
                                student_id,
                                job_id,
                                status,
                                applied_at
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                'Applied',
                                CURRENT_TIMESTAMP
                            )"
                        );


                        mysqli_stmt_bind_param(
                            $insert,
                            "ii",
                            $student_id,
                            $job_id
                        );


                        if (
                            mysqli_stmt_execute(
                                $insert
                            )
                        ) {

                            $message =
                                "Application submitted successfully!";

                            $message_type =
                                "success";


                            /* Refresh application */

                            $application = [
                                "id" =>
                                    mysqli_insert_id(
                                        $conn
                                    ),

                                "status" =>
                                    "Applied",

                                "applied_at" =>
                                    date(
                                        "Y-m-d H:i:s"
                                    ),

                                "updated_at" =>
                                    date(
                                        "Y-m-d H:i:s"
                                    )
                            ];

                        } else {

                            $message =
                                "Unable to submit your application. Please try again.";

                            $message_type =
                                "danger";
                        }


                        mysqli_stmt_close(
                            $insert
                        );
                    }
                }
            }
        }
    }
}


/* =========================================================
   ELIGIBILITY
========================================================= */

$student_cgpa =
    (float)$student["cgpa"];

$required_cgpa =
    (float)$job["min_cgpa"];


$cgpa_eligible =
    $student_cgpa >= $required_cgpa;


$eligible_department =
    trim(
        (string)
        $job["eligible_department"]
    );


$department_eligible = true;


if (
    $eligible_department !== "" &&
    strcasecmp(
        $eligible_department,
        "All Departments"
    ) !== 0
) {

    $department_eligible =
        strcasecmp(
            trim(
                $student["department"]
            ),
            $eligible_department
        ) === 0;
}


$deadline_passed = false;

if (
    !empty(
        $job["application_deadline"]
    )
) {

    $deadline_passed =
        strtotime(
            $job["application_deadline"]
        ) < time();
}


$can_apply =
    $job["status"] === "Open" &&
    !$deadline_passed &&
    !$application &&
    $cgpa_eligible &&
    $department_eligible;


/* =========================================================
   STUDENT INITIAL
========================================================= */

$student_name =
    $student["full_name"];

$initial =
    strtoupper(
        substr(
            trim($student_name),
            0,
            1
        )
    );


/* =========================================================
   COMPANY INITIAL
========================================================= */

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


/* =========================================================
   DEADLINE
========================================================= */

$deadline = "Not specified";

if (
    !empty(
        $job["application_deadline"]
    )
) {

    $deadline =
        date(
            "d M Y",
            strtotime(
                $job["application_deadline"]
            )
        );
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
    <?php echo e($job["job_title"]); ?>
    | Smart Placement Portal
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


.page-title {

    font-size: 19px;

    font-weight: 800;
}


.page-subtitle {

    font-size: 10px;

    color: #94a3b8;

    margin-top: 3px;
}


.profile {

    display: flex;

    align-items: center;

    gap: 10px;
}


.avatar {

    width: 36px;
    height: 36px;

    border-radius: 10px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 11px;

    font-weight: 800;
}


.profile-name {

    font-size: 10px;

    font-weight: 700;

    color: #334155;
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width: 1250px;

    margin: auto;

    padding: 30px 35px;
}


/* =========================================================
   BACK
========================================================= */

.back-link {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #64748b;

    text-decoration: none;

    font-size: 10px;

    font-weight: 700;

    margin-bottom: 18px;
}


.back-link:hover {

    color: #2563eb;
}


/* =========================================================
   ALERT
========================================================= */

.custom-alert {

    border-radius: 12px;

    border: 1px solid;

    padding: 13px 15px;

    font-size: 10px;

    font-weight: 600;

    margin-bottom: 18px;
}


.alert-success-custom {

    background: #ecfdf5;

    color: #047857;

    border-color: #a7f3d0;
}


.alert-warning-custom {

    background: #fffbeb;

    color: #b45309;

    border-color: #fde68a;
}


.alert-danger-custom {

    background: #fef2f2;

    color: #b91c1c;

    border-color: #fecaca;
}


/* =========================================================
   LAYOUT
========================================================= */

.job-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        330px;

    gap: 20px;

    align-items: start;
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

    border-radius: 17px;

    padding: 28px;

    margin-bottom: 18px;

    position: relative;

    overflow: hidden;
}


.job-header::after {

    content: "";

    position: absolute;

    width: 250px;
    height: 250px;

    border-radius: 50%;

    right: -100px;
    top: -120px;

    background: rgba(
        255,
        255,
        255,
        .05
    );
}


.job-header-content {

    position: relative;

    z-index: 2;
}


.company-row {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 20px;
}


.company-logo {

    width: 50px;
    height: 50px;

    border-radius: 13px;

    background: white;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: 800;

    font-size: 16px;

    overflow: hidden;
}


.company-logo img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}


.company-name {

    font-size: 11px;

    color: #cbd5e1;

    font-weight: 600;
}


.industry {

    font-size: 8px;

    color: #94a3b8;

    margin-top: 3px;
}


.job-title {

    font-size: 25px;

    font-weight: 800;

    line-height: 1.3;

    margin-bottom: 12px;
}


.job-type {

    display: inline-flex;

    padding: 6px 10px;

    border-radius: 20px;

    background: rgba(
        255,
        255,
        255,
        .1
    );

    border: 1px solid rgba(
        255,
        255,
        255,
        .12
    );

    font-size: 8px;

    font-weight: 700;
}


/* =========================================================
   CONTENT CARD
========================================================= */

.card {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 15px;

    padding: 23px;

    margin-bottom: 18px;
}


.card-title {

    font-size: 13px;

    font-weight: 800;

    color: #1e293b;

    margin-bottom: 15px;
}


.description {

    color: #64748b;

    font-size: 10px;

    line-height: 1.8;

    white-space: pre-line;
}


/* =========================================================
   DETAILS GRID
========================================================= */

.details-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 15px;
}


.detail-box {

    border: 1px solid #eef2f7;

    background: #fafcff;

    border-radius: 11px;

    padding: 14px;
}


.detail-icon {

    width: 30px;
    height: 30px;

    border-radius: 8px;

    background: #eff6ff;

    color: #2563eb;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 9px;

    margin-bottom: 9px;
}


.detail-label {

    font-size: 8px;

    color: #94a3b8;

    font-weight: 700;

    margin-bottom: 4px;
}


.detail-value {

    color: #334155;

    font-size: 10px;

    font-weight: 700;
}


/* =========================================================
   ELIGIBILITY
========================================================= */

.eligibility-list {

    display: grid;

    gap: 10px;
}


.eligibility-item {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px 12px;

    background: #f8fafc;

    border-radius: 9px;

    font-size: 9px;

    color: #475569;
}


.eligibility-item i {

    width: 22px;
    height: 22px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 8px;
}


.eligibility-item strong {

    color: #334155;
}


/* =========================================================
   RIGHT PANEL
========================================================= */

.apply-card {

    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 15px;

    padding: 22px;

    position: sticky;

    top: 20px;
}


.apply-title {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 5px;
}


.apply-subtitle {

    font-size: 9px;

    color: #94a3b8;

    margin-bottom: 20px;
}


.apply-info {

    display: grid;

    gap: 11px;

    margin-bottom: 18px;
}


.apply-info-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    font-size: 9px;
}


.apply-info-label {

    color: #94a3b8;
}


.apply-info-value {

    color: #334155;

    font-weight: 700;

    text-align: right;
}


.apply-btn {

    width: 100%;

    border: none;

    border-radius: 10px;

    padding: 12px;

    background: #2563eb;

    color: white;

    font-size: 10px;

    font-weight: 800;

    cursor: pointer;

    transition: .2s;
}


.apply-btn:hover {

    background: #1d4ed8;
}


.apply-btn:disabled {

    background: #cbd5e1;

    cursor: not-allowed;
}


.applied-box {

    text-align: center;

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #a7f3d0;

    border-radius: 10px;

    padding: 13px;

    font-size: 10px;

    font-weight: 800;
}


.applied-box small {

    display: block;

    color: #059669;

    font-size: 8px;

    margin-top: 4px;

    font-weight: 500;
}


/* =========================================================
   ELIGIBILITY WARNING
========================================================= */

.warning-box {

    background: #fff7ed;

    border: 1px solid #fed7aa;

    color: #c2410c;

    border-radius: 10px;

    padding: 12px;

    margin-bottom: 13px;

    font-size: 8px;

    line-height: 1.6;
}


.warning-box i {

    margin-right: 5px;
}


/* =========================================================
   COMPANY
========================================================= */

.company-description {

    font-size: 10px;

    color: #64748b;

    line-height: 1.8;

    white-space: pre-line;
}


.website-link {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 12px;

    color: #2563eb;

    text-decoration: none;

    font-size: 9px;

    font-weight: 700;
}


.website-link:hover {

    color: #1d4ed8;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .job-layout {

        grid-template-columns: 1fr;
    }


    .apply-card {

        position: static;
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


    .content {

        padding: 25px 15px;
    }


    .topbar {

        padding: 0 15px;
    }

}


@media (max-width: 550px) {

    .details-grid {

        grid-template-columns: 1fr;
    }


    .job-title {

        font-size: 20px;
    }


    .profile-name {

        display: none;
    }

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

                Job Details

            </div>

            <div class="page-subtitle">

                Review the opportunity before applying

            </div>

        </div>


        <div class="profile">


            <div class="avatar">

                <?php
                echo e($initial);
                ?>

            </div>


            <div class="profile-name">

                <?php
                echo e($student_name);
                ?>

            </div>


        </div>


    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- BACK -->

        <a
            href="jobs.php"
            class="back-link"
        >

            <i
                class="fa-solid fa-arrow-left"
            ></i>

            Back to Jobs

        </a>


        <!-- MESSAGE -->

        <?php if (
            $message !== ""
        ): ?>


            <div
                class="custom-alert
                <?php

                if (
                    $message_type ===
                    "success"
                ) {

                    echo "alert-success-custom";

                } elseif (
                    $message_type ===
                    "warning"
                ) {

                    echo "alert-warning-custom";

                } else {

                    echo "alert-danger-custom";
                }

                ?>"
            >

                <?php

                if (
                    $message_type ===
                    "success"
                ) {

                    echo '<i class="fa-solid fa-circle-check"></i>';

                } elseif (
                    $message_type ===
                    "warning"
                ) {

                    echo '<i class="fa-solid fa-triangle-exclamation"></i>';

                } else {

                    echo '<i class="fa-solid fa-circle-exclamation"></i>';
                }

                ?>

                &nbsp;

                <?php
                echo e($message);
                ?>

            </div>


        <?php endif; ?>


        <div class="job-layout">


            <!-- =================================================
                 LEFT
            ================================================= -->

            <div>


                <!-- JOB HEADER -->

                <div class="job-header">


                    <div
                        class="job-header-content"
                    >


                        <div class="company-row">


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


                            <div>

                                <div
                                    class="company-name"
                                >

                                    <?php
                                    echo e(
                                        $company_name
                                    );
                                    ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $job["industry"]
                                    )
                                ): ?>

                                    <div
                                        class="industry"
                                    >

                                        <?php
                                        echo e(
                                            $job["industry"]
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>


                            </div>


                        </div>


                        <div class="job-title">

                            <?php
                            echo e(
                                $job["job_title"]
                            );
                            ?>

                        </div>


                        <span class="job-type">

                            <?php
                            echo e(
                                $job["job_type"]
                            );
                            ?>

                        </span>


                    </div>


                </div>


                <!-- DESCRIPTION -->

                <div class="card">


                    <div class="card-title">

                        <i
                            class="fa-solid fa-align-left"
                        ></i>

                        &nbsp;

                        Job Description

                    </div>


                    <div class="description">

                        <?php

                        echo !empty(
                            $job["description"]
                        )
                            ? e(
                                $job["description"]
                            )
                            : "No job description provided.";

                        ?>

                    </div>


                </div>


                <!-- JOB DETAILS -->

                <div class="card">


                    <div class="card-title">

                        Job Information

                    </div>


                    <div class="details-grid">


                        <div class="detail-box">


                            <div class="detail-icon">

                                <i
                                    class="fa-solid fa-location-dot"
                                ></i>

                            </div>


                            <div
                                class="detail-label"
                            >

                                Location

                            </div>


                            <div
                                class="detail-value"
                            >

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


                        <div class="detail-box">


                            <div class="detail-icon">

                                <i
                                    class="fa-solid fa-indian-rupee-sign"
                                ></i>

                            </div>


                            <div
                                class="detail-label"
                            >

                                Salary / Package

                            </div>


                            <div
                                class="detail-value"
                            >

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


                        <div class="detail-box">


                            <div class="detail-icon">

                                <i
                                    class="fa-solid fa-users"
                                ></i>

                            </div>


                            <div
                                class="detail-label"
                            >

                                Vacancies

                            </div>


                            <div
                                class="detail-value"
                            >

                                <?php
                                echo e(
                                    $job["vacancies"]
                                );
                                ?>

                            </div>


                        </div>


                        <div class="detail-box">


                            <div class="detail-icon">

                                <i
                                    class="fa-solid fa-calendar-days"
                                ></i>

                            </div>


                            <div
                                class="detail-label"
                            >

                                Application Deadline

                            </div>


                            <div
                                class="detail-value"
                            >

                                <?php
                                echo e(
                                    $deadline
                                );
                                ?>

                            </div>


                        </div>


                    </div>


                </div>


                <!-- ELIGIBILITY -->

                <div class="card">


                    <div class="card-title">

                        <i
                            class="fa-solid fa-graduation-cap"
                        ></i>

                        &nbsp;

                        Eligibility Criteria

                    </div>


                    <div class="eligibility-list">


                        <div
                            class="eligibility-item"
                        >

                            <i
                                class="fa-solid fa-star"
                            ></i>

                            <span>

                                Minimum CGPA:

                                <strong>

                                    <?php
                                    echo number_format(
                                        $required_cgpa,
                                        2
                                    );
                                    ?>

                                </strong>

                            </span>

                        </div>


                        <div
                            class="eligibility-item"
                        >

                            <i
                                class="fa-solid fa-code-branch"
                            ></i>

                            <span>

                                Department:

                                <strong>

                                    <?php

                                    echo !empty(
                                        $eligible_department
                                    )
                                        ? e(
                                            $eligible_department
                                        )
                                        : "All Departments";

                                    ?>

                                </strong>

                            </span>

                        </div>


                        <div
                            class="eligibility-item"
                        >

                            <i
                                class="fa-solid fa-user-graduate"
                            ></i>

                            <span>

                                Eligible Year:

                                <strong>

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

                                </strong>

                            </span>

                        </div>


                    </div>


                </div>


                <!-- COMPANY -->

                <div class="card">


                    <div class="card-title">

                        About the Company

                    </div>


                    <div class="company-description">

                        <?php

                        echo !empty(
                            $job[
                                "company_description"
                            ]
                        )
                            ? e(
                                $job[
                                    "company_description"
                                ]
                            )
                            : "Company information is not available.";

                        ?>

                    </div>


                    <?php if (
                        !empty(
                            $job["website"]
                        )
                    ): ?>


                        <a
                            href="<?php
                            echo e(
                                $job["website"]
                            );
                            ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="website-link"
                        >

                            <i
                                class="fa-solid fa-globe"
                            ></i>

                            Visit Company Website

                        </a>


                    <?php endif; ?>


                </div>


            </div>


            <!-- =================================================
                 RIGHT
            ================================================= -->

            <aside class="apply-card">


                <div class="apply-title">

                    Apply for this Job

                </div>


                <div class="apply-subtitle">

                    Check your eligibility before
                    submitting your application.

                </div>


                <div class="apply-info">


                    <div class="apply-info-row">

                        <span
                            class="apply-info-label"
                        >
                            Your CGPA
                        </span>

                        <span
                            class="apply-info-value"
                        >

                            <?php
                            echo number_format(
                                $student_cgpa,
                                2
                            );
                            ?>

                        </span>

                    </div>


                    <div class="apply-info-row">

                        <span
                            class="apply-info-label"
                        >
                            Required CGPA
                        </span>

                        <span
                            class="apply-info-value"
                        >

                            <?php
                            echo number_format(
                                $required_cgpa,
                                2
                            );
                            ?>

                        </span>

                    </div>


                    <div class="apply-info-row">

                        <span
                            class="apply-info-label"
                        >
                            Your Department
                        </span>

                        <span
                            class="apply-info-value"
                        >

                            <?php
                            echo e(
                                $student[
                                    "department"
                                ]
                            );
                            ?>

                        </span>

                    </div>


                    <div class="apply-info-row">

                        <span
                            class="apply-info-label"
                        >
                            Deadline
                        </span>

                        <span
                            class="apply-info-value"
                        >

                            <?php
                            echo e(
                                $deadline
                            );
                            ?>

                        </span>

                    </div>


                </div>


                <!-- ALREADY APPLIED -->

                <?php if (
                    $application
                ): ?>


                    <div class="applied-box">


                        <i
                            class="fa-solid fa-circle-check"
                        ></i>

                        Already Applied


                        <small>

                            Status:

                            <?php
                            echo e(
                                $application[
                                    "status"
                                ]
                            );
                            ?>

                        </small>


                    </div>


                <?php elseif (
                    !$cgpa_eligible
                ): ?>


                    <div class="warning-box">

                        <i
                            class="fa-solid fa-triangle-exclamation"
                        ></i>

                        Your CGPA does not meet
                        the minimum requirement
                        for this job.

                    </div>


                    <button
                        class="apply-btn"
                        disabled
                    >

                        Not Eligible

                    </button>


                <?php elseif (
                    !$department_eligible
                ): ?>


                    <div class="warning-box">

                        <i
                            class="fa-solid fa-triangle-exclamation"
                        ></i>

                        Your department does not
                        match the eligibility
                        criteria for this job.

                    </div>


                    <button
                        class="apply-btn"
                        disabled
                    >

                        Not Eligible

                    </button>


                <?php elseif (
                    $deadline_passed
                ): ?>


                    <div class="warning-box">

                        <i
                            class="fa-solid fa-clock"
                        ></i>

                        The application deadline
                        has passed.

                    </div>


                    <button
                        class="apply-btn"
                        disabled
                    >

                        Applications Closed

                    </button>


                <?php elseif (
                    $job["status"] !== "Open"
                ): ?>


                    <div class="warning-box">

                        <i
                            class="fa-solid fa-lock"
                        ></i>

                        This job is currently
                        closed.

                    </div>


                    <button
                        class="apply-btn"
                        disabled
                    >

                        Job Closed

                    </button>


                <?php else: ?>


                    <form
                        method="POST"
                        onsubmit="return confirmApply();"
                    >

                        <button
                            type="submit"
                            name="apply_job"
                            class="apply-btn"
                        >

                            <i
                                class="fa-solid fa-paper-plane"
                            ></i>

                            &nbsp;

                            Apply Now

                        </button>

                    </form>


                <?php endif; ?>


            </aside>


        </div>


    </section>


</main>


<script>

function confirmApply()
{
    return confirm(
        "Are you sure you want to apply for this job?"
    );
}

</script>


</body>

</html>