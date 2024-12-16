<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password, login_attempts, last_attempt, is_locked FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 檢查帳戶是否被鎖定
        if ($user['is_locked']) {
            $_SESSION['error'] = '此帳戶已被鎖定,請聯繫管理員';
            header('Location: login.php');
            exit();
        }
        
        // 檢查是否需要等待
        if ($user['login_attempts'] >= 3) {
            $wait_time = 60 - (time() - strtotime($user['last_attempt']));
            if ($wait_time > 0) {
                $_SESSION['error'] = "請等待 {$wait_time} 秒後再試";
                header('Location: login.php');
                exit();
            }
        }
        
        // 驗證密碼
        if (password_verify($password, $user['password'])) {
            // 登入成功,重置嘗試次數
            $reset_sql = "UPDATE users SET login_attempts = 0, last_attempt = NULL WHERE id = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("i", $user['id']);
            $reset_stmt->execute();
            
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header('Location: index.php');
            exit();
        } else {
            // 登入失敗,增加嘗試次數
            $new_attempts = $user['login_attempts'] + 1;
            $update_sql = "UPDATE users SET login_attempts = ?, last_attempt = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_attempts, $user['id']);
            $update_stmt->execute();
            
            if ($new_attempts >= 9) {
                // 鎖定帳戶
                $lock_sql = "UPDATE users SET is_locked = TRUE WHERE id = ?";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("i", $user['id']);
                $lock_stmt->execute();
                
                $_SESSION['error'] = '登入失敗次數過多,帳戶已被鎖定';
            } elseif ($new_attempts % 3 == 0) {
                $_SESSION['error'] = '登入失敗,請等待1分鐘後再試';
            } else {
                $_SESSION['error'] = '使用者名稱或密碼錯誤';
            }
        }
    } else {
        $_SESSION['error'] = '使用者名稱或密碼錯誤';
    }
    
    $stmt->close();
    header('Location: login.php');
    exit();
}
?>