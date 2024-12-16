<?php
session_start();
include('db.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 檢查電子郵件是否存在
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 生成重設令牌
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600*3600);

        // 更新數據庫中的重設令牌
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expires, $email);
        $updateStmt->execute();

        // 發送重設密碼郵件
        $mail = new PHPMailer(true);

        try {
            // 服務器設置
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'azz150422@gmail.com'; // 替換為您的Gmail地址
            $mail->Password   = 'hsaz lkok idzx bqfz'; // 替換為您的Gmail應用密碼
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // 收件人
            $mail->setFrom('your-email@gmail.com', '大肌肌健身平台');
            $mail->addAddress($email);

            // 內容
            $mail->isHTML(true);
            $mail->Subject = '重設您的密碼';
            $mail->Body    = "請點擊以下鏈接重設您的密碼：<br><a href='https://9f86-163-17-135-93.ngrok-free.app/web0923/reset_password_form.php?token=$token'>重設密碼</a><br>此鏈接將在一小時後失效。
            如果無法點擊請直接複製網址!https://9f86-163-17-135-93.ngrok-free.app/web0923/reset_password_form.php?token=$token";

            $mail->send();
            $_SESSION['success'] = '重設密碼鏈接已發送到您的郵箱，請查收。';
        } catch (Exception $e) {
            $_SESSION['error'] = "郵件發送失敗，請稍後再試。錯誤信息: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = '找不到與此電子郵件關聯的帳戶。';
    }

    header('Location: forgot_password.php');
    exit();
}
?>