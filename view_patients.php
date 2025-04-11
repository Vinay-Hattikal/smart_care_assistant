<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$gender = $_GET['gender'] ?? "";
$age_group = $_GET['age_group'] ?? "";

// SQL base
$sql = "SELECT * FROM patients WHERE doctor_id = ?";
$params = [$doctor_id];
$types = "i";

// Filters
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR symptoms LIKE ? OR vitals LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if ($gender) {
    $sql .= " AND gender = ?";
    $params[] = $gender;
    $types .= "s";
}

if ($age_group === "child") {
    $sql .= " AND age < 18";
} elseif ($age_group === "adult") {
    $sql .= " AND age >= 18 AND age < 60";
} elseif ($age_group === "senior") {
    $sql .= " AND age >= 60";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Patients - Smart Care Assistant</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
        th { background-color: #f2f2f2; }
        .actions a { margin: 0 5px; text-decoration: none; }
    </style>
</head>
<body>
    <h2>👨‍⚕️ Dr. <?= htmlspecialchars($doctor_name) ?>'s Patients</h2>

    <form method="GET">
        🔍 <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        🧬 Gender:
        <select name="gender">
            <option value="">All</option>
            <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
        </select>
        🎂 Age Group:
        <select name="age_group">
            <option value="">All</option>
            <option value="child" <?= $age_group === 'child' ? 'selected' : '' ?>>Child (&lt;18)</option>
            <option value="adult" <?= $age_group === 'adult' ? 'selected' : '' ?>>Adult (18-59)</option>
            <option value="senior" <?= $age_group === 'senior' ? 'selected' : '' ?>>Senior (60+)</option>
        </select>
        <button type="submit">Filter</button>
        <a href="view_patients.php">Reset</a>
        <a href="export_pdf.php?search=<?= urlencode($search) ?>&gender=<?= $gender ?>&age_group=<?= $age_group ?>">🧾 Export PDF</a>
    </form>

    <br>

    <?php
    $symptom_counts = [];
    $date_counts = [];

    if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Age</th><th>Gender</th>
                <th>Symptoms</th><th>Vitals</th><th>Warnings</th>
                <th>Added On</th><th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()):
                $symptoms = strtolower($row['symptoms']);
                $vitals = strtolower($row['vitals']);
                $warnings = [];

                $keywords = ['chest pain', 'breathless', 'confused', 'seizure'];
                foreach ($keywords as $kw) {
                    if (strpos($symptoms, $kw) !== false) {
                        $warnings[] = "🚨 " . ucfirst($kw);
                        $symptom_counts[$kw] = ($symptom_counts[$kw] ?? 0) + 1;
                    }
                }

                if (preg_match('/(fever|temp)[^\d]*([\d.]+)/', $vitals, $m)) {
                    if ((float)$m[2] > 102) $warnings[] = "🔥 High Fever ({$m[2]}°F)";
                }
                if (preg_match('/bp[^\d]*(\d{2,3})\/(\d{2,3})/', $vitals, $bp)) {
                    if ((int)$bp[1] > 140 || (int)$bp[2] > 90) $warnings[] = "⚠️ High BP ({$bp[1]}/{$bp[2]})";
                }
                if (preg_match('/(heart rate|pulse)[^\d]*(\d{2,3})/', $vitals, $hr)) {
                    if ((int)$hr[2] > 110) $warnings[] = "❤️ High HR ({$hr[2]})";
                }

                $date = date('Y-m-d', strtotime($row['created_at']));
                $date_counts[$date] = ($date_counts[$date] ?? 0) + 1;
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['age'] ?></td>
                <td><?= $row['gender'] ?></td>
                <td><?= nl2br(htmlspecialchars($row['symptoms'])) ?></td>
                <td><?= nl2br(htmlspecialchars($row['vitals'])) ?></td>
                <td><?= empty($warnings) ? '✅ Stable' : implode('<br>', $warnings) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td class="actions">
                    <a href="edit_patient.php?id=<?= $row['id'] ?>">✏️</a>
                    <a href="delete_patient.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this patient?');">🗑️</a>
                    <a href="download_patient.php?id=<?= $row['id'] ?>">📄</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <br><canvas id="symptomChart" width="400" height="200"></canvas>
        <br><canvas id="dateChart" width="400" height="200"></canvas>

        <script>
            const symptomCtx = document.getElementById('symptomChart').getContext('2d');
            const dateCtx = document.getElementById('dateChart').getContext('2d');

            new Chart(symptomCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($symptom_counts)) ?>,
                    datasets: [{
                        label: 'Symptom Count',
                        data: <?= json_encode(array_values($symptom_counts)) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    }]
                }
            });

            new Chart(dateCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_keys($date_counts)) ?>,
                    datasets: [{
                        label: 'Patients per Day',
                        data: <?= json_encode(array_values($date_counts)) ?>,
                        borderColor: 'blue',
                        tension: 0.2
                    }]
                }
            });
        </script>

    <?php else: ?>
        <p>No patients found.</p>
    <?php endif; ?>

    <br><a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
