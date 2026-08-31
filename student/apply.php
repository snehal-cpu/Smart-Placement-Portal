<?php
session_start();

require_once("../config/database.php");

// Check if student is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION["user_id"];

// Make sure only students can apply
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    die("Only students can apply for jobs.");
}

// Check job ID
if (!isset($_GET["job_id"]) || empty($_GET["job_id"])) {
    die("Job ID is missing.");
}

$job_id = intval($_GET["job_id"]);

// Check whether job exists
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM jobs WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    die("Job not found.");
}

// Check whether student already applied
$stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM applications
     WHERE student_id = ? AND job_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "ii", $student_id, $job_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    echo "<script>
        alert('You have already applied for this job.');
        window.location.href='job_details.php?job_id=$job_id';
    </script>";

    exit();
}

// Insert application
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO applications
     (student_id, job_id, status)
     VALUES (?, ?, 'Applied')"
);

mysqli_stmt_bind_param($stmt, "ii", $student_id, $job_id);

if (mysqli_stmt_execute($stmt)) {

    echo "<script>
        alert('Application submitted successfully!');
        window.location.href='job_details.php?job_id=$job_id';
    </script>";

} else {

    echo "Application failed: " . mysqli_error($conn);
}
?>