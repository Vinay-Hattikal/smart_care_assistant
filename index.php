<?php
session_start();

// Database connection (direct)
$conn = new mysqli("localhost", "root", "", "smart_care_assistant");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM doctors WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $doctor = $result->fetch_assoc();
        if (password_verify($pass, $doctor['password'])) {
            $_SESSION['doctor_id'] = $doctor['id'];
            $_SESSION['doctor_name'] = $doctor['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $msg = "Invalid password.";
        }
    } else {
        $msg = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Care Assistant - Login</title>
</head>
<body>
    <h2>Doctor Login</h2>
    <?php if ($msg) echo "<p style='color:red;'>$msg</p>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
