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
   GET RECRUITER COMPANY
========================================================= */

$company = null;

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, company_name
     FROM companies
     WHERE user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load company profile.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$company = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$company) {

    die(
        "Company profile not found. Please complete your company profile first."
    );
}


$company_id = (int)$company["id"];


/* =========================================================
   DEFAULT VALUES
========================================================= */

$message = "";
$messageType = "";

$job_title = "";
$job_type = "";
$location = "";
$salary = "";
$min_cgpa = "";
$eligible_department = "";
$eligible_year = "";
$application_deadline = "";
$vacancies = "";
$status = "Open";
$description = "";
$skills = "";


/* =========================================================
   POST JOB
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       GET FORM DATA
    ===================================================== */

    $job_title = trim($_POST["job_title"] ?? "");
    $job_type = trim($_POST["job_type"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $salary = trim($_POST["salary"] ?? "");
    $min_cgpa = trim($_POST["min_cgpa"] ?? "");
    $eligible_department = trim(
        $_POST["eligible_department"] ?? ""
    );
    $eligible_year = trim(
        $_POST["eligible_year"] ?? ""
    );
    $application_deadline = trim(
        $_POST["application_deadline"] ?? ""
    );
    $vacancies = trim($_POST["vacancies"] ?? "");
    $status = trim($_POST["status"] ?? "Open");
    $description = trim($_POST["description"] ?? "");
    $skills = trim($_POST["skills"] ?? "");


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (

        $job_title === "" ||
        $job_type === "" ||
        $location === "" ||
        $min_cgpa === "" ||
        $eligible_department === "" ||
        $eligible_year === "" ||
        $application_deadline === "" ||
        $vacancies === "" ||
        $description === ""

    ) {

        $message =
            "Please fill in all required fields.";

        $messageType =
            "danger";


    } elseif (!is_numeric($min_cgpa)) {

        $message =
            "Please enter a valid minimum CGPA.";

        $messageType =
            "danger";


    } elseif (
        (float)$min_cgpa < 0 ||
        (float)$min_cgpa > 10
    ) {

        $message =
            "Minimum CGPA must be between 0 and 10.";

        $messageType =
            "danger";


    } elseif (

        !is_numeric($vacancies) ||
        (int)$vacancies <= 0

    ) {

        $message =
            "Vacancies must be greater than 0.";

        $messageType =
            "danger";


    } elseif (

        strtotime($application_deadline) === false

    ) {

        $message =
            "Please enter a valid application deadline.";

        $messageType =
            "danger";


    } else {


        /* =================================================
           VALIDATE JOB TYPE
        ================================================= */

        $allowedJobTypes = [

            "Full Time",
            "Part Time",
            "Internship",
            "Remote"

        ];


        if (

            !in_array(
                $job_type,
                $allowedJobTypes,
                true
            )

        ) {

            $message =
                "Please select a valid job type.";

            $messageType =
                "danger";

        }


        /* =================================================
           VALIDATE STATUS
        ================================================= */

        $allowedStatuses = [

            "Open",
            "Draft",
            "Closed"

        ];


        if (

            !in_array(
                $status,
                $allowedStatuses,
                true
            )

        ) {

            $status = "Open";
        }


        /* =================================================
           CONTINUE IF VALID
        ================================================= */

        if ($message === "") {


            /*
             * Convert numeric values
             */

            $min_cgpa =
                (float)$min_cgpa;

            $vacancies =
                (int)$vacancies;


            /* =============================================
               START TRANSACTION
            ============================================== */

            mysqli_begin_transaction($conn);


            try {


                /* =========================================
                   INSERT JOB
                ========================================== */

                $stmt = mysqli_prepare(

                    $conn,

                    "INSERT INTO jobs
                    (
                        company_id,
                        job_title,
                        job_type,
                        location,
                        salary,
                        min_cgpa,
                        eligible_department,
                        eligible_year,
                        application_deadline,
                        vacancies,
                        status,
                        description
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )"

                );


                if (!$stmt) {

                    throw new Exception(
                        "Unable to prepare job query."
                    );
                }


                mysqli_stmt_bind_param(

                    $stmt,

                    "issssdsssiss",

                    $company_id,
                    $job_title,
                    $job_type,
                    $location,
                    $salary,
                    $min_cgpa,
                    $eligible_department,
                    $eligible_year,
                    $application_deadline,
                    $vacancies,
                    $status,
                    $description

                );


                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(
                        "Unable to post the job."
                    );
                }


                /*
                 * Get newly inserted Job ID
                 */

                $job_id =
                    mysqli_insert_id($conn);


                mysqli_stmt_close($stmt);


                /* =========================================
                   INSERT REQUIRED SKILLS
                ========================================== */

                if ($skills !== "") {


                    /*
                     * Split skills using commas
                     */

                    $skillArray =

                        array_filter(

                            array_map(

                                "trim",

                                explode(
                                    ",",
                                    $skills
                                )

                            )

                        );


                    /*
                     * Remove duplicate skills
                     */

                    $skillArray =

                        array_unique(
                            $skillArray
                        );


                    if (!empty($skillArray)) {


                        $skillStmt =

                            mysqli_prepare(

                                $conn,

                                "INSERT INTO job_skills
                                (
                                    job_id,
                                    skill
                                )
                                VALUES
                                (
                                    ?, ?
                                )"

                            );


                        if (!$skillStmt) {

                            throw new Exception(
                                "Unable to prepare skills query."
                            );
                        }


                        foreach (

                            $skillArray
                            as $skill

                        ) {


                            /*
                             * Ignore empty values
                             */

                            if ($skill === "") {
                                continue;
                            }


                            mysqli_stmt_bind_param(

                                $skillStmt,

                                "is",

                                $job_id,
                                $skill

                            );


                            if (

                                !mysqli_stmt_execute(
                                    $skillStmt
                                )

                            ) {

                                throw new Exception(
                                    "Unable to save required skills."
                                );
                            }
                        }


                        mysqli_stmt_close(
                            $skillStmt
                        );
                    }
                }


                /* =========================================
                   COMMIT TRANSACTION
                ========================================== */

                mysqli_commit($conn);


                $message =

                    "Job posted successfully! Your opportunity is now available to eligible students.";


                $messageType =
                    "success";


                /* =========================================
                   RESET FORM
                ========================================== */

                $job_title = "";
                $job_type = "";
                $location = "";
                $salary = "";
                $min_cgpa = "";
                $eligible_department = "";
                $eligible_year = "";
                $application_deadline = "";
                $vacancies = "";
                $status = "Open";
                $description = "";
                $skills = "";


            } catch (Exception $e) {


                mysqli_rollback($conn);


                $message =
                    $e->getMessage();


                $messageType =
                    "danger";
            }
        }
    }
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
    Post a Job | Smart Placement Portal
</title>


<!-- BOOTSTRAP -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- FONT AWESOME -->

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>


<!-- GOOGLE FONT -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --primary:
        #7184ff;

    --primary-light:
        #6339d7;

    --border:
        rgba(148,163,184,.15);

    --muted:
        #94a3b8;

    --muted-dark:
        #64748b;
}


* {

    box-sizing:
        border-box;
}


body {

    margin: 0;

    font-family:
        "Inter",
        sans-serif;

    color:
        #e5e7eb;

    background:
        #07111f;

    min-height:
        100vh;
}


/* =========================================================
   BACKGROUND GRID
========================================================= */

body::before {

    content:
        "";

    position:
        fixed;

    inset:
        0;

    pointer-events:
        none;

    opacity:
        .28;

    background-image:

        linear-gradient(
            rgba(255,255,255,.035) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(255,255,255,.035) 1px,
            transparent 1px
        );

    background-size:
        70px 70px;

    z-index:
        -1;
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
        112px;

    padding:
        0 48px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    border-bottom:
        1px solid
        rgba(148,163,184,.12);

    background:
        rgba(7,17,31,.72);

    backdrop-filter:
        blur(15px);
}


.greeting-small {

    color:
        #94a3b8;

    font-size:
        12px;

    font-weight:
        600;

    margin-bottom:
        7px;
}


.topbar h1 {

    margin:
        0;

    font-size:
        27px;

    font-weight:
        800;

    letter-spacing:
        -.5px;
}


.topbar h1 span {

    background:

        linear-gradient(
            90deg,
            #9da7ff,
            #58d7ff
        );

    -webkit-background-clip:
        text;

    -webkit-text-fill-color:
        transparent;
}


/* =========================================================
   PROFILE
========================================================= */

.profile-area {

    display:
        flex;

    align-items:
        center;

    gap:
        14px;
}


.profile-text {

    text-align:
        right;
}


.profile-name {

    font-size:
        13px;

    font-weight:
        700;

    color:
        #e5e7eb;
}


.profile-role {

    font-size:
        11px;

    color:
        #94a3b8;

    margin-top:
        3px;
}


.avatar {

    width:
        56px;

    height:
        56px;

    border-radius:
        18px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

    color:
        white;

    background:

        linear-gradient(
            135deg,
            #7c91ff,
            #6439d9
        );

    box-shadow:

        0 8px 25px
        rgba(99,102,241,.25);
}


/* =========================================================
   CONTENT
========================================================= */

.content {

    max-width:
        1350px;

    padding:
        42px 48px 70px;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    margin-bottom:
        30px;
}


.page-header h2 {

    margin:
        0 0 8px;

    font-size:
        30px;

    font-weight:
        800;

    color:
        #f1f5f9;
}


.page-header p {

    margin:
        0;

    color:
        #94a3b8;

    font-size:
        13px;

    line-height:
        1.7;
}


/* =========================================================
   ALERT
========================================================= */

.alert {

    border-radius:
        15px;

    padding:
        16px 20px;

    font-size:
        13px;

    border:
        1px solid;
}


.alert-success {

    color:
        #86efac;

    background:
        rgba(34,197,94,.09);

    border-color:
        rgba(34,197,94,.2);
}


.alert-danger {

    color:
        #fca5a5;

    background:
        rgba(239,68,68,.08);

    border-color:
        rgba(239,68,68,.2);
}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    position:
        relative;

    overflow:
        hidden;

    background:

        linear-gradient(
            145deg,
            rgba(31,42,62,.94),
            rgba(17,29,47,.94)
        );

    border:
        1px solid
        rgba(148,163,184,.17);

    border-radius:
        24px;

    padding:
        34px;

    box-shadow:

        0 20px 60px
        rgba(0,0,0,.18);
}


/* =========================================================
   CARD HEADER
========================================================= */

.card-header-custom {

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

    margin-bottom:
        32px;

    padding-bottom:
        24px;

    border-bottom:
        1px solid
        rgba(148,163,184,.1);
}


.header-icon {

    width:
        52px;

    height:
        52px;

    border-radius:
        16px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        19px;

    color:
        #8ca7ff;

    background:
        rgba(100,116,255,.14);
}


.card-header-custom h3 {

    margin:
        0 0 5px;

    font-size:
        18px;

    font-weight:
        800;

    color:
        #f1f5f9;
}


.card-header-custom p {

    margin:
        0;

    color:
        #94a3b8;

    font-size:
        12px;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    margin:
        32px 0 18px;

    color:
        #e2e8f0;

    font-size:
        14px;

    font-weight:
        800;
}


.section-title i {

    color:
        #70d6ff;

    font-size:
        13px;
}


/* =========================================================
   FORM LABEL
========================================================= */

.form-label {

    color:
        #cbd5e1;

    font-size:
        12px;

    font-weight:
        700;

    margin-bottom:
        8px;
}


.required {

    color:
        #f87171;

    margin-left:
        3px;
}


/* =========================================================
   INPUTS
========================================================= */

.form-control,
.form-select {

    min-height:
        48px;

    border-radius:
        11px;

    border:
        1px solid
        rgba(148,163,184,.18);

    color:
        #e2e8f0;

    font-size:
        13px;

    background:
        rgba(6,15,28,.55);

    padding:
        12px 15px;

    transition:
        .25s ease;
}


.form-control::placeholder {

    color:
        #64748b;
}


.form-select {

    cursor:
        pointer;
}


.form-select option {

    background:
        #111c2d;

    color:
        white;
}


.form-control:focus,
.form-select:focus {

    color:
        #f1f5f9;

    background:
        rgba(8,19,35,.85);

    border-color:
        #7184ff;

    box-shadow:

        0 0 0 4px
        rgba(99,102,241,.12);
}


textarea.form-control {

    min-height:
        150px;

    resize:
        vertical;
}


/* =========================================================
   INPUT GROUP
========================================================= */

.input-group-text {

    border:
        1px solid
        rgba(148,163,184,.18);

    border-right:
        none;

    color:
        #94a3b8;

    background:
        rgba(6,15,28,.55);

    border-radius:
        11px 0 0 11px;

    font-size:
        12px;
}


.input-group
.form-control {

    border-radius:
        0 11px 11px 0;
}


/* =========================================================
   FIELD NOTE
========================================================= */

.field-note {

    margin-top:
        7px;

    color:
        #64748b;

    font-size:
        10px;
}


/* =========================================================
   BUTTON AREA
========================================================= */

.form-actions {

    display:
        flex;

    justify-content:
        flex-end;

    align-items:
        center;

    gap:
        13px;

    margin-top:
        36px;

    padding-top:
        25px;

    border-top:
        1px solid
        rgba(148,163,184,.1);
}


/* =========================================================
   BUTTONS
========================================================= */

.btn-cancel {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    min-height:
        46px;

    padding:
        0 19px;

    border-radius:
        11px;

    color:
        #cbd5e1;

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        700;

    border:
        1px solid
        rgba(148,163,184,.18);

    background:
        rgba(255,255,255,.02);

    transition:
        .25s ease;
}


.btn-cancel:hover {

    color:
        white;

    background:
        rgba(148,163,184,.08);
}


.btn-post {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        9px;

    min-height:
        48px;

    padding:
        0 24px;

    border:
        none;

    border-radius:
        12px;

    color:
        white;

    font-size:
        12px;

    font-weight:
        800;

    background:

        linear-gradient(
            135deg,
            #7184ff,
            #6339d7
        );

    box-shadow:

        0 10px 28px
        rgba(99,102,241,.28);

    transition:
        .25s ease;
}


.btn-post:hover {

    transform:
        translateY(-2px);

    box-shadow:

        0 15px 35px
        rgba(99,102,241,.4);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 850px) {

    .main {

        margin-left:
            75px;
    }

}


@media (max-width: 700px) {

    .topbar {

        min-height:
            auto;

        padding:
            22px 20px;

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            20px;
    }


    .profile-area {

        align-self:
            flex-end;
    }


    .content {

        padding:
            28px 18px 50px;
    }


    .form-card {

        padding:
            22px 17px;
    }


    .page-header h2 {

        font-size:
            24px;
    }


    .form-actions {

        flex-direction:
            column-reverse;
    }


    .btn-cancel,
    .btn-post {

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


<?php require_once "../includes/recruiter_sidebar.php"; ?>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <div class="greeting-small">

                Create a new opportunity

            </div>


            <h1>

                Post a

                <span>
                    New Job
                </span>

            </h1>

        </div>


        <div class="profile-area">


            <div class="profile-text">


                <div class="profile-name">

                    <?php

                    echo e(
                        $_SESSION["name"]
                        ?? "Recruiter"
                    );

                    ?>

                </div>


                <div class="profile-role">

                    Recruiter Account

                </div>


            </div>


            <div class="avatar">

                <?php

                echo strtoupper(

                    substr(

                        $_SESSION["name"]
                        ?? "R",

                        0,

                        1

                    )

                );

                ?>

            </div>


        </div>


    </header>


    <!-- =================================================
         CONTENT
    ================================================= -->

    <section class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">


            <h2>
                Create a Job Opportunity
            </h2>


            <p>

                Share your opportunity with eligible students and find the right talent for your organization.

            </p>


        </div>


        <!-- MESSAGE -->

        <?php if ($message !== ""): ?>


            <div
                class="alert alert-<?php echo e($messageType); ?>"
            >

                <i
                    class="fa-solid
                    <?php
                    echo $messageType === "success"
                        ? "fa-circle-check"
                        : "fa-circle-exclamation";
                    ?>
                    me-2"
                ></i>

                <?php echo e($message); ?>

            </div>


        <?php endif; ?>


        <!-- =================================================
             FORM CARD
        ================================================= -->

        <div class="form-card">


            <!-- CARD HEADER -->

            <div class="card-header-custom">


                <div class="header-icon">

                    <i
                        class="fa-solid fa-briefcase"
                    ></i>

                </div>


                <div>

                    <h3>
                        Job Details
                    </h3>


                    <p>

                        Fill in the information below to create a new job posting.

                    </p>

                </div>


            </div>


            <!-- FORM -->

            <form method="POST">


                <!-- =========================================
                     BASIC INFORMATION
                ========================================== -->

                <div class="section-title">

                    <i
                        class="fa-solid fa-circle-info"
                    ></i>

                    Basic Information

                </div>


                <div class="row g-4">


                    <div class="col-md-6">

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
                            placeholder="e.g. Software Developer"
                            value="<?php echo e($job_title); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-6">

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

                            <option value="">
                                Select Job Type
                            </option>


                            <?php

                            $jobTypes = [

                                "Full Time",
                                "Part Time",
                                "Internship",
                                "Remote"

                            ];


                            foreach ($jobTypes as $type):

                            ?>

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


                    <div class="col-md-6">

                        <label class="form-label">

                            Job Location

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            placeholder="e.g. Pune, Maharashtra"
                            value="<?php echo e($location); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">

                            Salary / Package

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">
                                ₹
                            </span>


                            <input
                                type="text"
                                name="salary"
                                class="form-control"
                                placeholder="e.g. 6 LPA or 25,000/month"
                                value="<?php echo e($salary); ?>"
                            >

                        </div>

                    </div>


                </div>


                <!-- =========================================
                     ELIGIBILITY
                ========================================== -->

                <div class="section-title">

                    <i
                        class="fa-solid fa-graduation-cap"
                    ></i>

                    Eligibility Criteria

                </div>


                <div class="row g-4">


                    <div class="col-md-4">

                        <label class="form-label">

                            Minimum CGPA

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="10"
                            name="min_cgpa"
                            class="form-control"
                            placeholder="e.g. 6.5"
                            value="<?php echo e($min_cgpa); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Eligible Department

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="eligible_department"
                            class="form-control"
                            placeholder="e.g. Computer Engineering"
                            value="<?php echo e($eligible_department); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Eligible Year

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="eligible_year"
                            class="form-control"
                            placeholder="e.g. Final Year"
                            value="<?php echo e($eligible_year); ?>"
                            required
                        >

                    </div>


                </div>


                <!-- =========================================
                     APPLICATION DETAILS
                ========================================== -->

                <div class="section-title">

                    <i
                        class="fa-solid fa-calendar-days"
                    ></i>

                    Application Details

                </div>


                <div class="row g-4">


                    <div class="col-md-4">

                        <label class="form-label">

                            Application Deadline

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="date"
                            name="application_deadline"
                            class="form-control"
                            value="<?php echo e($application_deadline); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Number of Vacancies

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            min="1"
                            name="vacancies"
                            class="form-control"
                            placeholder="e.g. 5"
                            value="<?php echo e($vacancies); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">

                            Job Status

                            <span class="required">
                                *
                            </span>

                        </label>


                        <select
                            name="status"
                            class="form-select"
                            required
                        >

                            <?php

                            $statuses = [

                                "Open",
                                "Draft",
                                "Closed"

                            ];


                            foreach ($statuses as $statusOption):

                            ?>

                                <option
                                    value="<?php echo e($statusOption); ?>"
                                    <?php
                                    echo $status === $statusOption
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php
                                    echo e($statusOption);
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                </div>


                <!-- =========================================
                     REQUIRED SKILLS
                ========================================== -->

                <div class="section-title">

                    <i
                        class="fa-solid fa-code"
                    ></i>

                    Required Skills

                </div>


                <div class="mb-4">

                    <label class="form-label">

                        Skills Required

                    </label>


                    <input
                        type="text"
                        name="skills"
                        class="form-control"
                        placeholder="e.g. C++, Java, Python, SQL, React"
                        value="<?php echo e($skills); ?>"
                    >


                    <div class="field-note">

                        Separate multiple skills using commas.

                    </div>

                </div>


                <!-- =========================================
                     JOB DESCRIPTION
                ========================================== -->

                <div class="section-title">

                    <i
                        class="fa-solid fa-align-left"
                    ></i>

                    Job Description

                </div>


                <div>

                    <label class="form-label">

                        Describe the Job

                        <span class="required">
                            *
                        </span>

                    </label>


                    <textarea
                        name="description"
                        class="form-control"
                        placeholder="Describe the responsibilities, requirements, qualifications and expectations for this position..."
                        required
                    ><?php echo e($description); ?></textarea>

                </div>


                <!-- =========================================
                     ACTIONS
                ========================================== -->

                <div class="form-actions">


                    <a
                        href="manage_jobs.php"
                        class="btn-cancel"
                    >

                        <i
                            class="fa-solid fa-arrow-left"
                        ></i>

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn-post"
                    >

                        <i
                            class="fa-solid fa-paper-plane"
                        ></i>

                        Publish Job

                    </button>


                </div>


            </form>


        </div>


    </section>


</main>


</body>

</html>