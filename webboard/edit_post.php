<?php
session_start();

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ฟังก์ชันเชื่อมต่อฐานข้อมูล
function connectDB() {
    $conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    return $conn;
}

// เชื่อมต่อกับฐานข้อมูล
$conn = connectDB();

// ดึงข้อมูลกระทู้ที่ต้องการแก้ไข
if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    $sql = "SELECT * FROM posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    // ตรวจสอบว่าผู้ใช้เป็นเจ้าของกระทู้หรือเป็น admin หรือไม่
    if ($post['user_id'] != $_SESSION['user_id'] && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1)) {
        echo "You are not authorized to edit this post.";
        exit();
    }
} else {
    echo "No post found.";
    exit();
}

// อัปเดตกระทู้เมื่อมีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $sql = "UPDATE posts SET title = ?, content = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $title, $content, $post_id);

    if ($stmt->execute()) {
        header("Location: home.php");
        exit();
    } else {
        die("Error updating post: " . $stmt->error);
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- ลิงก์ไปยังไฟล์ CSS ของคุณ -->
</head>
<body>
    <div class="container">
        <div class="register-box">

            <h1>Edit Post</h1>

            <!-- ฟอร์มสำหรับแก้ไขกระทู้ -->
            <form action="edit_post.php?post_id=<?php echo $post_id; ?>" method="POST">
                <input type="hidden" name="update_post" value="1">
                <div class="input-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                <div class="form-footer">
                    <a href="home.php" class="back-button">Back</a>
                    <button type="submit">Update Post</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>