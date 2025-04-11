<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id = $_GET['id'] ?? 0;

// Delete only if the patient belongs to the logged-in doctor
$stmt = $conn->prepare("DELETE FROM patients WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['doctor_id']);
$stmt->execute();

header("Location: view_patients.php");
exit();
