<?php

require_once "config/database.php";
require_once "config/security.php";


/* =========================================
   REDIRECT LOGGED-IN USER
========================================= */

redirectLoggedInUser();


$message = "";
$messageType = "";


/* =========================================
   GENERATE CSRF TOKEN
========================================= */

$csrfToken = generateCSRFToken();


/* =========================================
   REGISTER PROCESS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* CSRF PROTECTION */

    if (
        !isset($_POST["csrf_token"]) ||
        !verifyCSRFToken($_POST["csrf_token"])
    ) {

        $message =
            "Invalid request. Please refresh the page and try again.";

        $messageType =
            "danger";

    } else {


        /* GET INPUT */

        $full_name =
            trim($_POST["full_name"] ?? "");

        $email =
            strtolower(
                trim($_POST["email"] ?? "")
            );

        $password =
            $_POST["password"] ?? "";

        $confirm_password =
            $_POST["confirm_password"] ?? "";

        $role =
            $_POST["role"] ?? "";


        /* =========================================
           REQUIRED FIELD VALIDATION
        ========================================= */

        if (
            empty($full_name) ||
            empty($email) ||
            empty($password) ||
            empty($confirm_password) ||
            empty($role)
        ) {

            $message =
                "Please fill all required fields.";

            $messageType =
                "danger";


        /* =========================================
           FULL NAME VALIDATION
        ========================================= */

        } else {

            $nameValidation =
                validateFullName($full_name);


            if ($nameValidation !== true) {

                $message =
                    $nameValidation;

                $messageType =
                    "danger";


            /* =========================================
               EMAIL VALIDATION
            ========================================= */

            } elseif (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $message =
                    "Please enter a valid email address.";

                $messageType =
                    "danger";


            /* =========================================
               ROLE VALIDATION
            ========================================= */

            } elseif (
                !in_array(
                    $role,
                    ["student", "recruiter"],
                    true
                )
            ) {

                $message =
                    "Invalid account type.";

                $messageType =
                    "danger";


            /* =========================================
               PASSWORD MATCH
            ========================================= */

            } elseif (
                $password !==
                $confirm_password
            ) {

                $message =
                    "Passwords do not match.";

                $messageType =
                    "danger";


            /* =========================================
               PASSWORD SECURITY
            ========================================= */

            } else {

                $passwordValidation =
                    validatePassword($password);


                if (
                    $passwordValidation !== true
                ) {

                    $message =
                        $passwordValidation;

                    $messageType =
                        "danger";

                } else {


                    /* =========================================
                       CHECK DUPLICATE EMAIL
                    ========================================= */

                    $check = mysqli_prepare(
                        $conn,
                        "SELECT id
                         FROM users
                         WHERE email = ?
                         LIMIT 1"
                    );


                    if ($check) {

                        mysqli_stmt_bind_param(
                            $check,
                            "s",
                            $email
                        );


                        mysqli_stmt_execute(
                            $check
                        );


                        mysqli_stmt_store_result(
                            $check
                        );


                        if (
                            mysqli_stmt_num_rows($check) > 0
                        ) {

                            $message =
                                "An account with this email already exists.";

                            $messageType =
                                "danger";


                        } else {


                            /* =========================================
                               HASH PASSWORD
                            ========================================= */

                            $hashedPassword =
                                password_hash(
                                    $password,
                                    PASSWORD_DEFAULT
                                );


                            /* =========================================
                               INSERT USER
                            ========================================= */

                            $stmt = mysqli_prepare(
                                $conn,
                                "INSERT INTO users
                                (
                                    full_name,
                                    email,
                                    password,
                                    role
                                )
                                VALUES (?, ?, ?, ?)"
                            );


                            if ($stmt) {

                                mysqli_stmt_bind_param(
                                    $stmt,
                                    "ssss",
                                    $full_name,
                                    $email,
                                    $hashedPassword,
                                    $role
                                );


                                if (
                                    mysqli_stmt_execute($stmt)
                                ) {

                                    $userId =
                                        mysqli_insert_id($conn);


                                    /* =========================================
                                       CREATE STUDENT PROFILE
                                    ========================================= */

                                    if (
                                        $role === "student"
                                    ) {

                                        $student =
                                            mysqli_prepare(
                                                $conn,
                                                "INSERT INTO students
                                                (user_id)
                                                VALUES (?)"
                                            );


                                        if ($student) {

                                            mysqli_stmt_bind_param(
                                                $student,
                                                "i",
                                                $userId
                                            );


                                            mysqli_stmt_execute(
                                                $student
                                            );


                                            mysqli_stmt_close(
                                                $student
                                            );
                                        }


                                    /* =========================================
                                       CREATE RECRUITER PROFILE
                                    ========================================= */

                                    } elseif (
                                        $role === "recruiter"
                                    ) {

                                        $company =
                                            mysqli_prepare(
                                                $conn,
                                                "INSERT INTO companies
                                                (
                                                    user_id,
                                                    company_name,
                                                    company_email
                                                )
                                                VALUES (?, ?, ?)"
                                            );


                                        if ($company) {

                                            $companyName =
                                                $full_name .
                                                "'s Company";


                                            mysqli_stmt_bind_param(
                                                $company,
                                                "iss",
                                                $userId,
                                                $companyName,
                                                $email
                                            );


                                            mysqli_stmt_execute(
                                                $company
                                            );


                                            mysqli_stmt_close(
                                                $company
                                            );
                                        }
                                    }


                                    /*
                                     Regenerate CSRF token after
                                     successful registration.
                                    */

                                    $_SESSION["csrf_token"] =
                                        bin2hex(
                                            random_bytes(32)
                                        );


                                    $csrfToken =
                                        $_SESSION["csrf_token"];


                                    $message =
                                        "Registration successful! You can now login.";

                                    $messageType =
                                        "success";


                                } else {

                                    $message =
                                        "Registration failed. Please try again.";

                                    $messageType =
                                        "danger";
                                }


                                mysqli_stmt_close($stmt);


                            } else {

                                $message =
                                    "Something went wrong. Please try again later.";

                                $messageType =
                                    "danger";
                            }
                        }


                        mysqli_stmt_close($check);


                    } else {

                        $message =
                            "Something went wrong. Please try again later.";

                        $messageType =
                            "danger";
                    }
                }
            }
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
    Create Account | Smart Placement Portal
</title>


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

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>


<style>

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;
}


body {

    min-height: 100vh;

    font-family: "DM Sans", sans-serif;

    background:

        radial-gradient(
            circle at 15% 20%,
            rgba(91,124,255,.20),
            transparent 30%
        ),

        radial-gradient(
            circle at 85% 75%,
            rgba(139,92,246,.18),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #030712,
            #07111f 55%,
            #0b1020
        );

    color: white;

    overflow-x: hidden;
}


body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

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

    background-size: 55px 55px;

}


.auth-navbar {

    position: fixed;

    top: 0;
    left: 0;
    right: 0;

    z-index: 10;

    padding: 22px 0;
}


.nav-container {

    width:
        min(
            1180px,
            calc(100% - 40px)
        );

    margin: auto;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.logo {

    display: flex;

    align-items: center;

    gap: 11px;

    color: white;

    text-decoration: none;
}


.logo-icon {

    width: 44px;
    height: 44px;

    border-radius: 14px;

    display: flex;

    justify-content: center;
    align-items: center;

    background:
        linear-gradient(
            135deg,
            #5b7cff,
            #8b5cf6
        );

    box-shadow:
        0 10px 30px
        rgba(91,124,255,.35);
}


.logo-text {

    font-family: "Outfit";

    font-size: 15px;

    font-weight: 700;
}


.back-home {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #cbd5e1;

    font-size: 12px;

    text-decoration: none;

    transition: .25s;
}


.back-home:hover {

    color: white;
}


.orb {

    position: fixed;

    border-radius: 50%;

    filter: blur(80px);

    opacity: .35;

    pointer-events: none;
}


.orb-one {

    width: 330px;
    height: 330px;

    background: #2563eb;

    top: 20%;
    left: -150px;
}


.orb-two {

    width: 320px;
    height: 320px;

    background: #7c3aed;

    right: -130px;
    bottom: 5%;
}


.auth-page {

    min-height: 100vh;

    display: flex;

    justify-content: center;
    align-items: center;

    padding:
        120px
        20px
        50px;

    position: relative;
}


.auth-wrapper {

    width: min(100%, 1080px);

    display: grid;

    grid-template-columns:
        .9fr
        1.1fr;

    border-radius: 28px;

    overflow: hidden;

    position: relative;

    z-index: 2;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.04);

    backdrop-filter:
        blur(20px);

    box-shadow:
        0 35px 90px
        rgba(0,0,0,.35);
}


.auth-info {

    padding:
        55px
        48px;

    background:
        linear-gradient(
            160deg,
            rgba(91,124,255,.18),
            rgba(139,92,246,.10)
        );

    border-right:
        1px solid
        rgba(255,255,255,.08);

    display: flex;

    flex-direction: column;

    justify-content: center;
}


.info-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    width: fit-content;

    padding:
        8px
        13px;

    border-radius: 30px;

    background:
        rgba(255,255,255,.05);

    border:
        1px solid
        rgba(255,255,255,.10);

    color: #cbd5e1;

    font-size: 10px;

    margin-bottom: 25px;
}


.badge-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:
        0 0 12px #22c55e;
}


.auth-info h1 {

    font-family: "Outfit";

    font-size: 43px;

    line-height: 1.08;

    letter-spacing: -1.8px;

    margin-bottom: 18px;
}


.gradient-text {

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


.auth-info > p {

    color: #94a3b8;

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 28px;
}


.info-points {

    list-style: none;
}


.info-points li {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #cbd5e1;

    font-size: 11px;

    margin-bottom: 15px;
}


.info-points i {

    color: #60a5fa;
}


.auth-form-section {

    padding:
        45px
        48px;
}


.auth-logo {

    width: 55px;
    height: 55px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 17px;

    margin-bottom: 18px;

    background:
        linear-gradient(
            135deg,
            #5b7cff,
            #8b5cf6
        );

    box-shadow:
        0 15px 35px
        rgba(91,124,255,.28);
}


.auth-form-section h2 {

    font-family: "Outfit";

    font-size: 30px;

    margin-bottom: 7px;
}


.auth-subtitle {

    color: #94a3b8;

    font-size: 12px;

    margin-bottom: 24px;
}


.alert {

    padding: 12px 14px;

    margin-bottom: 17px;

    border-radius: 10px;

    font-size: 11px;
}


.alert-danger {

    color: #fecaca;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid
        rgba(239,68,68,.22);
}


.alert-success {

    color: #bbf7d0;

    background:
        rgba(34,197,94,.10);

    border:
        1px solid
        rgba(34,197,94,.22);
}


.form-row {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 14px;
}


.form-group {

    margin-bottom: 15px;
}


.form-label {

    display: block;

    margin-bottom: 7px;

    color: #cbd5e1;

    font-size: 11px;

    font-weight: 600;
}


.input-wrapper {

    position: relative;
}


.input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #64748b;

    font-size: 12px;

    pointer-events: none;
}


.form-control {

    width: 100%;

    height: 47px;

    padding:
        0
        15px
        0
        42px;

    border-radius: 11px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.055);

    color: white;

    outline: none;

    font-family: inherit;

    font-size: 12px;
}


.form-control::placeholder {

    color: #64748b;
}


.form-control:focus {

    border-color:
        rgba(96,165,250,.65);

    background:
        rgba(255,255,255,.075);

    box-shadow:
        0 0 0 4px
        rgba(91,124,255,.10);
}


select.form-control {

    appearance: auto;

    cursor: pointer;
}


select.form-control option {

    background: #111827;

    color: white;
}


.auth-btn {

    width: 100%;

    height: 49px;

    border: none;

    border-radius: 11px;

    cursor: pointer;

    margin-top: 4px;

    color: white;

    font-family: inherit;

    font-size: 12px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #5b7cff,
            #7c3aed
        );

    box-shadow:
        0 15px 35px
        rgba(91,124,255,.25);

    transition: .25s;
}


.auth-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 20px 40px
        rgba(91,124,255,.35);
}


.auth-footer {

    text-align: center;

    margin-top: 20px;

    color: #64748b;

    font-size: 11px;
}


.auth-footer a {

    color: #8ab4ff;

    font-weight: 600;

    text-decoration: none;
}


@media(max-width: 850px) {

    .auth-wrapper {

        max-width: 520px;

        grid-template-columns: 1fr;
    }

    .auth-info {

        display: none;
    }
}


@media(max-width: 550px) {

    .auth-navbar {

        padding: 17px 0;
    }

    .nav-container {

        width:
            calc(100% - 28px);
    }

    .logo-text {

        font-size: 12px;
    }

    .back-home span {

        display: none;
    }

    .auth-page {

        padding:
            100px
            14px
            30px;
    }

    .auth-form-section {

        padding:
            35px
            22px;
    }

    .form-row {

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>


<div class="orb orb-one"></div>
<div class="orb orb-two"></div>


<header class="auth-navbar">

<div class="nav-container">


<a
    href="index.php"
    class="logo"
>

<div class="logo-icon">

<i class="fa-solid fa-graduation-cap"></i>

</div>


<div class="logo-text">

Smart Placement Portal

</div>

</a>


<a
    href="index.php"
    class="back-home"
>

<i class="fa-solid fa-arrow-left"></i>

<span>

Back to Home

</span>

</a>


</div>

</header>



<main class="auth-page">


<div class="auth-wrapper">


<!-- LEFT SIDE -->

<div class="auth-info">


<div class="info-badge">

<span class="badge-dot"></span>

Start building your future

</div>


<h1>

Your career

<br>

starts with

<br>

<span class="gradient-text">

one smart step.

</span>

</h1>


<p>

Create your account, build your professional
profile and unlock opportunities that can help
shape your career.

</p>


<ul class="info-points">

<li>

<i class="fa-solid fa-circle-check"></i>

Create a professional profile

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

Discover placement opportunities

</li>


<li>

<i class="fa-solid fa-circle-check"></i>

Track your career journey

</li>

</ul>


</div>



<!-- REGISTER FORM -->

<div class="auth-form-section">


<div class="auth-logo">

<i class="fa-solid fa-user-plus"></i>

</div>


<h2>

Create Account

</h2>


<p class="auth-subtitle">

Join the Smart Placement Portal today.

</p>


<?php if ($message): ?>

<div
    class="alert alert-<?php echo htmlspecialchars($messageType); ?>"
>

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST" autocomplete="on">


<!-- CSRF TOKEN -->

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrfToken); ?>"
>


<div class="form-group">

<label class="form-label">

Full Name

</label>


<div class="input-wrapper">

<i
    class="fa-solid fa-user input-icon"
></i>


<input
    type="text"
    name="full_name"
    class="form-control"
    placeholder="Enter your full name"
    value="<?php echo htmlspecialchars($_POST["full_name"] ?? ""); ?>"
    maxlength="100"
    autocomplete="name"
    required
>

</div>

</div>



<div class="form-group">

<label class="form-label">

Email Address

</label>


<div class="input-wrapper">

<i
    class="fa-solid fa-envelope input-icon"
></i>


<input
    type="email"
    name="email"
    class="form-control"
    placeholder="Enter your email"
    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
    maxlength="255"
    autocomplete="email"
    required
>

</div>

</div>



<div class="form-group">

<label class="form-label">

Account Type

</label>


<div class="input-wrapper">

<i
    class="fa-solid fa-user-tag input-icon"
></i>


<select
    name="role"
    class="form-control"
    required
>

<option value="">

Select account type

</option>


<option
    value="student"
    <?php
        echo (
            ($_POST["role"] ?? "")
            === "student"
        )
        ? "selected"
        : "";
    ?>
>

Student

</option>


<option
    value="recruiter"
    <?php
        echo (
            ($_POST["role"] ?? "")
            === "recruiter"
        )
        ? "selected"
        : "";
    ?>
>

Recruiter

</option>

</select>

</div>

</div>



<div class="form-row">


<div class="form-group">

<label class="form-label">

Password

</label>


<div class="input-wrapper">

<i
    class="fa-solid fa-lock input-icon"
></i>


<input
    type="password"
    name="password"
    class="form-control"
    placeholder="8+ characters"
    minlength="8"
    autocomplete="new-password"
    required
>

</div>

</div>



<div class="form-group">

<label class="form-label">

Confirm Password

</label>


<div class="input-wrapper">

<i
    class="fa-solid fa-shield-halved input-icon"
></i>


<input
    type="password"
    name="confirm_password"
    class="form-control"
    placeholder="Confirm password"
    minlength="8"
    autocomplete="new-password"
    required
>

</div>

</div>


</div>


<button
    type="submit"
    class="auth-btn"
>

Create Account

<i class="fa-solid fa-arrow-right"></i>

</button>


</form>


<p class="auth-footer">

Already have an account?

<a href="login.php">

Login

</a>

</p>


</div>


</div>


</main>


</body>

</html>