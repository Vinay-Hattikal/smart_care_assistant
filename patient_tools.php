<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle symptom and medication logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptoms'], $_POST['medication'])) {
    $symptoms = $_POST['symptoms'];
    $medication = $_POST['medication'];
    $stmt = $conn->prepare("INSERT INTO patient_logs (patient_id, symptoms, medication, log_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $patient_id, $symptoms, $medication);
    $stmt->execute();
}

// Fetch past logs
$logs = $conn->query("SELECT * FROM patient_logs WHERE patient_id = $patient_id ORDER BY log_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Tools</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea, input { width: 100%; margin-bottom: 10px; padding: 8px; }
        table, th, td { border: 1px solid #ccc; border-collapse: collapse; padding: 6px; }
        th { background: #f0f0f0; }
        canvas { max-width: 100%; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>🩺 Daily Logging & Trends</h2>

    <!-- Symptom/Medication Form -->
    <form method="POST">
        <label>📝 Today's Symptoms:</label>
        <textarea name="symptoms" required></textarea>

        <label>💊 Medication Taken:</label>
        <input type="text" name="medication" required>

        <button type="submit">📥 Submit Log</button>
    </form>

    <h3>📋 Your Past Logs</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Symptoms</th>
            <th>Medication</th>
        </tr>
        <?php while ($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?= $log['log_date'] ?></td>
                <td><?= htmlspecialchars($log['symptoms']) ?></td>
                <td><?= htmlspecialchars($log['medication']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Visualization -->
    <h3>📈 Symptoms Trend (Last 7 Days)</h3>
    <canvas id="symptomChart"></canvas>

    <?php
    // Count symptoms for chart
    $chartData = $conn->query("SELECT DATE(log_date) as log_date, COUNT(*) as count FROM patient_logs WHERE patient_id = $patient_id GROUP BY DATE(log_date) ORDER BY log_date DESC LIMIT 7");
    $dates = $counts = [];
    while ($row = $chartData->fetch_assoc()) {
        $dates[] = $row['log_date'];
        $counts[] = $row['count'];
    }
    ?>

    <script>
        const ctx = document.getElementById('symptomChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_reverse($dates)) ?>,
                datasets: [{
                    label: 'Symptom Entries',
                    data: <?= json_encode(array_reverse($counts)) ?>,
                    backgroundColor: 'rgba(75,192,192,0.2)',
                    borderColor: 'rgb(75,192,192)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                tension: 0.3
            }
        });
    </script>

    <!-- Reminders UI -->
    <h3>🔔 Set Daily Reminder</h3>
    <p>This is a placeholder UI. We’ll add notification system or email reminder in the next step.</p>
    <label>Reminder Time:</label>
    <input type="time" value="09:00 AM">
    <button disabled>🔔 Set Reminder (Coming Soon)</button>

    <br><br>
    <a href="patient_dashboard.php">← Back to Dashboard</a>
</body>
</html>
