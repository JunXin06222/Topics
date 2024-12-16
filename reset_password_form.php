<?php
session_start();
include('db.php');

$token = $_GET['token'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

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
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重設密碼 - 大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <!-- Header content remains the same -->
    <div class="container">
        <div class="logo">
            <h1>大肌肌<span>健身平台</span></h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">首頁</a></li>
                <li><a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>">關於我們</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <li><a href="qa.php" class="<?= basename($_SERVER['PHP_SELF']) == 'qa.php' ? 'active' : '' ?>">AI菜單</a></li>
                    <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">個人資料</a></li>
                    <li><a href="progress.php" class="<?= basename($_SERVER['PHP_SELF']) == 'progress.php' ? 'active' : '' ?>">進度追蹤</a></li>
                    <li><a href="analysis.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analysis.php' ? 'active' : '' ?>">資料計算</a></li>
                    <li><a href="view_responses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'view_responses.php' ? 'active' : '' ?>">健身菜單紀錄</a></li>
                    <li><a href="start_workout.php" class="<?= basename($_SERVER['PHP_SELF']) == 'start_workout.php' ? 'active' : '' ?>">運動計時器</a></li>
                    <li><a href="logout.php">登出</a></li>
                    <li>歡迎, <?= htmlspecialchars($_SESSION['username']) ?></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>">登入</a></li>
                    <li><a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>">註冊</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<section class="auth-section">
    <div class="container">
        <div class="auth-form">
            <h2>重設密碼</h2>
            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form action="update_password.php" method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label for="new_password">新密碼:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">確認新密碼:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">更新密碼</button>
            </form>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2023 大肌肌健身平台. All rights reserved.</p>
</footer>
</body>
</html>