<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id = $_GET['id'] ?? 0;

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['doctor_id']);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) die("Patient not found or not allowed.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $symptoms = $_POST['symptoms'];
    $vitals = $_POST['vitals'];

    $stmt = $conn->prepare("UPDATE patients SET name=?, age=?, gender=?, symptoms=?, vitals=? WHERE id=? AND doctor_id=?");
    $stmt->bind_param("sisssii", $name, $age, $gender, $symptoms, $vitals, $id, $_SESSION['doctor_id']);
    if ($stmt->execute()) {
        header("Location: view_patients.php");
        exit();
    } else {
        echo "Update failed.";
    }
}
?>

<h2>Edit Patient</h2>
<form method="POST">
    Name: <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required><br><br>
    Age: <input type="number" name="age" value="<?= $patient['age'] ?>" required><br><br>
    Gender:
    <select name="gender" required>
        <option value="Male" <?= $patient['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $patient['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
    </select><br><br>
    Symptoms:<br>
    <textarea name="symptoms" required><?= htmlspecialchars($patient['symptoms']) ?></textarea><br><br>
    Vitals:<br>
    <textarea name="vitals" required><?= htmlspecialchars($patient['vitals']) ?></textarea><br><br>
    <button type="submit">Update</button>
</form>
<br>
<a href="view_patients.php">← Back</a>
