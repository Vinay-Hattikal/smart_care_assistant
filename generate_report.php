<?php
require 'vendor/autoload.php'; // Make sure DomPDF is properly installed via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['patient_id'])) {
    die("Patient ID missing.");
}

$patient_id = intval($_GET['patient_id']);
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch patient data
$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();
if (!$patient) {
    die("Patient not found.");
}

// Fetch notes
$notes = $conn->query("SELECT * FROM doctor_notes WHERE patient_id = $patient_id ORDER BY created_at DESC");

// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #999; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h2>Patient Report - <?= htmlspecialchars($patient['name']) ?></h2>
    <p><strong>Age:</strong> <?= $patient['age'] ?> | <strong>Gender:</strong> <?= $patient['gender'] ?></p>
    <p><strong>Symptoms:</strong> <?= htmlspecialchars($patient['symptoms']) ?></p>
    <p><strong>Vitals:</strong> <?= htmlspecialchars($patient['vitals']) ?></p>
    <hr>
    <h3>Doctor Notes & Follow-Up</h3>
    <table>
        <tr>
            <th>Note</th>
            <th>Follow-up Date</th>
            <th>Added On</th>
        </tr>
        <?php while ($note = $notes->fetch_assoc()): ?>
        <tr>
            <td><?= nl2br(htmlspecialchars($note['note'])) ?></td>
            <td><?= $note['follow_up_date'] ?></td>
            <td><?= $note['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output to browser (viewable PDF in new tab)
$dompdf->stream("Patient_Report_{$patient['name']}.pdf", ["Attachment" => false]);
exit;
?>
