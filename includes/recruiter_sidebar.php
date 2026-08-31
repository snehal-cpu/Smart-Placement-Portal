<?php

$current_page = basename($_SERVER["PHP_SELF"]);

?>

<aside class="sidebar" id="sidebar">

    <!-- BRAND -->
    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-building"></i>
        </div>

        <div class="brand-text">

            <div class="brand-title">
                Smart Placement
            </div>

            <div class="brand-subtitle">
                RECRUITER PORTAL
            </div>

        </div>

    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-nav">

        <div class="nav-section">

            <div class="nav-label">
                MAIN MENU
            </div>

            <a href="dashboard.php"
               class="nav-link <?php echo $current_page === "dashboard.php" ? "active" : ""; ?>">

                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>

            </a>


            <a href="company_profile.php"
               class="nav-link <?php echo $current_page === "company_profile.php" ? "active" : ""; ?>">

                <i class="fa-solid fa-building"></i>
                <span>Company Profile</span>

            </a>

        </div>


        <div class="nav-section">

            <div class="nav-label">
                JOB MANAGEMENT
            </div>


            <a href="post_job.php"
               class="nav-link <?php echo $current_page === "post_job.php" ? "active" : ""; ?>">

                <i class="fa-solid fa-circle-plus"></i>
                <span>Post Job</span>

            </a>


            <a href="manage_jobs.php"
               class="nav-link <?php echo $current_page === "manage_jobs.php" ? "active" : ""; ?>">

                <i class="fa-solid fa-briefcase"></i>
                <span>Manage Jobs</span>

            </a>


            <a href="applicants.php"
               class="nav-link <?php echo $current_page === "applicants.php" ? "active" : ""; ?>">

                <i class="fa-solid fa-users"></i>
                <span>Applicants</span>

            </a>

        </div>

    </nav>


    <!-- LOGOUT AT BOTTOM -->

    <div class="sidebar-footer">

        <a href="../logout.php"
           class="nav-link logout-link">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Logout</span>

        </a>

    </div>

</aside>


<div class="sidebar-overlay" id="sidebarOverlay"></div>