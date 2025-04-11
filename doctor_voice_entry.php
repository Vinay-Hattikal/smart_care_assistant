<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}
$doctor_name = $_SESSION['doctor_name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Voice Entry - Smart Care Assistant</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea, button, select { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
        button { border: none; cursor: pointer; border-radius: 5px; }
        .btn-start { background-color: #4CAF50; color: white; }
        .btn-stop { background-color: #f44336; color: white; }
        .btn-clear { background-color: #9e9e9e; color: white; }
        .btn-download { background-color: #2196F3; color: white; }
    </style>
</head>
<body>
    <h2>🎤 Voice-to-Text Entry (Multi-language to English)</h2>

    <label>Select Input Language:</label>
    <select id="languageSelect">
        <option value="en-IN">English (India)</option>
        <option value="hi-IN">Hindi (India)</option>
        <option value="kn-IN">Kannada (India)</option>
    </select>

    <button onclick="startRecognition()" class="btn-start">🎙️ Start Listening</button>
    <button onclick="stopRecognition()" class="btn-stop">🛑 Stop</button>
    <button onclick="clearText()" class="btn-clear">🧹 Clear</button>
    <button onclick="downloadText()" class="btn-download">⬇️ Download Text</button>

    <textarea id="voiceOutput" rows="12" placeholder="Your spoken words will appear here..."></textarea>

    <script>
        let recognition;
        let isRecording = false;

        function startRecognition() {
            if (!('webkitSpeechRecognition' in window)) {
                alert("Speech recognition is not supported in this browser. Please use Google Chrome.");
                return;
            }

            const lang = document.getElementById("languageSelect").value;
            recognition = new webkitSpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = false;
            recognition.lang = lang;

            recognition.onresult = function(event) {
                let finalTranscript = '';
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript + ' ';
                    }
                }
                document.getElementById("voiceOutput").value += finalTranscript;
            };

            recognition.onerror = function(event) {
                console.error("Speech Recognition Error:", event.error);
            };

            recognition.onend = function() {
                isRecording = false;
                console.log("Speech recognition ended.");
            };

            recognition.start();
            isRecording = true;
        }

        function stopRecognition() {
            if (recognition && isRecording) {
                recognition.stop();
                isRecording = false;
            }
        }

        function clearText() {
            document.getElementById("voiceOutput").value = "";
        }

        function downloadText() {
            const text = document.getElementById("voiceOutput").value;
            const blob = new Blob([text], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'voice_notes.txt';
            link.click();
        }
    </script>

    <br><br>
    <a href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
