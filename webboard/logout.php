<?php
session_start();
session_destroy(); // ลบข้อมูล session ทั้งหมด

header("Location: login.php"); // กลับไปหน้า login
exit();
?>