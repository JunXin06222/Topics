<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $waist = $_POST['waist'];
    $neck = $_POST['neck'];
    $hip = ($gender == 'female') ? $_POST['hip'] : null;
    $dietary_preferences = $_POST['dietary_preferences'];
    $dislikes = $_POST['dislikes'];
    $goal = $_POST['goal'];

    $stmt = $conn->prepare("INSERT INTO user_profiles 
        (user_id, height, weight, age, gender, waist, neck, hip, dietary_preferences, dislikes, goal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        height = VALUES(height), 
        weight = VALUES(weight),
        age = VALUES(age),
        gender = VALUES(gender),
        waist = VALUES(waist),
        neck = VALUES(neck),
        hip = VALUES(hip),
        dietary_preferences = VALUES(dietary_preferences),
        dislikes = VALUES(dislikes),
        goal = VALUES(goal)");
    $stmt->bind_param("iiiisddssss", $user_id, $height, $weight, $age, $gender, $waist, $neck, $hip, $dietary_preferences, $dislikes, $goal);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();


if ($profile === null) {
    $profile = array(); // 創建一個空數組，避免後續代碼出錯
}

if (!isset($profile['goal'])) {
    $profile['goal'] = '';
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資料 - 大肌肌健身平台</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f4f7fc;
        color: #333;
        line-height: 1.6;
    }
    .profile-container {
        max-width: 800px;
        margin: 120px auto 40px;
        padding: 40px;
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    h1 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 40px;
        font-size: 32px;
        font-weight: 700;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #34495e;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #dde1e7;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        outline: none;
    }
    .form-group textarea {
        height: 120px;
        resize: vertical;
    }
    .full-width {
        grid-column: 1 / -1;
    }
    .submit-btn {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 14px 24px;
        font-size: 18px;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        width: 100%;
        margin-top: 30px;
    }
    .submit-btn:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    @media (max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .profile-container {
            padding: 30px;
            margin-top: 100px;
        }
    }
</style>
</head>
<body>
<header>
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
                    <li><a href="change_password.php" >修改密碼</a></li>
                    <li class="welcome">歡迎, <?= htmlspecialchars($_SESSION['username']) ?></li>
                    <li><a href="logout.php">登出</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="<?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>">登入</a></li>
                    <li><a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>">註冊</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

    <main>
    <div class="profile-container">
    <h1>個人資料</h1>
    <form method="post">
        <div class="form-grid">
            <div class="form-group">
                <label for="height">身高 (cm)</label>
                <input type="number" id="height" name="height" value="<?php echo htmlspecialchars($profile['height'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="weight">體重 (kg)</label>
                <input type="number" id="weight" name="weight" value="<?php echo htmlspecialchars($profile['weight'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="age">年齡</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($profile['age'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">性別</label>
                <select id="gender" name="gender" required>
                    <option value="male" <?php echo (($profile['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>男性</option>
                    <option value="female" <?php echo (($profile['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>女性</option>
                </select>
            </div>
            <div class="form-group">
                <label for="waist">腰圍 (cm)</label>
                <input type="number" step="0.1" id="waist" name="waist" value="<?php echo htmlspecialchars($profile['waist'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="neck">脖圍 (cm)</label>
                <input type="number" step="0.1" id="neck" name="neck" value="<?php echo htmlspecialchars($profile['neck'] ?? ''); ?>" required>
            </div>
            <div class="form-group" id="hip_group" style="display:none;">
                <label for="hip">臀圍 (cm)</label>
                <input type="number" step="0.1" id="hip" name="hip" value="<?php echo htmlspecialchars($profile['hip'] ?? ''); ?>">
            </div>
            <div class="form-group full-width">
                <label for="dietary_preferences">飲食偏好</label>
                <textarea id="dietary_preferences" name="dietary_preferences"><?php echo htmlspecialchars($profile['dietary_preferences'] ?? ''); ?></textarea>
            </div>
            <div class="form-group full-width">
                <label for="dislikes">不喜歡的食物</label>
                <input type="text" id="dislikes" name="dislikes" value="<?php echo htmlspecialchars($profile['dislikes'] ?? ''); ?>">
            </div>
            <div class="form-group full-width">
                <label for="goal">目標</label>
                <select id="goal" name="goal">
                    <option value="減重" <?php echo (isset($profile['goal']) && $profile['goal'] == '減重') ? 'selected' : ''; ?>>減重</option>
                    <option value="增肌" <?php echo (isset($profile['goal']) && $profile['goal'] == '增肌') ? 'selected' : ''; ?>>增肌</option>
                </select>
</div>
        </div>
        <button type="submit" class="submit-btn">保存資料</button>
    </form>
</div>
    </main>
    <script>
    document.getElementById('gender').addEventListener('change', function() {
        var hipGroup = document.getElementById('hip_group');
        if (this.value === 'female') {
            hipGroup.style.display = 'block';
        } else {
            hipGroup.style.display = 'none';
        }
    });

    // 頁面加載時檢查性別
    window.onload = function() {
        var gender = document.getElementById('gender').value;
        var hipGroup = document.getElementById('hip_group');
        if (gender === 'female') {
            hipGroup.style.display = 'block';
        } else {
            hipGroup.style.display = 'none';
        }
    };

    document.querySelector('form').addEventListener('submit', function(e) {
        var height = document.getElementById('height').value;
        var weight = document.getElementById('weight').value;
        
        if (height <= 0 || weight <= 0) {
            e.preventDefault();
            alert('身高和體重必須大於0');
        }
    });
    </script>
</body>
</html>
