<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

// Direct DB connection
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $symptoms = $_POST['symptoms'];
    $vitals = $_POST['vitals'];
    $doctor_id = $_SESSION['doctor_id'];

    $sql = "INSERT INTO patients (doctor_id, name, age, gender, symptoms, vitals)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisss", $doctor_id, $name, $age, $gender, $symptoms, $vitals);

    if ($stmt->execute()) {
        $msg = "Patient record added successfully!";
    } else {
        $msg = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Patient - Smart Care Assistant</title>
</head>
<body>
    <h2>Add Patient & Log Vitals</h2>
    <?php if ($msg) echo "<p style='color:green;'>$msg</p>"; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Patient Name" required><br><br>
        <input type="number" name="age" placeholder="Age" required><br><br>
        <select name="gender" required>
            <option value="">--Gender--</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
        </select><br><br>
        <textarea name="symptoms" placeholder="Symptoms/Complaints" rows="4" required></textarea><br><br>
        <textarea name="vitals" placeholder="Vitals (BP, HR, Temp, etc)" rows="4" required></textarea><br><br>
        <button type="submit">Save Patient</button>
    </form>

    <br><br>
    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
