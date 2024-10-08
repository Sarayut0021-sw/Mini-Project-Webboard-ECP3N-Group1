<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Board - Home</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- เรียกใช้ CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> <!-- ใช้ Bootstrap -->
</head>
<body style="background-color: #FFF5EE">
    <header class="header">
        <h1 style="text-align:center;">Welcome to Web Board</h1>
        <nav class="navbar">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <div class="container mt-5">
        <h2>Recent Posts</h2>
        <div class="post-list">
            <?php
            // ฟังก์ชันเชื่อมต่อฐานข้อมูล
            function connectDB() {
                $conn = new mysqli('localhost', 'root', '', 'user_db');
                if ($conn->connect_error) {
                    die('Connection failed: ' . $conn->connect_error);
                }
                return $conn;
            }

            $conn = connectDB();

            // ดึงกระทู้ล่าสุด
            $sql = "SELECT posts.*, users.first_name, users.last_name FROM posts JOIN users ON posts.user_id = users.id ORDER BY created_at DESC LIMIT 5";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>';
                    echo '<p class="card-text">' . nl2br(htmlspecialchars($row['content'])) . '</p>';
                    echo '<small>Posted by ' . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . ' on ' . $row['created_at'] . '</small>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No posts available.</p>';
            }

            $conn->close();
            ?>
        </div>
    </div>

    <footer class="footer text-center mt-5">
        <p>&copy; <?php echo date("Y"); ?> Your Website Name. All Rights Reserved.</p>
    </footer>
</body>
</html>