<?php
// delete_user.php
session_start();

include '../User/config.php';
// kiểm tra đã đăng nhập và role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=' . urlencode('Bạn không có quyền'));
    exit;
}

// lấy ID từ query string
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "ID không hợp lệ.";
    header('Location: dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "Xóa người dùng thành công.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Lỗi xóa người dùng: " . $e->getMessage();
}

header('Location: dashboard.php');
exit;
?>
