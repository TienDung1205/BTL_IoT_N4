<?php
// authenticate.php
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.php?error=' . urlencode('Vui lòng nhập đủ thông tin.'));
    exit;
}


$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // tránh tiết lộ user tồn tại hay không — trả lỗi chung
    header('Location: login.php?error=' . urlencode('Tên đăng nhập hoặc mật khẩu không đúng.'));
    exit;
}

// kiểm tra mật khẩu
if (!password_verify($password, $user['password'])) {
    header('Location: login.php?error=' . urlencode('Tên đăng nhập hoặc mật khẩu không đúng.'));
    exit;
}

// kiểm tra role phải là admin
if ($user['role'] !== 'admin') {
    header('Location: login.php?error=' . urlencode('Bạn không có quyền truy cập trang admin.'));
    exit;
}

// thành công: thiết lập session an toàn
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
// có thể thêm timestamp đăng nhập:
$_SESSION['login_time'] = time();

header('Location: index.php');
exit;

?>