<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_name = $_SESSION['patient_name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard - Smart Care Assistant</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 30px; }
        .container { background: white; padding: 25px; border-radius: 10px; max-width: 700px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; }
        .nav-link { display: block; margin: 15px 0; padding: 12px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; text-align: center; }
        .nav-link:hover { background: #2980b9; }
        .logout { margin-top: 30px; text-align: center; }
        .logout a { color: red; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>👋 Welcome, <?= htmlspecialchars($patient_name) ?>!</h2>

        <a href="patient_tools.php" class="nav-link">🧰 Daily Logging & Health Tools</a>
        <a href="view_logs.php" class="nav-link">📅 View Symptom & Medication Logs</a>
        <a href="visualize_trends.php" class="nav-link">📈 Symptom Trends</a>
        <a href="reminder_settings.php" class="nav-link">⏰ Medication Reminder Settings</a>

        <div class="logout">
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</body>
</html>
