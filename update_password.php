<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 檢查密碼是否匹配
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = '密碼不匹配。';
        header("Location: reset_password_form.php?token=$token");
        exit();
    }

    // 檢查令牌是否有效
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['error'] = '無效或過期的重設鏈接。';
        header('Location: forgot_password.php');
        exit();
    }

    $user = $result->fetch_assoc();

    // 更新密碼
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $updateStmt->bind_param("si", $hashed_password, $user['id']);

    if ($updateStmt->execute()) {
        $_SESSION['success'] = '密碼已成功更新，請使用新密碼登入。';
        header('Location: login.php');
    } else {
        $_SESSION['error'] = '更新密碼時出現錯誤，請稍後再試。';
        header("Location: reset_password_form.php?token=$token");
    }

    $updateStmt->close();
} else {
    header('Location: login.php');
}

$conn->close();
?>