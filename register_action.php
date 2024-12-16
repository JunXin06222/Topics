<?php
session_start();
include('db.php');

$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// 檢查密碼是否相符
if ($password !== $confirm_password) {
    $_SESSION['error'] = '兩次輸入的密碼不相符';
    header('Location: register.php');
    exit();
}

// 密碼加密
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 檢查使用者名稱是否已存在
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = '使用者名稱或電子郵件已存在';
    header('Location: register.php');
} else {
    // 插入新用戶
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = '註冊成功，請登入';
        header('Location: login.php');
    } else {
        $_SESSION['error'] = '註冊失敗，請再試一次';
        header('Location: register.php');
    }
}

$stmt->close();
$conn->close();
?>