<?php
session_start(); // เริ่มการทำงาน session

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'user_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // คำสั่ง SQL เพื่อเลือกผู้ใช้ที่มีอีเมลตรงกับที่กรอกมา
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // ตรวจสอบว่าพบผู้ใช้ที่มีอีเมลนี้หรือไม่
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            // สร้าง session เพื่อเก็บข้อมูลผู้ใช้
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            // ตรวจสอบว่าเป็น admin หรือไม่
            if ($user['is_admin'] == 1) {
                $_SESSION['is_admin'] = true; // เก็บสถานะ admin
            } else {
                $_SESSION['is_admin'] = false;
            }

            // เปลี่ยนเส้นทางไปยังหน้า home.php หลังจากล็อกอินสำเร็จ
            header("Location: home.php");
            exit();
        } else {
            echo "Incorrect password!";
        }
    } else {
        echo "No user found with this email!";
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
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- เชื่อมต่อไฟล์ CSS -->
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
        
                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Sign up</a></p>

            <!-- ปุ่มย้อนกลับไปหน้า index.php -->
            <br>
            <a href="index.php" style="text-decoration: none;">
                <button type="button">Back yo Home page</button>
            </a>
        </div>
    </div>
</body>
</html>