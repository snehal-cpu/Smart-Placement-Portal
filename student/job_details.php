<?php
session_start();

require_once "../config/database.php";

/* Check job ID */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Job ID is missing.");
}

$job_id = intval($_GET['id']);

/* Fetch job */
$stmt = mysqli_prepare($conn, "SELECT * FROM jobs WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Job not found.");
}

$job = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($job['job_title']); ?> - Job Details</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .job-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            color: #222;
        }

        .job-type {
            display: inline-block;
            background: #e8f0ff;
            color: #2563eb;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .info-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .label {
            font-size: 13px;
            color: #777;
            margin-bottom: 5px;
        }

        .value {
            font-weight: bold;
            color: #222;
        }

        .description {
            margin-top: 25px;
        }

        .description h2 {
            margin-bottom: 10px;
        }

        .description p {
            line-height: 1.7;
            color: #555;
            white-space: pre-line;
        }

        .apply-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .apply-btn:hover {
            background: #1d4ed8;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        @media(max-width: 600px) {
            body {
                padding: 20px;
            }

            .info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<?php require_once "../includes/student_sidebar.php"; ?>
<div class="container">

    <a href="jobs.php" class="back-btn">← Back to Jobs</a>

    <div class="job-card">

        <h1>
            <?php echo htmlspecialchars($job['job_title']); ?>
        </h1>

        <span class="job-type">
            <?php echo htmlspecialchars($job['job_type']); ?>
        </span>

        <div class="info">

            <div class="info-box">
                <div class="label">📍 Location</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['location'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">💰 Salary</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['salary'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">🎓 Minimum CGPA</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['min_cgpa'] ?? '0'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">🏫 Eligible Department</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['eligible_department'] ?? 'All'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">📚 Eligible Year</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['eligible_year'] ?? 'All'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">👥 Vacancies</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['vacancies'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">📅 Application Deadline</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['application_deadline'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">📌 Status</div>
                <div class="value">
                    <?php echo htmlspecialchars($job['status']); ?>
                </div>
            </div>

        </div>

        <div class="description">

            <h2>Job Description</h2>

            <p>
                <?php echo htmlspecialchars($job['description']); ?>
            </p>

        </div>

        <?php if ($job['status'] === 'Open'): ?>

            <a href="apply.php?job_id=<?php echo $job['id']; ?>"
               class="apply-btn">
                Apply Now
            </a>

        <?php else: ?>

            <p style="color:red; font-weight:bold;">
                Applications are currently closed for this job.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>