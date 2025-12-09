<?php
session_start();
include '../User/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        $_SESSION['error'] = "ID người dùng không hợp lệ.";
        header('Location: dashboard.php');
        exit;
    }
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $role === '') {
        $_SESSION['error'] = "Vui lòng nhập đủ thông tin.";
        header("Location: edit_user.php?id=$id");
        exit;
    }

    // Kiểm tra username/email trùng
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Username hoặc email đã tồn tại.";
        header("Location: edit_user.php?id=$id");
        exit;
    }

    // Cập nhật
    try {
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
            $stmt->execute([$username, $email, $role, $hashedPassword, $id]);
            $_SESSION['success'] = "Cập nhật người dùng thành công.";
            header('Location: dashboard.php');
            exit;
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
            $stmt->execute([$username, $email, $role, $id]);
            $_SESSION['success'] = "Cập nhật người dùng thành công.";
            header('Location: dashboard.php');
            exit;
        }


    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi cập nhật: " . $e->getMessage();
        header("Location: edit_user.php?id=$id");
        exit;
    }
}