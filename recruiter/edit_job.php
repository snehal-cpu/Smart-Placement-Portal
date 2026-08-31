<?php

require_once "../includes/auth.php";
requireRole("recruiter");

require_once "../config/database.php";


$user_id = $_SESSION["user_id"];


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| COMPANY LOGO URL
|--------------------------------------------------------------------------
| Supports either:
|
| logo_123.png
| uploads/company_logos/logo_123.png
|
*/

function getCompanyLogoUrl($logo)
{
    if (empty($logo)) {
        return "";
    }


    /*
     * If database already stores uploads/... path
     */

    if (
        strpos(
            $logo,
            "uploads/"
        ) === 0
    ) {

        return "../" . $logo;
    }


    /*
     * Otherwise database stores only filename
     */

    return
        "../uploads/company_logos/" .
        rawurlencode(
            basename($logo)
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

    header(
        "Location: manage_jobs.php"
    );

    exit;
}


/* =========================================================
   FETCH JOB + COMPANY
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

        c.company_name,
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


mysqli_stmt_execute(
    $stmt
);


$result = mysqli_stmt_get_result(
    $stmt
);


$job = mysqli_fetch_assoc(
    $result
);


if (!$job) {

    header(
        "Location: manage_jobs.php"
    );

    exit;
}


/* =========================================================
   COMPANY LOGO
========================================================= */

$companyLogoUrl =
    getCompanyLogoUrl(
        $job["logo"] ?? ""
    );


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$success = "";


/* =========================================================
   UPDATE JOB
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $job_title =
        trim(
            $_POST["job_title"] ?? ""
        );


    $description =
        trim(
            $_POST["description"] ?? ""
        );


    $job_type =
        trim(
            $_POST["job_type"] ?? ""
        );


    $location =
        trim(
            $_POST["location"] ?? ""
        );


    $salary =
        trim(
            $_POST["salary"] ?? ""
        );


    $min_cgpa =
        trim(
            $_POST["min_cgpa"] ?? ""
        );


    $eligible_department =
        trim(
            $_POST["eligible_department"] ?? ""
        );


    $eligible_year =
        trim(
            $_POST["eligible_year"] ?? ""
        );


    $application_deadline =
        trim(
            $_POST["application_deadline"] ?? ""
        );


    $vacancies =
        trim(
            $_POST["vacancies"] ?? ""
        );


    $status =
        trim(
            $_POST["status"] ?? ""
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($job_title === "") {

        $error =
            "Job title is required.";

    }

    elseif ($description === "") {

        $error =
            "Job description is required.";

    }

    elseif (

        !in_array(

            $job_type,

            [
                "Full Time",
                "Part Time",
                "Internship"
            ],

            true
        )

    ) {

        $error =
            "Please select a valid job type.";

    }

    elseif ($location === "") {

        $error =
            "Location is required.";

    }

    elseif (

        $min_cgpa === "" ||

        !is_numeric(
            $min_cgpa
        )

    ) {

        $error =
            "Please enter a valid CGPA.";

    }

    elseif (

        (float)$min_cgpa < 0 ||

        (float)$min_cgpa > 10

    ) {

        $error =
            "Minimum CGPA must be between 0 and 10.";

    }

    elseif (

        $vacancies === "" ||

        !filter_var(
            $vacancies,
            FILTER_VALIDATE_INT
        ) ||

        (int)$vacancies < 1

    ) {

        $error =
            "Vacancies must be at least 1.";

    }

    elseif (

        !in_array(

            $status,

            [
                "Open",
                "Closed",
                "Draft"
            ],

            true
        )

    ) {

        $error =
            "Please select a valid status.";

    }

    elseif (

        $application_deadline !== "" &&

        strtotime(
            $application_deadline
        ) === false

    ) {

        $error =
            "Please enter a valid application deadline.";

    }


    /* =====================================================
       DEADLINE VALIDATION
    ===================================================== */

    if (

        $error === "" &&

        $application_deadline !== ""

    ) {

        $deadlineTimestamp =
            strtotime(
                $application_deadline
            );


        if (

            $status === "Open" &&

            $deadlineTimestamp < strtotime(
                date("Y-m-d")
            )

        ) {

            $error =
                "An open job cannot have a past application deadline.";

        }

    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === "") {

        $min_cgpa_value =
            number_format(

                (float)$min_cgpa,

                2,

                ".",

                ""
            );


        $vacancies_value =
            (int)$vacancies;


        $stmt = mysqli_prepare(

            $conn,

            "UPDATE jobs

             SET

                job_title = ?,
                description = ?,
                job_type = ?,
                location = ?,
                salary = ?,
                min_cgpa = ?,
                eligible_department = ?,
                eligible_year = ?,
                application_deadline = NULLIF(?, ''),
                vacancies = ?,
                status = ?

             WHERE id = ?

               AND company_id = ?

             LIMIT 1"
        );


        mysqli_stmt_bind_param(

            $stmt,

            "sssssssssisii",

            $job_title,
            $description,
            $job_type,
            $location,
            $salary,
            $min_cgpa_value,
            $eligible_department,
            $eligible_year,
            $application_deadline,
            $vacancies_value,
            $status,
            $job_id,
            $job["company_id"]
        );


        if (

            mysqli_stmt_execute(
                $stmt
            )

        ) {

            $success =
                "Job updated successfully.";


            /*
             * Refresh page values
             */

            $job["job_title"] =
                $job_title;

            $job["description"] =
                $description;

            $job["job_type"] =
                $job_type;

            $job["location"] =
                $location;

            $job["salary"] =
                $salary;

            $job["min_cgpa"] =
                $min_cgpa_value;

            $job["eligible_department"] =
                $eligible_department;

            $job["eligible_year"] =
                $eligible_year;

            $job["application_deadline"] =
                $application_deadline;

            $job["vacancies"] =
                $vacancies_value;

            $job["status"] =
                $status;

        }

        else {

            $error =
                "Unable to update job. Please try again.";

        }

    }

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
    Edit Job | Smart Placement Portal
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

    box-sizing:
        border-box;

}


body {

    margin:
        0;

    font-family:
        "Inter",
        sans-serif;

    background:
        #f8fafc;

    color:
        #0f172a;

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position:
        fixed;

    left:
        0;

    top:
        0;

    width:
        250px;

    height:
        100vh;

    background:
        #0f172a;

    color:
        white;

    padding:
        25px 18px;

    z-index:
        1000;

}


.logo {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        10px 12px 30px;

    font-size:
        19px;

    font-weight:
        800;

}


.logo-icon {

    width:
        40px;

    height:
        40px;

    border-radius:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    overflow:
        hidden;

    flex-shrink:
        0;

}


.company-logo-img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    display:
        block;

}


.nav-link {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        12px 15px;

    margin-bottom:
        5px;

    border-radius:
        10px;

    color:
        #94a3b8;

    text-decoration:
        none;

    transition:
        .2s;

}


.nav-link:hover {

    color:
        white;

    background:
        #1e293b;

}


.nav-link.active {

    color:
        white;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:
        250px;

    min-height:
        100vh;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height:
        75px;

    background:
        white;

    border-bottom:
        1px solid #e2e8f0;

    padding:
        0 35px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

}


.topbar-title {

    font-size:
        18px;

    font-weight:
        700;

}


.back-link {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    color:
        #64748b;

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        600;

}


.back-link:hover {

    color:
        #2563eb;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1050px;

    padding:
        35px;

    margin:
        auto;

}


.page-heading {

    margin-bottom:
        25px;

}


.page-heading h2 {

    font-size:
        27px;

    font-weight:
        800;

    margin-bottom:
        7px;

}


.page-heading p {

    margin:
        0;

    color:
        #64748b;

    font-size:
        13px;

}


/* =========================================================
   COMPANY INFORMATION
========================================================= */

.company-info {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    background:
        #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius:
        14px;

    padding:
        14px 17px;

    margin-bottom:
        20px;

    font-size:
        12px;

    color:
        #1e40af;

}


.company-info-logo {

    width:
        42px;

    height:
        42px;

    border-radius:
        10px;

    overflow:
        hidden;

    background:
        white;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    flex-shrink:
        0;

}


.company-info-logo img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.company-info-logo i {

    color:
        #2563eb;

}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    background:
        white;

    border:
        1px solid #e2e8f0;

    border-radius:
        17px;

    overflow:
        hidden;

    margin-bottom:
        20px;

}


.form-section {

    padding:
        25px;

    border-bottom:
        1px solid #eef2f7;

}


.form-section:last-child {

    border-bottom:
        none;

}


.section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    font-size:
        14px;

    font-weight:
        800;

    margin-bottom:
        20px;

}


.section-icon {

    width:
        34px;

    height:
        34px;

    border-radius:
        9px;

    background:
        #eff6ff;

    color:
        #2563eb;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        12px;

}


/* =========================================================
   FORM
========================================================= */

.form-label {

    font-size:
        11px;

    font-weight:
        700;

    color:
        #334155;

    margin-bottom:
        7px;

}


.required {

    color:
        #dc2626;

}


.form-control,
.form-select {

    min-height:
        44px;

    border:
        1px solid #dbe2ea;

    border-radius:
        9px;

    font-size:
        12px;

}


textarea.form-control {

    min-height:
        130px;

    resize:
        vertical;

}


.form-control:focus,
.form-select:focus {

    border-color:
        #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.1);

}


.form-text {

    font-size:
        10px;

    color:
        #94a3b8;

}


/* =========================================================
   BUTTONS
========================================================= */

.form-actions {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    padding:
        20px 25px;

    background:
        #fafafa;

}


.btn-cancel {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    color:
        #64748b;

    border:
        1px solid #dbe2ea;

    background:
        white;

    padding:
        11px 18px;

    border-radius:
        9px;

    font-size:
        12px;

    font-weight:
        700;

    text-decoration:
        none;

}


.btn-cancel:hover {

    color:
        #334155;

    background:
        #f8fafc;

}


.btn-save {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    border:
        none;

    color:
        white;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    padding:
        11px 21px;

    border-radius:
        9px;

    font-size:
        12px;

    font-weight:
        700;

    cursor:
        pointer;

}


.btn-save:hover {

    box-shadow:
        0 8px 20px
        rgba(37,99,235,.2);

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    border-radius:
        11px;

    font-size:
        12px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .sidebar {

        width:
            70px;

        padding:
            20px 10px;

    }


    .logo span,
    .nav-link span {

        display:
            none;

    }


    .logo {

        justify-content:
            center;

        padding-left:
            0;

        padding-right:
            0;

    }


    .nav-link {

        justify-content:
            center;

    }


    .main {

        margin-left:
            70px;

    }

}


@media (max-width: 650px) {

    .content {

        padding:
            20px 15px;

    }


    .topbar {

        padding:
            0 15px;

    }


    .form-section {

        padding:
            20px 17px;

    }


    .form-actions {

        padding:
            17px;

        flex-direction:
            column-reverse;

        align-items:
            stretch;

    }


    .btn-cancel,
    .btn-save {

        justify-content:
            center;

        width:
            100%;

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


            <?php if ($companyLogoUrl !== ""): ?>


                <img
                    src="<?php echo e($companyLogoUrl); ?>"
                    alt="Company Logo"
                    class="company-logo-img"
                >


            <?php else: ?>


                <i class="fa-solid fa-building"></i>


            <?php endif; ?>


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
            href="applicants.php"
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

            Edit Job

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


        <!-- HEADING -->

        <div class="page-heading">

            <h2>
                Edit Job Opportunity
            </h2>

            <p>
                Update the details of your job posting.
            </p>

        </div>


        <!-- COMPANY INFO -->

        <div class="company-info">


            <div class="company-info-logo">


                <?php if ($companyLogoUrl !== ""): ?>


                    <img
                        src="<?php echo e($companyLogoUrl); ?>"
                        alt="<?php echo e($job["company_name"]); ?>"
                    >


                <?php else: ?>


                    <i class="fa-solid fa-building"></i>


                <?php endif; ?>


            </div>


            <div>

                <div>
                    Editing job for
                </div>

                <strong>

                    <?php
                    echo e(
                        $job["company_name"]
                    );
                    ?>

                </strong>

            </div>


        </div>


        <!-- SUCCESS -->

        <?php if ($success !== ""): ?>


            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?php
                echo e($success);
                ?>

            </div>


        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($error !== ""): ?>


            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php
                echo e($error);
                ?>

            </div>


        <?php endif; ?>


        <!-- =================================================
             FORM
        ================================================= -->

        <form
            method="POST"
            class="form-card"
            id="editJobForm"
        >


            <!-- BASIC INFORMATION -->

            <div class="form-section">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="fa-solid fa-briefcase"></i>

                    </div>

                    Basic Job Information

                </div>


                <div class="row g-3">


                    <div class="col-md-8">

                        <label class="form-label">

                            Job Title

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="job_title"
                            class="form-control"
                            maxlength="150"
                            required
                            value="<?php echo e($job["job_title"]); ?>"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Job Type

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="job_type"
                            class="form-select"
                            required
                        >


                            <option
                                value="Full Time"
                                <?php
                                echo
                                    $job["job_type"] === "Full Time"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Full Time
                            </option>


                            <option
                                value="Part Time"
                                <?php
                                echo
                                    $job["job_type"] === "Part Time"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Part Time
                            </option>


                            <option
                                value="Internship"
                                <?php
                                echo
                                    $job["job_type"] === "Internship"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Internship
                            </option>


                        </select>

                    </div>


                    <div class="col-12">

                        <label class="form-label">

                            Job Description

                            <span class="required">
                                *
                            </span>

                        </label>


                        <textarea
                            name="description"
                            class="form-control"
                            required
                        ><?php echo e($job["description"]); ?></textarea>


                        <div class="form-text">

                            Describe responsibilities,
                            requirements and expectations.

                        </div>

                    </div>


                </div>


            </div>


            <!-- JOB DETAILS -->

            <div class="form-section">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>

                    Job Details

                </div>


                <div class="row g-3">


                    <div class="col-md-6">

                        <label class="form-label">

                            Location

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            maxlength="150"
                            required
                            value="<?php echo e($job["location"]); ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Salary / Stipend

                        </label>


                        <input
                            type="text"
                            name="salary"
                            class="form-control"
                            maxlength="100"
                            placeholder="Example: ₹6 LPA"
                            value="<?php echo e($job["salary"]); ?>"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Minimum CGPA

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            name="min_cgpa"
                            id="min_cgpa"
                            class="form-control"
                            min="0"
                            max="10"
                            step="0.01"
                            required
                            value="<?php echo e($job["min_cgpa"]); ?>"
                        >


                        <div class="form-text">

                            CGPA must be between
                            0 and 10.

                        </div>

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Vacancies

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            name="vacancies"
                            class="form-control"
                            min="1"
                            required
                            value="<?php echo e($job["vacancies"]); ?>"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Application Deadline

                        </label>


                        <input
                            type="date"
                            name="application_deadline"
                            class="form-control"
                            value="<?php echo e($job["application_deadline"]); ?>"
                        >

                    </div>


                </div>


            </div>


            <!-- ELIGIBILITY -->

            <div class="form-section">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="fa-solid fa-graduation-cap"></i>

                    </div>

                    Eligibility

                </div>


                <div class="row g-3">


                    <div class="col-md-6">

                        <label class="form-label">

                            Eligible Department

                        </label>


                        <input
                            type="text"
                            name="eligible_department"
                            class="form-control"
                            maxlength="255"
                            placeholder="Example: Computer Engineering, IT"
                            value="<?php echo e($job["eligible_department"]); ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Eligible Year

                        </label>


                        <input
                            type="text"
                            name="eligible_year"
                            class="form-control"
                            maxlength="100"
                            placeholder="Example: 2nd Year, 3rd Year, Final Year"
                            value="<?php echo e($job["eligible_year"]); ?>"
                        >

                    </div>


                </div>


            </div>


            <!-- STATUS -->

            <div class="form-section">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="fa-solid fa-toggle-on"></i>

                    </div>

                    Job Status

                </div>


                <div class="row">


                    <div class="col-md-5">


                        <label class="form-label">

                            Status

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="status"
                            class="form-select"
                            required
                        >


                            <option
                                value="Open"
                                <?php
                                echo
                                    $job["status"] === "Open"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Open
                            </option>


                            <option
                                value="Draft"
                                <?php
                                echo
                                    $job["status"] === "Draft"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Draft
                            </option>


                            <option
                                value="Closed"
                                <?php
                                echo
                                    $job["status"] === "Closed"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Closed
                            </option>


                        </select>


                        <div class="form-text">

                            Open jobs are visible
                            to eligible students.

                        </div>


                    </div>


                </div>


            </div>


            <!-- ACTIONS -->

            <div class="form-actions">


                <a
                    href="manage_jobs.php"
                    class="btn-cancel"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn-save"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Changes

                </button>


            </div>


        </form>


    </section>


</main>


<script>


/* =========================================================
   CGPA VALIDATION
========================================================= */

const cgpaInput =
    document.getElementById(
        "min_cgpa"
    );


cgpaInput.addEventListener(

    "input",

    function () {

        let value =
            parseFloat(
                this.value
            );


        if (value > 10) {

            this.value =
                "10";

        }


        if (value < 0) {

            this.value =
                "0";

        }

    }

);


/* =========================================================
   FINAL FORM VALIDATION
========================================================= */

document
    .getElementById(
        "editJobForm"
    )
    .addEventListener(

        "submit",

        function (event) {


            const cgpa =
                parseFloat(
                    cgpaInput.value
                );


            if (

                isNaN(cgpa) ||

                cgpa < 0 ||

                cgpa > 10

            ) {

                event.preventDefault();


                alert(
                    "Minimum CGPA must be between 0 and 10."
                );


                cgpaInput.focus();


                return false;

            }

        }

    );


</script>


</body>

</html>