<?php
session_start();

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
         . $_SESSION['success'] .
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>';
    unset($_SESSION['success']); // xóa sau khi hiển thị
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
         . $_SESSION['error'] .
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>';
    unset($_SESSION['error']);
}

include '../User/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addUser.php'); // trang quản lý user
    exit;
}
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === '' || $email === '' || $role === '' || $password === '') {
    $_SESSION['error'] = "Vui lòng nhập đủ thông tin.";
    header('Location: addUser.php');
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = "Username hoặc email đã tồn tại.";
    header('Location: addUser.php');
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, role, created_at, password) VALUES (?, ?, ?, NOW(), ?)");
try {
    $stmt->execute([$username, $email, $role, $hashedPassword]);
    $_SESSION['success'] = "Thêm người dùng thành công.";
    header('Location: dashboard.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = "Lỗi thêm người dùng: " . $e->getMessage();
}
