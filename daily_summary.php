<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$mysqli = new mysqli("localhost", "root", "", "smart_care_assistant");

// Check DB connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Today's date
$today = date('Y-m-d');

// Prepare query to get data only if both entries exist
$sql = "
SELECT 
    p.name AS patient_name,
    pl.symptoms,
    tp.task,
    tp.prescription
FROM 
    patient_logs pl
JOIN 
    tasks_prescriptions tp ON pl.patient_id = tp.patient_id AND pl.date = tp.date
JOIN 
    patients p ON pl.patient_id = p.id
WHERE 
    tp.doctor_id = ? AND pl.date = ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Summary - Smart Care Assistant</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        h2 { margin-bottom: 20px; }
        .summary { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <h2>📋 Auto-Generated Patient Summary for Today</h2>

    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='summary'>";
            echo "<strong>👤 Patient:</strong> " . htmlspecialchars($row['patient_name']) . "<br>";
            echo "<strong>🩺 Symptoms:</strong> " . nl2br(htmlspecialchars($row['symptoms'])) . "<br>";
            echo "<strong>📌 Task:</strong> " . nl2br(htmlspecialchars($row['task'])) . "<br>";
            echo "<strong>💊 Prescription:</strong> " . nl2br(htmlspecialchars($row['prescription'])) . "<br>";
            echo "</div>";
        }
    } else {
        echo "<p>No summaries available for today. Make sure both symptoms and prescriptions are logged.</p>";
    }

    $stmt->close();
    $mysqli->close();
    ?>

    <br>
    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
