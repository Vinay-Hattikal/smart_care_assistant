<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch patients list
$patients_result = $conn->query("SELECT id, name FROM patients WHERE doctor_id = $doctor_id");

// Handle selection
$selected_patient = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$history = [];

if ($selected_patient) {
    $sql = "SELECT 'Patient Record' as type, symptoms, vitals, created_at, NULL as note, NULL as adr
            FROM patients WHERE id = $selected_patient
            UNION
            SELECT 'Doctor Note', NULL, NULL, created_at, note, NULL FROM doctor_notes WHERE patient_id = $selected_patient
            UNION
            SELECT 'ADR Alert', NULL, NULL, created_at, NULL, adr_details FROM adr_reports WHERE patient_id = $selected_patient
            ORDER BY created_at ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) $history[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical History Timeline</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .timeline { border-left: 3px solid #555; margin-left: 20px; padding-left: 20px; }
        .entry { margin-bottom: 20px; }
        .entry h4 { margin: 0; }
        .type { font-weight: bold; }
        .timestamp { font-size: 0.9em; color: #888; }
    </style>
</head>
<body>

<h2>📅 Medical History Timeline</h2>

<form method="GET" action="">
    <label>Select Patient:</label>
    <select name="patient_id" onchange="this.form.submit()">
        <option value="">-- Choose --</option>
        <?php while ($p = $patients_result->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>" <?= $selected_patient == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($selected_patient): ?>
    <h3>History for Patient ID <?= $selected_patient ?>:</h3>
    <div class="timeline">
        <?php if (empty($history)): ?>
            <p>No history found.</p>
        <?php else: ?>
            <?php foreach ($history as $entry): ?>
                <div class="entry">
                    <span class="type"><?= $entry['type'] ?></span> <span class="timestamp">(<?= $entry['created_at'] ?>)</span><br>
                    <?php if ($entry['type'] == 'Patient Record'): ?>
                        <strong>Symptoms:</strong> <?= nl2br(htmlspecialchars($entry['symptoms'])) ?><br>
                        <strong>Vitals:</strong> <?= nl2br(htmlspecialchars($entry['vitals'])) ?>
                    <?php elseif ($entry['type'] == 'Doctor Note'): ?>
                        <strong>Note:</strong> <?= nl2br(htmlspecialchars($entry['note'])) ?>
                    <?php elseif ($entry['type'] == 'ADR Alert'): ?>
                        <strong>ADR:</strong> <?= nl2br(htmlspecialchars($entry['adr'])) ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>
