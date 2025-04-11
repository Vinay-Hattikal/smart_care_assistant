<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}
$doctor_id = $_SESSION['doctor_id'];
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get today's date
$today = date("Y-m-d");

// Fetch today's patients
$stmt = $conn->prepare("SELECT * FROM patients WHERE doctor_id = ? AND DATE(created_at) = ?");
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$today_patients = $stmt->get_result();

// Smart Alert Logic
$critical_patients = [];
foreach ($today_patients as $row) {
    $vitals = strtolower($row['vitals']);
    $critical = false;
    if (preg_match('/bp[^\d]*(\d{2,3})\/(\d{2,3})/', $vitals, $bp)) {
        if ((int)$bp[1] < 90 || (int)$bp[2] < 60) $critical = true;
    }
    if (strpos($vitals, 'temp') !== false && preg_match('/temp[^\d]*(\d{2,3})/', $vitals, $temp)) {
        if ((int)$temp[1] > 102) $critical = true;
    }
    if ($critical) $critical_patients[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Alerts & Daily Summary</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f0f0f0; }
        .alert { background-color: #ffe0e0; color: red; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<h2>⚠️ Smart Alerts for Critical Patients</h2>
<?php if (count($critical_patients) > 0): ?>
    <div class="alert">
        <strong>Critical alerts found for:</strong><br>
        <ul>
            <?php foreach ($critical_patients as $p): ?>
                <li><?= htmlspecialchars($p['name']) ?> (<?= $p['vitals'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else: ?>
    <p>No critical vitals detected today. ✅</p>
<?php endif; ?>

<h2>🧾 Today's Patient Summary</h2>
<table>
    <tr>
        <th>ID</th><th>Name</th><th>Symptoms</th><th>Vitals</th><th>Registered Time</th>
    </tr>
    <?php foreach ($today_patients as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['symptoms']) ?></td>
            <td><?= htmlspecialchars($p['vitals']) ?></td>
            <td><?= $p['created_at'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<br>
<a href="dashboard.php">← Back to Dashboard</a>

</body>
</html>
