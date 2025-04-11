<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Template-Based Entry - Smart Care Assistant</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; }
        textarea, select, button { width: 100%; padding: 10px; font-size: 16px; margin-top: 10px; }
        button { background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        textarea { resize: vertical; min-height: 150px; }
    </style>
</head>
<body>
    <h2>📋 Template-Based Entry</h2>

    <label for="templateSelect">Select a Template:</label>
    <select id="templateSelect" onchange="loadTemplate()">
        <option value="">-- Choose a Template --</option>
        <option value="template1">Standard Fever Case</option>
        <option value="template2">Hypertension Checkup</option>
        <option value="template3">Post-Surgery Follow-Up</option>
        <option value="template4">Chest Pain (Possible Angina)</option>
        <option value="template5">Diabetic Review</option>
        <option value="template6">Pediatric Cold & Cough</option>
        <option value="template7">Urinary Tract Infection (UTI)</option>
    </select>

    <label for="templateOutput">📝 Notes:</label>
    <textarea id="templateOutput" placeholder="Template content will appear here..."></textarea>

    <button onclick="clearTemplate()">🧹 Clear</button>
    <button onclick="saveTemplate()">💾 Save Entry</button>

    <script>
        function loadTemplate() {
            const output = document.getElementById("templateOutput");
            const selected = document.getElementById("templateSelect").value;

            const templates = {
                template1: `Chief Complaint: Fever, body ache\nVitals: Temp 101.2°F, BP 120/80\nDiagnosis: Viral fever\nPrescription: Paracetamol 500mg TID for 3 days\nAdvice: Rest, fluids, follow-up in 3 days`,
                template2: `Chief Complaint: Routine BP Check\nVitals: BP 150/95, HR 80\nDiagnosis: Stage 1 Hypertension\nPrescription: Amlodipine 5mg OD\nAdvice: Diet change, exercise, monitor BP weekly`,
                template3: `Post-Surgery Review\nVitals: Normal\nWound Status: Healing, no infection\nPrescription: Antibiotics continued for 5 days\nAdvice: No strenuous activity, next dressing in 2 days`,
                template4: `Chief Complaint: Chest Pain\nVitals: BP 135/85, HR 95, O2 Sat 97%\nDiagnosis: Suspected Angina\nPrescription: Nitroglycerin PRN, Aspirin 75mg OD\nAdvice: ECG, Cardiology referral`,
                template5: `Chief Complaint: Diabetic Review\nVitals: BP 130/85, Blood Sugar: FBS 140, PPBS 190\nDiagnosis: Type 2 Diabetes Mellitus\nPrescription: Metformin 500mg BD\nAdvice: Diet control, walk 30 mins daily, monthly sugar check`,
                template6: `Chief Complaint: Pediatric Cold & Cough\nVitals: Temp 100°F, HR 110\nDiagnosis: Common Cold\nPrescription: Syrup Crocin 5ml TID, Steam inhalation\nAdvice: Keep warm, monitor fever, follow-up in 2 days`,
                template7: `Chief Complaint: Urinary Tract Infection (UTI)\nVitals: Temp 99°F, BP 118/76\nDiagnosis: UTI\nPrescription: Ciprofloxacin 500mg BD for 5 days\nAdvice: Drink plenty of fluids, hygiene maintenance`
            };

            output.value = templates[selected] || "";
        }

        function clearTemplate() {
            document.getElementById("templateOutput").value = "";
            document.getElementById("templateSelect").value = "";
        }

        function saveTemplate() {
            const text = document.getElementById("templateOutput").value;
            alert("Template entry saved (simulated). You can enhance this to save to database.");
        }
    </script>

    <br><br>
    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
