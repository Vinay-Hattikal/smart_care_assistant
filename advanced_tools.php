<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle notes submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'], $_POST['patient_id'], $_POST['follow_up'])) {
    $note = $_POST['note'];
    $patient_id = $_POST['patient_id'];
    $follow_up = $_POST['follow_up'];

    $stmt = $conn->prepare("INSERT INTO doctor_notes (doctor_id, patient_id, note, follow_up_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $doctor_id, $patient_id, $note, $follow_up);
    $stmt->execute();
}

// Fetch patients
$patients = [];
$res = $conn->prepare("SELECT * FROM patients WHERE doctor_id = ? ORDER BY created_at DESC");
$res->bind_param("i", $doctor_id);
$res->execute();
$patients = $res->get_result();

// Fetch upcoming and overdue follow-ups
$today = date('Y-m-d');
$follow_ups = $conn->query("
    SELECT doctor_notes.*, patients.name 
    FROM doctor_notes 
    JOIN patients ON doctor_notes.patient_id = patients.id 
    WHERE doctor_notes.doctor_id = $doctor_id 
    ORDER BY follow_up_date ASC
");

$upcoming = [];
$overdue = [];

while ($row = $follow_ups->fetch_assoc()) {
    $fdate = $row['follow_up_date'];
    if ($fdate < $today) {
        $overdue[] = $row;
    } else {
        $upcoming[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Advanced Tools - Smart Care Assistant</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .tabs button { padding: 10px 20px; margin-right: 10px; cursor: pointer; background: #e0e0e0; border: none; border-radius: 4px; }
        .tab-content { display: none; padding: 15px; border: 1px solid #ccc; margin-top: 10px; border-radius: 5px; }
        .tab-content.active { display: block; }
        textarea { width: 100%; height: 60px; }
        table, th, td { border: 1px solid #ccc; border-collapse: collapse; padding: 8px; }
        th { background: #f0f0f0; }
        canvas { max-width: 100%; margin-top: 20px; }
    </style>
    <script>
        function openTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</head>
<body>
    <h2>🛠️ Advanced Tools - Dr. <?= htmlspecialchars($doctor_name) ?></h2>

    <div class="tabs">
        <button onclick="openTab('adrTab')">⚕️ ADR Detection</button>
        <button onclick="openTab('notesTab')">📝 Doctor Notes & Follow-Up</button>
        <button onclick="openTab('trendsTab')">📊 Patient Trends</button>
        <button onclick="openTab('reportTab')">📄 Patient Report</button>
    </div>

    <!-- ADR Detection -->
    <div id="adrTab" class="tab-content active">
        <h3>⚠️ ADR Detection Module</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Symptoms</th>
                <th>Vitals</th>
                <th>ADR Risk</th>
            </tr>
            <?php foreach ($patients as $row): ?>
                <?php
                    $symptoms = strtolower($row['symptoms']);
                    $vitals = strtolower($row['vitals']);
                    $risks = [];

                    if (strpos($symptoms, 'rash') !== false || strpos($symptoms, 'dizziness') !== false || strpos($symptoms, 'nausea') !== false) {
                        $risks[] = "🧪 Possible ADR Symptoms";
                    }
                    if (preg_match('/bp[^\d]*(\d{2,3})\/(\d{2,3})/', $vitals, $bp)) {
                        if ((int)$bp[1] < 90 || (int)$bp[2] < 60) $risks[] = "⚠️ Low BP";
                    }
                    $risk_status = $risks ? implode("<br>", $risks) : "✅ No ADR signs";
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['symptoms']) ?></td>
                    <td><?= htmlspecialchars($row['vitals']) ?></td>
                    <td><?= $risk_status ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Doctor Notes -->
    <div id="notesTab" class="tab-content">
        <h3>📝 Add Notes & Schedule Follow-up</h3>

        <h4>🔔 Follow-Up Alerts</h4>

        <?php if (count($overdue) > 0): ?>
            <div style="background:#ffdddd; padding:10px; border-left:4px solid red; margin-bottom: 10px;">
                <strong>⚠️ Overdue Follow-ups:</strong>
                <ul style="margin: 0;">
                    <?php foreach ($overdue as $row): ?>
                        <li><?= htmlspecialchars($row['name']) ?> - Due on <?= $row['follow_up_date'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (count($upcoming) > 0): ?>
            <div style="background:#e0ffe0; padding:10px; border-left:4px solid green; margin-bottom: 20px;">
                <strong>📅 Upcoming Follow-ups:</strong>
                <ul style="margin: 0;">
                    <?php foreach ($upcoming as $row): ?>
                        <li><?= htmlspecialchars($row['name']) ?> - <?= $row['follow_up_date'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Select Patient:</label><br>
            <select name="patient_id" required>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (<?= $p['id'] ?>)</option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Doctor's Note:</label><br>
            <textarea name="note" required></textarea><br><br>

            <label>Follow-up Date:</label><br>
            <input type="date" name="follow_up" required><br><br>

            <button type="submit">➕ Save Note</button>
        </form>

        <h4>📋 Previous Notes</h4>
        <table>
            <tr>
                <th>Patient</th>
                <th>Note</th>
                <th>Follow-up Date</th>
                <th>Added On</th>
            </tr>
            <?php
                $notes = $conn->query("SELECT doctor_notes.*, patients.name FROM doctor_notes JOIN patients ON doctor_notes.patient_id = patients.id WHERE doctor_notes.doctor_id = $doctor_id ORDER BY created_at DESC");
                while ($n = $notes->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($n['name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($n['note'])) ?></td>
                    <td><?= $n['follow_up_date'] ?></td>
                    <td><?= $n['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Patient Trends -->
    <div id="trendsTab" class="tab-content">
        <h3>📊 Patient Trends & Analytics</h3>

        <canvas id="symptomChart" height="120"></canvas>
        <canvas id="genderChart" height="120"></canvas>
        <canvas id="dailyVisitsChart" height="120"></canvas>

        <script>
            const symptomsData = {
                labels: ['Fever', 'Cough', 'Headache', 'Nausea', 'Dizziness'],
                datasets: [{
                    label: 'Top Symptoms',
                    data: [15, 10, 8, 6, 4],
                    backgroundColor: ['#f44336', '#2196f3', '#ff9800', '#4caf50', '#9c27b0']
                }]
            };

            const genderData = {
                labels: ['Male', 'Female'],
                datasets: [{
                    label: 'Gender Distribution',
                    data: [60, 40],
                    backgroundColor: ['#42a5f5', '#ef5350']
                }]
            };

            const dailyVisitsData = {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Daily Patient Visits',
                    data: [12, 9, 14, 11, 6, 4],
                    backgroundColor: '#03a9f4'
                }]
            };

            new Chart(document.getElementById('symptomChart'), {
                type: 'bar',
                data: symptomsData,
                options: { responsive: true }
            });

            new Chart(document.getElementById('genderChart'), {
                type: 'pie',
                data: genderData,
                options: { responsive: true }
            });

            new Chart(document.getElementById('dailyVisitsChart'), {
                type: 'bar',
                data: dailyVisitsData,
                options: { responsive: true }
            });
        </script>
    </div>

    <!-- Patient Report Generator -->
    <div id="reportTab" class="tab-content">
        <h3>📄 Patient Report Generator</h3>
        <form method="GET" action="generate_report.php" target="_blank">
            <label>Select Patient:</label>
            <select name="patient_id" required>
                <?php
                    $res->execute();
                    $patients2 = $res->get_result();
                    foreach ($patients2 as $p):
                ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['id'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <button type="submit">🧾 Generate Report</button>
        </form>
        <p style="color: gray; margin-top: 10px;">* Opens a new tab to view and download a printable patient summary PDF.</p>
    </div>

    <br>
    <a href="history_timeline.php">📅 Patient Medical History</a> |
    <a href="doctor_tasks_prescription.php">📋 Manage Tasks & Prescriptions</a>
    <a href="doctor_voice_entry.php">🎤 Voice-to-Text Entry</a>
    <li><a href="doctor_templates.php">📝 Template-Based Entry</a></li>


    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
