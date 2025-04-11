<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

require 'vendor/autoload.php';
use Dompdf\Dompdf;

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];

// DB connection
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$sql = "SELECT * FROM patients WHERE doctor_id = ?";
if (!empty($search)) {
    $searchParam = "%" . $search . "%";
    $sql .= " AND (name LIKE ? OR symptoms LIKE ? OR vitals LIKE ?)";
    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $doctor_id, $searchParam, $searchParam, $searchParam);
} else {
    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
}

$html = "<h2>Patient Summary Report - Dr. " . htmlspecialchars($doctor_name) . "</h2>";
$html .= "<table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Symptoms</th>
                <th>Vitals</th>
                <th>Warnings</th>
                <th>Added On</th>
            </tr>";

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $symptoms = strtolower($row['symptoms']);
        $vitals = strtolower($row['vitals']);
        $warnings = [];

        // Warnings logic
        $symptom_keywords = ['chest pain', 'breathless', 'confused', 'seizure'];
        foreach ($symptom_keywords as $keyword) {
            if (strpos($symptoms, $keyword) !== false) {
                $warnings[] = ucfirst($keyword);
            }
        }

        if (preg_match('/(fever|temp)[^\d]*([\d.]+)/', $vitals, $matches)) {
            if ((float)$matches[2] > 102) $warnings[] = "High Fever ({$matches[2]}°F)";
        }

        if (preg_match('/bp[^\d]*(\d{2,3})\/(\d{2,3})/', $vitals, $bp)) {
            if ((int)$bp[1] > 140 || (int)$bp[2] > 90) $warnings[] = "High BP ({$bp[1]}/{$bp[2]})";
        }

        if (preg_match('/(heart rate|pulse)[^\d]*(\d{2,3})/', $vitals, $hr)) {
            if ((int)$hr[2] > 110) $warnings[] = "High HR ({$hr[2]})";
        }

        $warning_text = empty($warnings) ? "Stable" : implode(", ", $warnings);

        $html .= "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td>{$row['age']}</td>
                    <td>{$row['gender']}</td>
                    <td>" . nl2br(htmlspecialchars($row['symptoms'])) . "</td>
                    <td>" . nl2br(htmlspecialchars($row['vitals'])) . "</td>
                    <td>{$warning_text}</td>
                    <td>{$row['created_at']}</td>
                </tr>";
    }

    $html .= "</table>";
} else {
    $html .= "<p>Error fetching data.</p>";
}

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("patient_summary_" . date("Ymd_His") . ".pdf", ["Attachment" => true]);
exit;
?>
