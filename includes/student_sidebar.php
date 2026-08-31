<?php

$current_page = basename($_SERVER["PHP_SELF"]);

?>

<aside
    class="sidebar"
    id="sidebar"
>

    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">

            <i class="fa-solid fa-graduation-cap"></i>

        </div>


        <div class="brand-text">

            <strong>
                Smart Placement
            </strong>

            <span>
                STUDENT PORTAL
            </span>

        </div>

    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-nav">


        <!-- MAIN MENU -->

        <div class="nav-section-title">

            Main Menu

        </div>


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="nav-link <?php
                echo $current_page === 'dashboard.php'
                    ? 'active'
                    : '';
            ?>"
        >

            <i class="fa-solid fa-chart-line"></i>

            <span>
                Dashboard
            </span>

        </a>



        <!-- PLACEMENT -->

        <div
            class="nav-section-title"
            style="margin-top: 25px;"
        >

            Placement

        </div>


        <!-- EXPLORE JOBS -->

        <a
            href="jobs.php"
            class="nav-link <?php
                echo $current_page === 'jobs.php'
                    ? 'active'
                    : '';
            ?>"
        >

            <i class="fa-solid fa-briefcase"></i>

            <span>
                Explore Jobs
            </span>

        </a>



        <!-- MY APPLICATIONS -->

        <a
            href="applications.php"
            class="nav-link <?php
                echo $current_page === 'applications.php'
                    ? 'active'
                    : '';
            ?>"
        >

            <i class="fa-solid fa-file-lines"></i>

            <span>
                My Applications
            </span>

        </a>



        <!-- ACCOUNT -->

        <div
            class="nav-section-title"
            style="margin-top: 25px;"
        >

            Account

        </div>


        <!-- PROFILE -->

        <a
            href="profile.php"
            class="nav-link <?php
                echo $current_page === 'profile.php'
                    ? 'active'
                    : '';
            ?>"
        >

            <i class="fa-solid fa-user"></i>

            <span>
                My Profile
            </span>

        </a>


    </nav>



    <!-- LOGOUT AT BOTTOM -->

    <div class="sidebar-bottom">


        <div class="sidebar-divider"></div>


        <a
            href="../logout.php"
            class="nav-link logout-link"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </div>


</aside>


<!-- MOBILE OVERLAY -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>