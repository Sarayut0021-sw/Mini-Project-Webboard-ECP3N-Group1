<?php
// เชื่อมต่อกับฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'user_db');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ตรวจสอบว่ามี email นี้ในระบบหรือยัง
    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "This email is already registered!";
    } else {
        // บันทึกข้อมูลลงในฐานข้อมูล
        $sql = "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $first_name, $last_name, $email, $password);

        if ($stmt->execute()) {
            // เมื่อสมัครสมาชิกสำเร็จ ให้เปลี่ยนเส้นทางไปยังหน้า login.php
            header("Location: login.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- เชื่อมต่อไฟล์ CSS -->
</head>
<body>
<div class="register-box">
    <h2>Register</h2>
    <form action="register.php" method="POST">
    <div class="input-group">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required><br>
    </div>
    <div class="input-group">
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required><br>
    </div>
    <div class="input-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
    </div>
    <div class="input-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
    </div>
        <button type="submit">Register</button>
    </form>
    <p>You already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>