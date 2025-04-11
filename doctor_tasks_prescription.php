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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task'], $_POST['task_date'], $_POST['patient_id'])) {
        $stmt = $conn->prepare("INSERT INTO tasks (doctor_id, patient_id, task, task_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $doctor_id, $_POST['patient_id'], $_POST['task'], $_POST['task_date']);
        $stmt->execute();
    }

    if (isset($_POST['prescription'], $_POST['prescribed_on'], $_POST['patient_id'])) {
        $stmt = $conn->prepare("INSERT INTO prescriptions (doctor_id, patient_id, prescription, prescribed_on) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $doctor_id, $_POST['patient_id'], $_POST['prescription'], $_POST['prescribed_on']);
        $stmt->execute();
    }
}

// Fetch patients
$res = $conn->prepare("SELECT id, name FROM patients WHERE doctor_id = ?");
$res->bind_param("i", $doctor_id);
$res->execute();
$patients = $res->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tasks & Prescriptions</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea, input, select { width: 100%; padding: 8px; margin: 6px 0; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>🩺 Doctor Tools: Tasks & Prescriptions</h2>

    <div class="section">
        <h3>📝 Assign Task</h3>
        <form method="POST">
            <label>Patient:</label>
            <select name="patient_id" required>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Task:</label>
            <input type="text" name="task" required>

            <label>Task Date:</label>
            <input type="date" name="task_date" required>

            <button type="submit">Add Task</button>
        </form>
    </div>

    <div class="section">
        <h3>💊 Prescribe Medication</h3>
        <form method="POST">
            <label>Patient:</label>
            <select name="patient_id" required>
                <?php
                    $res->execute();
                    $patients2 = $res->get_result();
                    foreach ($patients2 as $p):
                ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Prescription:</label>
            <textarea name="prescription" required></textarea>

            <label>Prescribed On:</label>
            <input type="date" name="prescribed_on" required>

            <button type="submit">Save Prescription</button>
        </form>
    </div>

    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
