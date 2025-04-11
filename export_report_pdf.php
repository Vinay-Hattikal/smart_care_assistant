<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

$conn = new mysqli("localhost", "root", "", "smart_care_assistant");

$patient_id = $_POST['patient_id'];
$chart = $_POST['chart_data'] ?? '';

$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();

$dompdf = new Dompdf();
$html = "
    <h2>🧾 Patient Report: {$patient['name']}</h2>
    <p><strong>Age:</strong> {$patient['age']} | <strong>Gender:</strong> {$patient['gender']}</p>
    <p><strong>Symptoms:</strong> {$patient['symptoms']}</p>
    <p><strong>Vitals:</strong> {$patient['vitals']}</p>
    <h3>📊 Symptoms Chart</h3>
    <img src='$chart' style='width:500px; height:auto;'>
";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("patient_report_{$patient['id']}.pdf", ["Attachment" => 1]);
?>
