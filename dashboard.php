<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}
$doctor_name = $_SESSION['doctor_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard - Smart Care Assistant</title>
</head>
<body>
    <h2>Welcome, Dr. <?php echo $doctor_name; ?> 👨‍⚕️</h2>
    <p>This is your dashboard. Patient records and alerts will appear here in the next step.</p>
    <p><a href="add_patient.php">➕ Add New Patient</a></p>
    <p><a href="view_patients.php">📋 View All Patients</a></p>
    <p></p><a href="smart_alerts.php">🚨 Smart Alerts & Daily Summary</a></p>
    <a href="daily_summary.php">📄 Daily Patient Summary</a>


    <a href="advanced_tools.php">🧪 Advanced Tools</a>


   <p> <a href="logout.php">Logout</a></p>
</body>
</html>
