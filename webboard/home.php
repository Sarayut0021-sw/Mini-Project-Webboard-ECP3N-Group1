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

// ตรวจสอบว่ามีการโพสต์กระทู้ใหม่หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $title, $content);

    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }

    $stmt->close();
}

// ตรวจสอบว่ามีคำขอลบกระทู้หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // ตรวจสอบว่าผู้ใช้เป็นเจ้าของกระทู้หรือเป็น admin หรือไม่
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    $sql = "SELECT * FROM posts WHERE id = ? AND (user_id = ? OR ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $post_id, $user_id, $isAdmin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ผู้ใช้เป็นเจ้าของกระทู้หรือเป็น admin ลบกระทู้
        $sql = "DELETE FROM posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $post_id);

        if (!$stmt->execute()) {
            die("Error deleting post: " . $stmt->error);
        }
    } else {
        echo "You are not authorized to delete this post.";
    }

    $stmt->close();
}

// ตรวจสอบว่ามีคำขอลบผู้ใช้หรือไม่ (เฉพาะ admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    $user_id_to_delete = $_POST['user_id'];

    // ลบผู้ใช้จากฐานข้อมูล
    $sql_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql_delete_user);
    $stmt->bind_param('i', $user_id_to_delete);

    if (!$stmt->execute()) {
        die("Error deleting user: " . $stmt->error);
    } else {
        echo "User deleted successfully!";
    }

    $stmt->close();
}

// ตรวจสอบว่ามีการโพสต์ความคิดเห็นใหม่หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_comment'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $post_id, $user_id, $comment);

    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }

    $stmt->close();
}

// ดึงกระทู้ทั้งหมดพร้อมผู้ที่โพสต์กระทู้
$sql = "SELECT posts.*, users.first_name, users.last_name FROM posts JOIN users ON posts.user_id = users.id ORDER BY created_at DESC";
$result = $conn->query($sql);

// ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// ปิดการเชื่อมต่อฐานข้อมูลหลังจากทำงานเสร็จสิ้น
// conn->close(); // ลบการปิดการเชื่อมต่อที่นี่

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="home-container">
        <h1>Welcome, <?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?>!</h1>
        <p>You are now logged in.</p>

        <!-- ฟอร์มสำหรับตั้งกระทู้ -->
        <div class="post-form">
            <h2>Create a Post</h2>
            <form action="home.php" method="POST">
                <input type="hidden" name="create_post" value="1">
                <div class="input-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="input-group">
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" rows="5" required></textarea>
                </div>
                <button type="submit">Post</button>
            </form>
        </div>

        <!-- แสดงกระทู้และความคิดเห็น -->
        <div class="post-list">
            <h2>All Posts</h2>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="post">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                    <small>Posted by <?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?> on <?php echo $row['created_at']; ?></small>
                    
                    <!-- ตรวจสอบว่าผู้ใช้เป็นเจ้าของกระทู้หรือเป็น admin -->
                    <?php if ($row['user_id'] == $_SESSION['user_id'] || $isAdmin): ?>
                        <form action="edit_post.php" method="GET" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                            <button type="submit">Edit</button>
                        </form>
                        <form action="home.php" method="POST" style="display:inline;">
                            <input type="hidden" name="delete_post" value="1">
                            <input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this post?');">Delete Post</button>
                        </form>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <!-- แสดงความคิดเห็นของกระทู้ -->
                    <?php
                    $post_id = $row['id'];
                    $sql_comments = "SELECT comments.*, users.first_name, users.last_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC";
                    $stmt = $conn->prepare($sql_comments);
                    $stmt->bind_param('i', $post_id);
                    $stmt->execute();
                    $comments_result = $stmt->get_result();
                    ?>

                    <div class="comments">
                        <h4>Comments:</h4>
                        <?php while ($comment = $comments_result->fetch_assoc()): ?>
                            <div class="comment">
                                <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                                <small>Comment by <?php echo htmlspecialchars($comment['first_name'] . " " . $comment['last_name']); ?> on <?php echo $comment['created_at']; ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- ฟอร์มสำหรับแสดงความคิดเห็นใต้กระทู้ -->
                    <div class="comment-form">
                        <form action="home.php" method="POST">
                            <input type="hidden" name="create_comment" value="1">
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            <div class="input-group">
                                <label for="comment">Write a comment:</label>
                                <textarea id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit">Comment</button>
                        </form>
                    </div>
                </div>
                <hr>
            <?php endwhile; ?>
        </div>

        <!-- ฟอร์มสำหรับ admin ในการลบผู้ใช้ -->
        <?php if ($isAdmin): ?>
            <h2>Manage Users</h2>
            <?php
            $users_sql = "SELECT id, first_name, last_name FROM users WHERE id != ?";
            $stmt = $conn->prepare($users_sql);
            $stmt->bind_param('i', $_SESSION['user_id']); // แสดงผู้ใช้นอกเหนือจากตัวเอง
            $stmt->execute();
            $users_result = $stmt->get_result();
            ?>
            <div class="user-list">
                <?php while($user = $users_result->fetch_assoc()): ?>
                    <div class="user">
                        <p><?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></p>
                        <form action="home.php" method="POST" style="display:inline;">
                            <input type="hidden" name="delete_user" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <nav class="navbar">
            <a href="logout.php">Logout</a><br>
            <a href="index.php">Home page</a>
        </nav>
    </div>
</body>
</html>