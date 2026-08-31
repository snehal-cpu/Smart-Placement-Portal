<?php

require_once "config/database.php";
require_once "config/security.php";


/* =========================================
   REDIRECT IF USER IS ALREADY LOGGED IN
========================================= */

redirectLoggedInUser();


$message = "";


/* =========================================
   GENERATE CSRF TOKEN
========================================= */

$csrfToken = generateCSRFToken();


/* =========================================
   LOGIN PROCESS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF PROTECTION */

    if (
        !isset($_POST["csrf_token"]) ||
        !verifyCSRFToken($_POST["csrf_token"])
    ) {

        $message = "Invalid request. Please refresh the page and try again.";

    } elseif (!checkLoginAttempts()) {

        $message =
            "Too many login attempts. Please try again after 5 minutes.";

    } else {

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";


        /* INPUT VALIDATION */

        if (empty($email) || empty($password)) {

            $message = "Please enter email and password.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            /*
             Generic message prevents revealing
             unnecessary information.
            */

            increaseLoginAttempts();

            $message = "Invalid email or password.";

        } else {

            /*
             Normalize email
            */

            $email = strtolower($email);


            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, full_name, password, role, status
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );


            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "s",
                    $email
                );


                mysqli_stmt_execute($stmt);


                $result =
                    mysqli_stmt_get_result($stmt);


                if (
                    $user =
                    mysqli_fetch_assoc($result)
                ) {

                    /*
                     Check account status
                    */

                    if (
                        $user["status"] !== "active"
                    ) {

                        increaseLoginAttempts();

                        $message =
                            "Unable to login with these credentials.";

                    }


                    /*
                     Verify password
                    */

                    elseif (
                        password_verify(
                            $password,
                            $user["password"]
                        )
                    ) {

                        /*
                         Regenerate session ID
                         to prevent session fixation.
                        */

                        session_regenerate_id(true);


                        /*
                         Store only necessary
                         session information.
                        */

                        $_SESSION["user_id"] =
                            (int) $user["id"];

                        $_SESSION["full_name"] =
                            $user["full_name"];

                        $_SESSION["role"] =
                            $user["role"];


                        /*
                         Reset failed attempts
                        */

                        resetLoginAttempts();


                        /*
                         Regenerate CSRF token
                         after successful login
                        */

                        $_SESSION["csrf_token"] =
                            bin2hex(
                                random_bytes(32)
                            );


                        /*
                         Redirect according to role
                        */

                        if (
                            $user["role"] === "student"
                        ) {

                            header(
                                "Location: student/dashboard.php"
                            );

                            exit;

                        } elseif (
                            $user["role"] === "recruiter"
                        ) {

                            header(
                                "Location: recruiter/dashboard.php"
                            );

                            exit;

                        } elseif (
                            $user["role"] === "admin"
                        ) {

                            header(
                                "Location: admin/dashboard.php"
                            );

                            exit;

                        } else {

                            /*
                             Unknown role:
                             destroy session.
                            */

                            session_unset();
                            session_destroy();

                            $message =
                                "Unable to login. Please contact the administrator.";
                        }

                    } else {

                        increaseLoginAttempts();

                        $message =
                            "Invalid email or password.";
                    }

                } else {

                    /*
                     Generic response:
                     do not reveal whether
                     an email exists.
                    */

                    increaseLoginAttempts();

                    $message =
                        "Invalid email or password.";
                }


                mysqli_stmt_close($stmt);

            } else {

                $message =
                    "Something went wrong. Please try again later.";
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

<title>Login | Smart Placement Portal</title>


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
            rgba(91, 124, 255, .20),
            transparent 30%
        ),
        radial-gradient(
            circle at 85% 75%,
            rgba(139, 92, 246, .18),
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

    z-index: 0;
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

    width: min(1180px, calc(100% - 40px));

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

    font-family: "Outfit", sans-serif;

    font-size: 15px;

    font-weight: 700;
}


.back-home {

    color: #cbd5e1;

    text-decoration: none;

    font-size: 12px;

    display: flex;

    align-items: center;

    gap: 8px;

    transition: .25s;
}


.back-home:hover {

    color: white;

    transform: translateX(-3px);
}


.auth-page {

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding:
        120px
        20px
        50px;

    position: relative;

    z-index: 1;
}


.orb {

    position: fixed;

    border-radius: 50%;

    filter: blur(80px);

    opacity: .35;

    pointer-events: none;
}


.orb-one {

    width: 320px;
    height: 320px;

    background: #2563eb;

    top: 20%;
    left: -150px;
}


.orb-two {

    width: 300px;
    height: 300px;

    background: #7c3aed;

    right: -120px;
    bottom: 10%;
}


.auth-wrapper {

    width: min(100%, 1000px);

    display: grid;

    grid-template-columns: 1fr 1fr;

    border-radius: 28px;

    overflow: hidden;

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

    padding: 55px 48px;

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

    padding: 8px 13px;

    border-radius: 30px;

    border:
        1px solid
        rgba(255,255,255,.10);

    background:
        rgba(255,255,255,.05);

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

    font-family: "Outfit", sans-serif;

    font-size: 45px;

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

    margin-bottom: 30px;
}


.info-points {

    list-style: none;
}


.info-points li {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 15px;

    color: #cbd5e1;

    font-size: 11px;
}


.info-points i {

    color: #60a5fa;
}


.auth-form-section {

    padding: 50px 45px;

    display: flex;

    flex-direction: column;

    justify-content: center;
}


.auth-logo {

    width: 55px;
    height: 55px;

    border-radius: 17px;

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
        0 15px 35px
        rgba(91,124,255,.28);

    margin-bottom: 20px;
}


.auth-form-section h2 {

    font-family: "Outfit", sans-serif;

    font-size: 30px;

    margin-bottom: 8px;
}


.auth-subtitle {

    color: #94a3b8;

    font-size: 12px;

    margin-bottom: 28px;
}


.alert {

    padding: 12px 14px;

    margin-bottom: 18px;

    border-radius: 10px;

    font-size: 11px;

    color: #fecaca;

    background:
        rgba(239,68,68,.10);

    border:
        1px solid
        rgba(239,68,68,.22);
}


.form-group {

    margin-bottom: 17px;
}


.form-label {

    display: block;

    color: #cbd5e1;

    font-size: 11px;

    font-weight: 600;

    margin-bottom: 8px;
}


.input-wrapper {

    position: relative;
}


.input-icon {

    position: absolute;

    top: 50%;
    left: 15px;

    transform: translateY(-50%);

    color: #64748b;

    font-size: 13px;
}


.form-control {

    width: 100%;

    height: 48px;

    border-radius: 11px;

    padding:
        0
        45px
        0
        43px;

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

    box-shadow:
        0 0 0 4px
        rgba(91,124,255,.10);
}


.password-toggle {

    position: absolute;

    right: 14px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    color: #64748b;

    cursor: pointer;
}


.auth-btn {

    width: 100%;

    height: 49px;

    border: none;

    border-radius: 11px;

    margin-top: 8px;

    cursor: pointer;

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

    transform: translateY(-2px);

    box-shadow:
        0 20px 40px
        rgba(91,124,255,.35);
}


.auth-footer {

    text-align: center;

    margin-top: 23px;

    color: #64748b;

    font-size: 11px;
}


.auth-footer a {

    color: #8ab4ff;

    font-weight: 600;

    text-decoration: none;
}


@media(max-width: 800px) {

    .auth-wrapper {

        max-width: 480px;

        grid-template-columns: 1fr;
    }

    .auth-info {

        display: none;
    }

    .auth-form-section {

        padding: 42px 30px;
    }
}


@media(max-width: 500px) {

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

    .back-home span {

        display: none;
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

<span>Back to Home</span>

</a>

</div>

</header>



<main class="auth-page">


<div class="auth-wrapper">


<div class="auth-info">

<div class="info-badge">

<span class="badge-dot"></span>

Your career journey starts here

</div>


<h1>

Welcome back.

<br>

<span class="gradient-text">

Keep moving forward.

</span>

</h1>


<p>

Access your personalized placement dashboard,
discover opportunities and continue building
your professional future.

</p>


<ul class="info-points">

<li>

<i class="fa-solid fa-circle-check"></i>

Manage your placement journey

</li>

<li>

<i class="fa-solid fa-circle-check"></i>

Track job applications

</li>

<li>

<i class="fa-solid fa-circle-check"></i>

Discover new opportunities

</li>

</ul>

</div>



<div class="auth-form-section">


<div class="auth-logo">

<i class="fa-solid fa-right-to-bracket"></i>

</div>


<h2>

Welcome Back

</h2>


<p class="auth-subtitle">

Login securely to continue your placement journey.

</p>


<?php if ($message): ?>

<div class="alert">

<i class="fa-solid fa-circle-exclamation"></i>

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST" autocomplete="on">


<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrfToken); ?>"
>


<div class="form-group">

<label class="form-label">

Email Address

</label>


<div class="input-wrapper">

<i class="fa-solid fa-envelope input-icon"></i>


<input
    type="email"
    name="email"
    class="form-control"
    placeholder="Enter your email"
    autocomplete="email"
    required
>

</div>

</div>



<div class="form-group">

<label class="form-label">

Password

</label>


<div class="input-wrapper">

<i class="fa-solid fa-lock input-icon"></i>


<input
    type="password"
    name="password"
    id="password"
    class="form-control"
    placeholder="Enter your password"
    autocomplete="current-password"
    required
>


<button
    type="button"
    class="password-toggle"
    onclick="togglePassword()"
    aria-label="Show or hide password"
>

<i
    class="fa-solid fa-eye"
    id="passwordIcon"
></i>

</button>

</div>

</div>


<button
    type="submit"
    class="auth-btn"
>

Login

<i class="fa-solid fa-arrow-right"></i>

</button>


</form>


<p class="auth-footer">

Don't have an account?

<a href="register.php">

Create Account

</a>

</p>


</div>


</div>


</main>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("passwordIcon");


    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");
    }
}

</script>


</body>

</html>