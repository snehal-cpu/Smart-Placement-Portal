<?php
session_start();

/* =========================================================
   LOGIN STATUS
========================================================= */

$is_logged_in = isset($_SESSION['user_id']);

$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';


/* =========================================================
   DASHBOARD URL
========================================================= */

$dashboard_url = 'login.php';

if ($is_logged_in) {

    if ($user_role === 'student') {
        $dashboard_url = 'student/dashboard.php';
    } elseif ($user_role === 'recruiter') {
        $dashboard_url = 'recruiter/dashboard.php';
    }
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once 'config/database.php';


/* =========================================================
   LIVE STATISTICS
========================================================= */

$student_count = 0;
$company_count = 0;
$job_count = 0;
$application_count = 0;


/* STUDENTS */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM students"
);

if ($result) {
    $row = $result->fetch_assoc();
    $student_count = (int)$row['total'];
}


/* COMPANIES */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM companies"
);

if ($result) {
    $row = $result->fetch_assoc();
    $company_count = (int)$row['total'];
}


/* ACTIVE JOBS */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM jobs WHERE status = 'active'"
);

if ($result) {
    $row = $result->fetch_assoc();
    $job_count = (int)$row['total'];
}


/* APPLICATIONS */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM applications"
);

if ($result) {
    $row = $result->fetch_assoc();
    $application_count = (int)$row['total'];
}


/* =========================================================
   SAFE OUTPUT
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
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

    <title>Smart Placement Portal | Build Your Career</title>

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
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --bg: #07111f;
    --bg-dark: #030712;

    --primary: #5b7cff;
    --purple: #8b5cf6;
    --cyan: #22d3ee;

    --text: #f8fafc;
    --muted: #94a3b8;

    --white: #ffffff;

    --light-bg: #f7f9fc;

    --dark-text: #111827;

    --border: rgba(255,255,255,.12);

    --shadow:
        0 30px 80px rgba(2,6,23,.18);

}


/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;

}

html {

    scroll-behavior: smooth;

}

body {

    font-family: "DM Sans", sans-serif;

    background: var(--light-bg);

    color: var(--dark-text);

    overflow-x: hidden;

}

a {

    text-decoration: none;

}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {

    position: fixed;

    top: 0;
    left: 0;
    right: 0;

    z-index: 999;

    padding: 18px 0;

    transition: .35s ease;

}

.navbar.scrolled {

    background: rgba(7,17,31,.90);

    backdrop-filter: blur(20px);

    border-bottom:
        1px solid rgba(255,255,255,.08);

    padding: 12px 0;

}

.nav-container {

    width: min(1200px, calc(100% - 40px));

    margin: auto;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


/* LOGO */

.logo {

    display: flex;

    align-items: center;

    gap: 11px;

    color: white;

}

.logo-icon {

    width: 44px;
    height: 44px;

    display: flex;

    justify-content: center;
    align-items: center;

    border-radius: 14px;

    background:

        linear-gradient(
            135deg,
            var(--primary),
            var(--purple)
        );

    box-shadow:

        0 10px 30px
        rgba(91,124,255,.35);

}

.logo-text {

    font-family: "Outfit", sans-serif;

    font-weight: 700;

    font-size: 15px;

}


/* NAVIGATION */

.nav-links {

    display: flex;

    gap: 30px;

}

.nav-links a {

    color: #cbd5e1;

    font-size: 13px;

    font-weight: 500;

    transition: .2s;

}

.nav-links a:hover {

    color: white;

}


/* NAV BUTTONS */

.nav-buttons {

    display: flex;

    align-items: center;

    gap: 10px;

}

.login-btn {

    color: white;

    font-size: 13px;

    padding: 10px 16px;

}

.register-btn {

    padding: 11px 18px;

    border-radius: 10px;

    background: white;

    color: #111827;

    font-size: 12px;

    font-weight: 700;

    transition: .25s;

}

.register-btn:hover {

    transform: translateY(-2px);

    box-shadow:

        0 12px 30px
        rgba(0,0,0,.2);

}


/* =========================================================
   HERO
========================================================= */

.hero {

    position: relative;

    min-height: 820px;

    padding: 160px 20px 120px;

    background:

        radial-gradient(
            circle at 20% 30%,
            rgba(91,124,255,.22),
            transparent 32%
        ),

        radial-gradient(
            circle at 80% 25%,
            rgba(139,92,246,.18),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #030712,
            #07111f 55%,
            #0b1020
        );

    overflow: hidden;

}


/* GRID */

.hero::before {

    content: "";

    position: absolute;

    inset: 0;

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

    mask-image:

        linear-gradient(
            to bottom,
            black,
            transparent
        );

}


/* GLOW ORBS */

.orb {

    position: absolute;

    border-radius: 50%;

    filter: blur(70px);

    opacity: .35;

}

.orb-one {

    width: 350px;
    height: 350px;

    background: #2563eb;

    top: 120px;
    left: -150px;

}

.orb-two {

    width: 280px;
    height: 280px;

    background: #7c3aed;

    top: 180px;
    right: -100px;

}


.hero-container {

    width: min(1200px, calc(100% - 40px));

    margin: auto;

    position: relative;

    z-index: 2;

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 70px;

    align-items: center;

}


/* HERO TEXT */

.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 8px 14px;

    margin-bottom: 25px;

    border-radius: 30px;

    border:
        1px solid rgba(255,255,255,.12);

    background:
        rgba(255,255,255,.05);

    color: #cbd5e1;

    font-size: 11px;

}

.badge-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow:

        0 0 15px
        #22c55e;

}


.hero-title {

    font-family: "Outfit", sans-serif;

    font-size:

        clamp(
            48px,
            6vw,
            76px
        );

    line-height: 1.02;

    letter-spacing: -3px;

    font-weight: 800;

    color: white;

    margin-bottom: 25px;

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


.hero-description {

    max-width: 580px;

    color: #94a3b8;

    font-size: 15px;

    line-height: 1.8;

    margin-bottom: 30px;

}


.hero-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 12px;

    margin-bottom: 35px;

}


.primary-btn {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    padding: 14px 21px;

    border-radius: 11px;

    background:

        linear-gradient(
            135deg,
            #5b7cff,
            #7c3aed
        );

    color: white;

    font-size: 12px;

    font-weight: 700;

    box-shadow:

        0 15px 35px
        rgba(91,124,255,.30);

    transition: .25s;

}

.primary-btn:hover {

    transform:
        translateY(-3px);

    color: white;

}


.secondary-btn {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    padding: 14px 21px;

    border-radius: 11px;

    border:

        1px solid
        rgba(255,255,255,.15);

    background:

        rgba(255,255,255,.04);

    color: white;

    font-size: 12px;

    transition: .25s;

}

.secondary-btn:hover {

    background:

        rgba(255,255,255,.10);

    color: white;

}


/* TRUST */

.trust-row {

    display: flex;

    align-items: center;

    gap: 12px;

}

.trust-avatars {

    display: flex;

}

.trust-avatar {

    width: 30px;
    height: 30px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-left: -7px;

    border: 2px solid #07111f;

    background: #1e293b;

    color: white;

    font-size: 9px;

}

.trust-avatar:first-child {

    margin-left: 0;

}

.trust-text {

    font-size: 10px;

    color: #94a3b8;

}

.trust-text strong {

    display: block;

    color: white;

}


/* =========================================================
   HERO DASHBOARD
========================================================= */

.hero-dashboard {

    position: relative;

}

.dashboard-card {

    position: relative;

    padding: 18px;

    border-radius: 24px;

    background:

        rgba(255,255,255,.08);

    border:

        1px solid
        rgba(255,255,255,.12);

    backdrop-filter:

        blur(18px);

    box-shadow:

        0 35px 80px
        rgba(0,0,0,.30);

}


.dashboard-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

}

.dashboard-title {

    color: white;

    font-family: "Outfit", sans-serif;

    font-size: 16px;

    font-weight: 700;

}

.dashboard-subtitle {

    color: #94a3b8;

    font-size: 9px;

}

.profile-circle {

    width: 38px;
    height: 38px;

    border-radius: 12px;

    display: flex;

    align-items: center;
    justify-content: center;

    background:

        linear-gradient(
            135deg,
            #5b7cff,
            #8b5cf6
        );

    color: white;

}


/* DASHBOARD STATS */

.dashboard-stats {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 10px;

    margin-bottom: 16px;

}

.dash-stat {

    padding: 15px;

    border-radius: 15px;

    background:

        rgba(255,255,255,.06);

    border:

        1px solid
        rgba(255,255,255,.08);

}

.dash-label {

    color: #94a3b8;

    font-size: 8px;

}

.dash-value {

    margin-top: 4px;

    color: white;

    font-family: "Outfit";

    font-size: 22px;

    font-weight: 700;

}

.dash-value.blue {

    color: #60a5fa;

}

.dash-value.green {

    color: #4ade80;

}


/* JOB LIST */

.job-preview-title {

    color: white;

    font-size: 10px;

    font-weight: 600;

    margin-bottom: 9px;

}

.job-item {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 11px;

    margin-bottom: 7px;

    border-radius: 12px;

    background:

        rgba(255,255,255,.055);

}

.job-logo {

    width: 32px;
    height: 32px;

    border-radius: 9px;

    display: flex;

    align-items: center;
    justify-content: center;

    background:

        rgba(91,124,255,.16);

    color: #8ab4ff;

}

.job-info {

    flex: 1;

}

.job-title {

    color: white;

    font-size: 9px;

    font-weight: 600;

}

.job-company {

    color: #64748b;

    font-size: 7px;

}

.job-tag {

    padding: 5px 8px;

    border-radius: 6px;

    background:

        rgba(34,197,94,.12);

    color: #4ade80;

    font-size: 7px;

}


/* FLOATING CARDS */

.float-card {

    position: absolute;

    padding: 13px;

    border-radius: 15px;

    background:

        rgba(255,255,255,.12);

    border:

        1px solid
        rgba(255,255,255,.15);

    backdrop-filter:
        blur(15px);

    color: white;

    box-shadow:

        0 20px 50px
        rgba(0,0,0,.25);

}

.float-one {

    left: -55px;

    top: 70px;

    animation:
        float 4s ease-in-out infinite;

}

.float-two {

    right: -45px;

    bottom: 35px;

    animation:
        float 5s ease-in-out infinite;

}

.float-icon {

    color: #60a5fa;

    margin-bottom: 7px;

}

.float-number {

    font-family: "Outfit";

    font-size: 20px;

    font-weight: 700;

}

.float-label {

    color: #94a3b8;

    font-size: 8px;

}


@keyframes float {

    0%,
    100% {

        transform:
            translateY(0);

    }

    50% {

        transform:
            translateY(-10px);

    }

}


/* =========================================================
   STATS
========================================================= */

.stats-section {

    position: relative;

    margin-top: -60px;

    z-index: 5;

}

.stats-container {

    width:

        min(
            1080px,
            calc(100% - 40px)
        );

    margin: auto;

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    background: white;

    border-radius: 22px;

    box-shadow:

        0 25px 70px
        rgba(15,23,42,.12);

    overflow: hidden;

}

.stat-box {

    padding: 28px 20px;

    text-align: center;

    border-right:

        1px solid #eef2f7;

}

.stat-box:last-child {

    border-right: none;

}

.stat-number {

    font-family: "Outfit";

    font-size: 28px;

    font-weight: 700;

    color: #111827;

}

.stat-number span {

    color: #5b7cff;

}

.stat-label {

    color: #94a3b8;

    font-size: 10px;

    margin-top: 4px;

}


/* =========================================================
   SECTIONS
========================================================= */

.section {

    padding:

        110px
        20px;

}

.container {

    width:

        min(
            1150px,
            calc(100% - 40px)
        );

    margin: auto;

}


.section-header {

    max-width: 650px;

    margin:

        0 auto
        55px;

    text-align: center;

}

.section-tag {

    color: #6366f1;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 10px;

}

.section-title {

    font-family: "Outfit";

    font-size: 40px;

    line-height: 1.15;

    letter-spacing: -1.5px;

    color: #111827;

    margin-bottom: 14px;

}

.section-description {

    color: #64748b;

    font-size: 13px;

    line-height: 1.8;

}


/* =========================================================
   FEATURES
========================================================= */

.features-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 18px;

}

.feature-card {

    position: relative;

    padding: 28px;

    border-radius: 20px;

    background: white;

    border:

        1px solid #edf0f5;

    transition: .3s;

    overflow: hidden;

}

.feature-card::before {

    content: "";

    position: absolute;

    width: 130px;
    height: 130px;

    border-radius: 50%;

    background:

        radial-gradient(
            circle,
            rgba(91,124,255,.12),
            transparent
        );

    right: -50px;

    top: -50px;

}

.feature-card:hover {

    transform:
        translateY(-8px);

    box-shadow:

        0 25px 50px
        rgba(15,23,42,.10);

}

.feature-icon {

    width: 50px;
    height: 50px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 15px;

    margin-bottom: 18px;

    color: #5b7cff;

    background:

        linear-gradient(
            135deg,
            #eef2ff,
            #eff6ff
        );

}

.feature-card h3 {

    font-family: "Outfit";

    font-size: 16px;

    margin-bottom: 9px;

}

.feature-card p {

    color: #64748b;

    font-size: 11px;

    line-height: 1.8;

}


/* =========================================================
   AUDIENCE
========================================================= */

.audience {

    background:

        linear-gradient(
            180deg,
            #f8fafc,
            #eef2ff
        );

}

.audience-grid {

    display: grid;

    grid-template-columns:
        repeat(2,1fr);

    gap: 22px;

}

.audience-card {

    position: relative;

    padding: 40px;

    overflow: hidden;

    border-radius: 24px;

    background: white;

    box-shadow:

        0 20px 50px
        rgba(15,23,42,.06);

}

.audience-card::after {

    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    border-radius: 50%;

    right: -100px;
    bottom: -120px;

    background:

        linear-gradient(
            135deg,
            rgba(91,124,255,.14),
            rgba(139,92,246,.08)
        );

}

.audience-icon {

    width: 58px;
    height: 58px;

    display: flex;

    justify-content: center;
    align-items: center;

    border-radius: 18px;

    background:

        linear-gradient(
            135deg,
            #5b7cff,
            #8b5cf6
        );

    color: white;

    font-size: 20px;

    margin-bottom: 20px;

}

.audience-card h3 {

    font-family: "Outfit";

    font-size: 24px;

    margin-bottom: 10px;

}

.audience-card > p {

    color: #64748b;

    font-size: 12px;

    line-height: 1.8;

    margin-bottom: 22px;

}

.feature-list {

    list-style: none;

}

.feature-list li {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 11px;

    font-size: 11px;

    color: #475569;

}

.feature-list i {

    color: #22c55e;

}


/* =========================================================
   HOW IT WORKS
========================================================= */

.steps-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 25px;

}

.step-card {

    text-align: center;

    padding: 25px;

}

.step-number {

    width: 65px;
    height: 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin:

        0 auto
        20px;

    border-radius: 20px;

    color: #5b7cff;

    font-family: "Outfit";

    font-weight: 700;

    background:

        linear-gradient(
            135deg,
            #eef2ff,
            #eff6ff
        );

}

.step-card h3 {

    font-family: "Outfit";

    font-size: 17px;

    margin-bottom: 9px;

}

.step-card p {

    color: #64748b;

    font-size: 11px;

    line-height: 1.8;

}


/* =========================================================
   CTA
========================================================= */

.cta-section {

    padding:

        20px
        20px
        100px;

}

.cta {

    position: relative;

    overflow: hidden;

    width:

        min(
            1150px,
            calc(100% - 40px)
        );

    margin: auto;

    padding:

        75px
        30px;

    border-radius: 30px;

    text-align: center;

    background:

        radial-gradient(
            circle at top right,
            rgba(139,92,246,.5),
            transparent 35%
        ),

        linear-gradient(
            135deg,
            #07111f,
            #111c35
        );

    color: white;

}

.cta::before {

    content: "";

    position: absolute;

    width: 350px;
    height: 350px;

    border-radius: 50%;

    border:

        1px solid
        rgba(255,255,255,.08);

    left: -150px;
    bottom: -180px;

}

.cta-content {

    position: relative;

    z-index: 2;

}

.cta h2 {

    font-family: "Outfit";

    font-size: 42px;

    letter-spacing: -1.5px;

    margin-bottom: 12px;

}

.cta p {

    max-width: 560px;

    margin:

        0 auto
        25px;

    color: #94a3b8;

    font-size: 13px;

}

.cta-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:

        14px
        22px;

    border-radius: 11px;

    background: white;

    color: #111827;

    font-size: 12px;

    font-weight: 700;

}


/* =========================================================
   FOOTER
========================================================= */

footer {

    padding:

        55px
        20px
        25px;

    background: #030712;

    color: white;

}

.footer-container {

    width:

        min(
            1150px,
            calc(100% - 40px)
        );

    margin: auto;

}

.footer-grid {

    display: grid;

    grid-template-columns:
        1.6fr
        1fr
        1fr;

    gap: 60px;

    padding-bottom: 40px;

    border-bottom:

        1px solid
        rgba(255,255,255,.08);

}

.footer-logo {

    display: flex;

    align-items: center;

    gap: 10px;

    font-family: "Outfit";

    font-weight: 700;

    margin-bottom: 14px;

}

.footer-description {

    max-width: 360px;

    color: #64748b;

    font-size: 10px;

    line-height: 1.8;

}

.footer-column h4 {

    color: #cbd5e1;

    font-size: 10px;

    margin-bottom: 15px;

}

.footer-column a {

    display: block;

    color: #64748b;

    font-size: 10px;

    margin-bottom: 10px;

}

.footer-column a:hover {

    color: white;

}

.footer-bottom {

    padding-top: 22px;

    display: flex;

    justify-content: space-between;

    color: #475569;

    font-size: 9px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 950px) {

    .nav-links {
        display: none;
    }

    .hero-container {

        grid-template-columns: 1fr;

        text-align: center;

    }

    .hero-description {

        margin-left: auto;
        margin-right: auto;

    }

    .hero-buttons {

        justify-content: center;

    }

    .trust-row {

        justify-content: center;

    }

    .hero-dashboard {

        max-width: 650px;

        margin: auto;

    }

    .features-grid {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width: 700px) {

    .stats-container {

        grid-template-columns:
            repeat(2,1fr);

    }

    .stat-box {

        border-bottom:
            1px solid #eef2f7;

    }

    .features-grid {

        grid-template-columns: 1fr;

    }

    .audience-grid {

        grid-template-columns: 1fr;

    }

    .steps-grid {

        grid-template-columns: 1fr;

    }

    .footer-grid {

        grid-template-columns:
            1fr
            1fr;

    }

}


@media(max-width: 520px) {

    .nav-container {

        width:
            calc(100% - 28px);

    }

    .logo-text {

        font-size: 12px;

    }

    .login-btn {

        display: none;

    }

    .hero {

        padding-top: 135px;

    }

    .hero-title {

        font-size: 46px;

        letter-spacing: -2px;

    }

    .hero-buttons {

        flex-direction: column;

    }

    .primary-btn,
    .secondary-btn {

        justify-content: center;

    }

    .dashboard-stats {

        grid-template-columns: 1fr;

    }

    .float-one {

        left: -5px;

    }

    .float-two {

        right: -5px;

    }

    .section-title {

        font-size: 32px;

    }

    .cta h2 {

        font-size: 32px;

    }

    .footer-grid {

        grid-template-columns: 1fr;

    }

    .footer-bottom {

        flex-direction: column;

        gap: 8px;

        text-align: center;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header
    class="navbar"
    id="navbar"
>

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


<nav class="nav-links">

<a href="#features">
Features
</a>

<a href="#students">
Students
</a>

<a href="#recruiters">
Recruiters
</a>

<a href="#how-it-works">
How It Works
</a>

</nav>


<div class="nav-buttons">

<?php if ($is_logged_in): ?>

<a
href="<?php echo e($dashboard_url); ?>"
class="login-btn"
>
Dashboard
</a>

<a
href="logout.php"
class="register-btn"
>
Logout
</a>

<?php else: ?>

<a
href="login.php"
class="login-btn"
>
Login
</a>

<a
href="register.php"
class="register-btn"
>
Get Started
</a>

<?php endif; ?>

</div>


</div>

</header>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">


<div class="orb orb-one"></div>

<div class="orb orb-two"></div>


<div class="hero-container">


<!-- HERO CONTENT -->

<div>


<div class="hero-badge">

<span class="badge-dot"></span>

Smart Career & Placement Platform

</div>


<h1 class="hero-title">

Your career.

<br>

Your future.

<br>

<span class="gradient-text">

One smart platform.

</span>

</h1>


<p class="hero-description">

Connect with opportunities that match your skills,
showcase your achievements and take control of your
placement journey with one modern platform built for
students and recruiters.

</p>


<div class="hero-buttons">


<?php if ($is_logged_in): ?>

<a
href="<?php echo e($dashboard_url); ?>"
class="primary-btn"
>

Open Dashboard

<i class="fa-solid fa-arrow-right"></i>

</a>


<?php else: ?>

<a
href="register.php"
class="primary-btn"
>

Start Your Journey

<i class="fa-solid fa-arrow-right"></i>

</a>


<a
href="login.php"
class="secondary-btn"
>

<i class="fa-solid fa-right-to-bracket"></i>

Sign In

</a>

<?php endif; ?>


</div>


<div class="trust-row">


<div class="trust-avatars">

<div class="trust-avatar">
<i class="fa-solid fa-user"></i>
</div>

<div class="trust-avatar">
<i class="fa-solid fa-code"></i>
</div>

<div class="trust-avatar">
<i class="fa-solid fa-building"></i>
</div>

<div class="trust-avatar">
<i class="fa-solid fa-graduation-cap"></i>
</div>

</div>


<div class="trust-text">

<strong>
Built for ambitious careers
</strong>

Students • Recruiters • Opportunities

</div>


</div>


</div>



<!-- DASHBOARD -->

<div class="hero-dashboard">


<div class="dashboard-card">


<div class="dashboard-top">

<div>

<div class="dashboard-title">

Placement Dashboard

</div>

<div class="dashboard-subtitle">

Track your career journey

</div>

</div>


<div class="profile-circle">

<i class="fa-solid fa-user"></i>

</div>


</div>



<div class="dashboard-stats">


<div class="dash-stat">

<div class="dash-label">

Applications

</div>

<div class="dash-value blue">

12

</div>

</div>



<div class="dash-stat">

<div class="dash-label">

Shortlisted

</div>

<div class="dash-value green">

04

</div>

</div>



<div class="dash-stat">

<div class="dash-label">

Profile

</div>

<div class="dash-value">

92%

</div>

</div>


</div>



<div class="job-preview-title">

Recommended Opportunities

</div>



<div class="job-item">


<div class="job-logo">

<i class="fa-solid fa-code"></i>

</div>


<div class="job-info">

<div class="job-title">

Software Developer

</div>

<div class="job-company">

Technology Company • Pune

</div>

</div>


<div class="job-tag">

Match

</div>


</div>



<div class="job-item">


<div class="job-logo">

<i class="fa-solid fa-chart-line"></i>

</div>


<div class="job-info">

<div class="job-title">

Data Analyst

</div>

<div class="job-company">

Analytics Company • Mumbai

</div>

</div>


<div class="job-tag">

New

</div>


</div>



<div class="job-item">


<div class="job-logo">

<i class="fa-solid fa-laptop-code"></i>

</div>


<div class="job-info">

<div class="job-title">

Web Developer

</div>

<div class="job-company">

Digital Company • Remote

</div>

</div>


<div class="job-tag">

Open

</div>


</div>


</div>



<!-- FLOATING CARD 1 -->

<div class="float-card float-one">

<div class="float-icon">

<i class="fa-solid fa-fire"></i>

</div>

<div class="float-number">

92%

</div>

<div class="float-label">

Profile Strength

</div>

</div>



<!-- FLOATING CARD 2 -->

<div class="float-card float-two">

<div class="float-icon">

<i class="fa-solid fa-briefcase"></i>

</div>

<div class="float-number">

<?php echo $job_count; ?>+

</div>

<div class="float-label">

Active Jobs

</div>

</div>


</div>


</div>

</section>



<!-- =========================================================
     STATS
========================================================= -->

<section class="stats-section">

<div class="stats-container">


<div class="stat-box">

<div class="stat-number">

<?php echo $student_count; ?><span>+</span>

</div>

<div class="stat-label">

Student Profiles

</div>

</div>



<div class="stat-box">

<div class="stat-number">

<?php echo $job_count; ?><span>+</span>

</div>

<div class="stat-label">

Active Opportunities

</div>

</div>



<div class="stat-box">

<div class="stat-number">

<?php echo $company_count; ?><span>+</span>

</div>

<div class="stat-label">

Recruiters

</div>

</div>



<div class="stat-box">

<div class="stat-number">

<?php echo $application_count; ?><span>+</span>

</div>

<div class="stat-label">

Applications

</div>

</div>


</div>

</section>



<!-- =========================================================
     FEATURES
========================================================= -->

<section
class="section"
id="features"
>

<div class="container">


<div class="section-header">

<div class="section-tag">

POWERFUL FEATURES

</div>


<h2 class="section-title">

Everything you need for your placement journey.

</h2>


<p class="section-description">

One platform to manage profiles, opportunities,
applications and recruitment in a smarter and
more organized way.

</p>


</div>



<div class="features-grid">


<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-briefcase"></i>

</div>

<h3>

Discover Opportunities

</h3>

<p>

Explore placement opportunities that match your
skills, education and career goals.

</p>

</div>



<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-file-circle-check"></i>

</div>

<h3>

Easy Applications

</h3>

<p>

Apply to suitable jobs quickly and manage all
your applications from one place.

</p>

</div>



<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-chart-line"></i>

</div>

<h3>

Track Progress

</h3>

<p>

Stay updated about application status,
shortlisting and your placement journey.

</p>

</div>



<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-user-graduate"></i>

</div>

<h3>

Professional Profile

</h3>

<p>

Showcase your education, CGPA, skills,
projects and resume professionally.

</p>

</div>



<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-building"></i>

</div>

<h3>

Recruiter Portal

</h3>

<p>

Recruiters can post opportunities and review
applications efficiently.

</p>

</div>



<div class="feature-card">

<div class="feature-icon">

<i class="fa-solid fa-shield-halved"></i>

</div>

<h3>

Smart & Secure

</h3>

<p>

Role-based access keeps student and recruiter
experiences organized and secure.

</p>

</div>


</div>


</div>

</section>



<!-- =========================================================
     STUDENTS / RECRUITERS
========================================================= -->

<section
class="section audience"
id="students"
>

<div class="container">


<div class="section-header">

<div class="section-tag">

BUILT FOR EVERYONE

</div>


<h2 class="section-title">

Two journeys. One powerful platform.

</h2>


<p class="section-description">

Designed to help students discover their potential
and recruiters discover talented candidates.

</p>


</div>



<div class="audience-grid">


<!-- STUDENTS -->

<div class="audience-card">


<div class="audience-icon">

<i class="fa-solid fa-user-graduate"></i>

</div>


<h3>

For Students

</h3>


<p>

Create a professional profile and manage your
complete placement journey in one place.

</p>


<ul class="feature-list">

<li>
<i class="fa-solid fa-circle-check"></i>
Create your student profile
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Add academic details and CGPA
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Showcase technical skills
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Upload your resume
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Apply and track opportunities
</li>

</ul>


</div>



<!-- RECRUITERS -->

<div
class="audience-card"
id="recruiters"
>


<div class="audience-icon">

<i class="fa-solid fa-building"></i>

</div>


<h3>

For Recruiters

</h3>


<p>

Manage opportunities, review applications and
connect with promising student candidates.

</p>


<ul class="feature-list">

<li>
<i class="fa-solid fa-circle-check"></i>
Create company profile
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Post job opportunities
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Review applications
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
View candidate profiles
</li>

<li>
<i class="fa-solid fa-circle-check"></i>
Manage recruitment process
</li>

</ul>


</div>


</div>


</div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
class="section"
id="how-it-works"
>

<div class="container">


<div class="section-header">

<div class="section-tag">

HOW IT WORKS

</div>


<h2 class="section-title">

Your journey in three simple steps.

</h2>


<p class="section-description">

Start your profile, discover opportunities and
track every step of your career journey.

</p>


</div>



<div class="steps-grid">


<div class="step-card">

<div class="step-number">

01

</div>

<h3>

Build Your Profile

</h3>

<p>

Add your education, skills, CGPA and resume.

</p>

</div>



<div class="step-card">

<div class="step-number">

02

</div>

<h3>

Discover & Apply

</h3>

<p>

Explore relevant opportunities and submit
applications easily.

</p>

</div>



<div class="step-card">

<div class="step-number">

03

</div>

<h3>

Track Your Progress

</h3>

<p>

Stay updated about application status and
recruitment progress.

</p>

</div>


</div>


</div>

</section>



<!-- =========================================================
     CTA
========================================================= -->

<section class="cta-section">

<div class="cta">


<div class="cta-content">


<?php if ($is_logged_in): ?>


<h2>

Welcome back,
<?php echo e($user_name); ?>.

</h2>


<p>

Continue your placement journey and explore
new opportunities from your dashboard.

</p>


<a
href="<?php echo e($dashboard_url); ?>"
class="cta-btn"
>

Open Dashboard

<i class="fa-solid fa-arrow-right"></i>

</a>



<?php else: ?>


<h2>

Your next opportunity starts here.

</h2>


<p>

Create your profile today and take the next
step toward your career.

</p>


<a
href="register.php"
class="cta-btn"
>

Get Started Now

<i class="fa-solid fa-arrow-right"></i>

</a>


<?php endif; ?>


</div>


</div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

<div class="footer-container">


<div class="footer-grid">


<div>


<div class="footer-logo">

<div class="logo-icon">

<i class="fa-solid fa-graduation-cap"></i>

</div>

Smart Placement Portal

</div>


<p class="footer-description">

A modern platform connecting students,
recruiters and career opportunities in one
smart placement ecosystem.

</p>


</div>



<div class="footer-column">

<h4>

Platform

</h4>

<a href="#features">
Features
</a>

<a href="#students">
Students
</a>

<a href="#recruiters">
Recruiters
</a>

<a href="#how-it-works">
How It Works
</a>

</div>



<div class="footer-column">

<h4>

Account

</h4>

<a href="login.php">
Login
</a>

<a href="register.php">
Register
</a>

<?php if ($is_logged_in): ?>

<a
href="<?php echo e($dashboard_url); ?>"
>
Dashboard
</a>

<?php endif; ?>

</div>


</div>



<div class="footer-bottom">

<span>

© <?php echo date("Y"); ?>
Smart Placement Portal

</span>


<span>

Build. Apply. Achieve.

</span>


</div>


</div>

</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const navbar =
document.getElementById("navbar");


window.addEventListener(
"scroll",
function() {

if (window.scrollY > 30) {

navbar.classList.add("scrolled");

}

else {

navbar.classList.remove("scrolled");

}

}
);


document
.querySelectorAll(
'a[href^="#"]'
)
.forEach(
function(link) {

link.addEventListener(
"click",
function(e) {

const id =
this.getAttribute("href");

const target =
document.querySelector(id);

if (target) {

e.preventDefault();

target.scrollIntoView({

behavior: "smooth"

});

}

}
);

}
);

</script>


</body>
</html>