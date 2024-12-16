<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

function calculateBMI($weight, $height) {
    return $weight / (($height / 100) ** 2);
}

function calculateBodyFat($gender, $waist, $neck, $height, $hip = null) {
    if ($gender == 'male') {
        return 495 / (1.0324 - 0.19077 * log10($waist - $neck) + 0.15456 * log10($height)) - 450;
    } else {
        return 495 / (1.29579 - 0.35004 * log10($waist + $hip - $neck) + 0.22100 * log10($height)) - 450;
    }
}

function calculateBMR($weight, $height, $age, $gender) {
    if ($gender == 'male') {
        return 9.99 * $weight + 6.25 * $height - 4.92 * $age + (166 * 1 - 161);
    } else {
        return 9.99 * $weight + 6.25 * $height - 4.92 * $age + (166 * 0 - 161);
    }
}

function getBMIStatus($bmi) {
    if ($bmi < 18.5) return '過輕';
    if ($bmi < 24) return '正常';
    if ($bmi < 27) return '過重';
    if ($bmi < 30) return '輕度肥胖';
    if ($bmi < 35) return '中度肥胖';
    return '重度肥胖';
}

function getBodyFatStatus($bodyFat, $gender) {
    if ($gender == 'male') {
        if ($bodyFat < 6) return '過低';
        if ($bodyFat < 14) return '運動員';
        if ($bodyFat < 18) return '健康';
        if ($bodyFat < 25) return '可接受';
        return '過高';
    } else {
        if ($bodyFat < 14) return '過低';
        if ($bodyFat < 21) return '運動員';
        if ($bodyFat < 25) return '健康';
        if ($bodyFat < 32) return '可接受';
        return '過高';
    }
}

$bmi = $bodyFat = $bmr = null;
$comparison = null;

// 獲取用戶個人資料
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userProfile = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['clear_data'])) {
        $stmt = $conn->prepare("DELETE FROM user_analysis WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $clear_message = "所有資料分析記錄已成功清除。";
    } else {
        $weight = $_POST['weight'];
        $height = $_POST['height'];
        $age = $_POST['age'];
        $gender = $_POST['gender'];
        $waist = $_POST['waist'];
        $neck = $_POST['neck'];
        $hip = ($gender == 'female') ? $_POST['hip'] : null;

        $bmi = calculateBMI($weight, $height);
        $bodyFat = calculateBodyFat($gender, $waist, $neck, $height, $hip);
        $bmr = calculateBMR($weight, $height, $age, $gender);

        // 獲取最近的分析結果
        $stmt = $conn->prepare("SELECT * FROM user_analysis WHERE user_id = ? ORDER BY date DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $latestAnalysis = $result->fetch_assoc();

        if ($latestAnalysis) {
            $comparison = [
                'weight' => $weight - $latestAnalysis['weight'],
                'bmi' => $bmi - $latestAnalysis['bmi'],
                'body_fat' => $bodyFat - $latestAnalysis['body_fat'],
                'bmr' => $bmr - $latestAnalysis['bmr']
            ];
        }

        // 插入新的分析結果
        if ($gender == 'female') {
            $stmt = $conn->prepare("INSERT INTO user_analysis (user_id, weight, height, age, gender, waist, neck, hip, bmi, body_fat, bmr, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iddiisddddd", $user_id, $weight, $height, $age, $gender, $waist, $neck, $hip, $bmi, $bodyFat, $bmr);
        } else {
            $stmt = $conn->prepare("INSERT INTO user_analysis (user_id, weight, height, age, gender, waist, neck, bmi, body_fat, bmr, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iddiisdddd", $user_id, $weight, $height, $age, $gender, $waist, $neck, $bmi, $bodyFat, $bmr);
        }

        $stmt->execute();
    }
}

// 獲取最新的分析結果
$stmt = $conn->prepare("SELECT * FROM user_analysis WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$latestAnalysis = $result->fetch_assoc();

if (!$userProfile) {
    $userProfile = [
        'height' => '',
        'weight' => '',
        'age' => '',
        'gender' => '',
        'waist' => '',
        'neck' => '',
        'hip' => ''
    ];
}

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>資料分析 - 大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .analysis-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .analysis-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .analysis-form input,
        .analysis-form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .analysis-form label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .clear-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .clear-btn:hover {
            background-color: #c82333;
        }

        .analysis-results {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .analysis-results h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .status-indicator {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-normal { background-color: #28a745; color: white; }
        .status-warning { background-color: #ffc107; color: black; }
        .status-danger { background-color: #dc3545; color: white; }

        .comparison {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 8px;
        }
        .comparison h3 {
            margin-top: 0;
            color: #495057;
        }
        .comparison p {
            margin: 5px 0;
        }
        .positive-change { color: #28a745; }
        .negative-change { color: #dc3545; }

        /* 新增的 CSS 來對齊表單元素 */
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .form-group label {
            flex: 0 0 100px;
            margin-bottom: 0;
        }
        .form-group input,
        .form-group select {
            flex: 1;
            margin-bottom: 0;
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
        <div class="analysis-container">
            <h1>資料分析</h1>
            <?php if (isset($clear_message)): ?>
                <p style="color: green;"><?php echo $clear_message; ?></p>
            <?php endif; ?>
            
            <div class="analysis-form">
                <form method="post">
                    <div class="form-group">
                        <label for="height">身高 (cm):</label>
                        <input type="number" step="0.1" id="height" name="height" value="<?php echo $userProfile['height'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="weight">體重 (kg):</label>
                        <input type="number" step="0.1" id="weight" name="weight" value="<?php echo $userProfile['weight'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="age">年齡:</label>
                        <input type="number" id="age" name="age" value="<?php echo $userProfile['age'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="gender">性別:</label>
                        <select id="gender" name="gender" required>
                            <option value="male" <?php echo ($userProfile['gender'] == 'male') ? 'selected' : ''; ?>>男性</option>
                            <option value="female" <?php echo ($userProfile['gender'] == 'female') ? 'selected' : ''; ?>>女性</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="waist">腰圍 (cm):</label>
                        <input type="number" step="0.1" id="waist" name="waist" value="<?php echo $userProfile['waist'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="neck">頸圍 (cm):</label>
                        <input type="number" step="0.1" id="neck" name="neck" value="<?php echo $userProfile['neck'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group" id="hip_group" style="display:none;">
                        <label for="hip">臀圍 (cm):</label>
                        <input type="number" step="0.1" id="hip" name="hip" value="<?php echo $userProfile['hip'] ?? ''; ?>">
                    </div>

                    <button type="submit" class="submit-btn">計算並比較</button>
                </form>
            </div>

            <form method="post" onsubmit="return confirm('確定要清除所有資料分析記錄嗎？這個操作無法撤銷。');">
                <button type="submit" name="clear_data" class="clear-btn">清除所有資料</button>
            </form><?php if ($comparison): ?>
                <div class="comparison">
                    <h3>與上次分析結果比較</h3>
                    <p>體重變化: <span class="<?php echo $comparison['weight'] < 0 ? 'positive-change' : 'negative-change'; ?>"><?php echo number_format($comparison['weight'], 2); ?> kg</span></p>
                    <p>BMI變化: <span class="<?php echo $comparison['bmi'] < 0 ? 'positive-change' : 'negative-change'; ?>"><?php echo number_format($comparison['bmi'], 2); ?></span></p>
                    <p>體脂率變化: <span class="<?php echo $comparison['body_fat'] < 0 ? 'positive-change' : 'negative-change'; ?>"><?php echo number_format($comparison['body_fat'], 2); ?>%</span></p>
                    <p>基礎代謝率變化: <span class="<?php echo $comparison['bmr'] > 0 ? 'positive-change' : 'negative-change'; ?>"><?php echo number_format($comparison['bmr'], 2); ?> 卡路里/天</span></p>
                </div>
            <?php endif; ?>

            <?php if ($latestAnalysis): ?>
                <div class="analysis-results">
                    <h2>最新分析結果 (<?php echo $latestAnalysis['date']; ?>)</h2>
                    <?php
                        $bmiStatus = getBMIStatus($latestAnalysis['bmi']);
                        $bodyFatStatus = getBodyFatStatus($latestAnalysis['body_fat'], $latestAnalysis['gender']);
                        
                        $bmiClass = ($bmiStatus == '正常') ? 'status-normal' : (($bmiStatus == '過重' || $bmiStatus == '過輕') ? 'status-warning' : 'status-danger');
                        $bodyFatClass = ($bodyFatStatus == '健康') ? 'status-normal' : (($bodyFatStatus == '可接受' || $bodyFatStatus == '運動員') ? 'status-warning' : 'status-danger');
                    ?>
                    <p>BMI: <?php echo number_format($latestAnalysis['bmi'], 2); ?> 
                       <span class="status-indicator <?php echo $bmiClass; ?>"><?php echo $bmiStatus; ?></span>
                    </p>
                    <p>體脂率: <?php echo number_format($latestAnalysis['body_fat'], 2); ?>% 
                       <span class="status-indicator <?php echo $bodyFatClass; ?>"><?php echo $bodyFatStatus; ?></span>
                    </p>
                    <p>基礎代謝率: <?php echo number_format($latestAnalysis['bmr'], 2); ?> 卡路里/天</p>
                    <p>體重: <?php echo $latestAnalysis['weight']; ?> kg</p>
                    <p>身高: <?php echo $latestAnalysis['height']; ?> cm</p>
                    <p>腰圍: <?php echo $latestAnalysis['waist']; ?> cm</p>
                    <p>頸圍: <?php echo $latestAnalysis['neck']; ?> cm</p>
                    <?php if ($latestAnalysis['gender'] == 'female'): ?>
                        <p>臀圍: <?php echo $latestAnalysis['hip']; ?> cm</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    document.getElementById('gender').addEventListener('change', function() {
        var hipGroup = document.getElementById('hip_group');
        var hipField = document.getElementById('hip');
        if (this.value === 'female') {
            hipGroup.style.display = 'flex';
            hipField.required = true;
        } else {
            hipGroup.style.display = 'none';
            hipField.required = false;
        }
    });

    // 頁面加載時檢查性別
    window.onload = function() {
        var gender = document.getElementById('gender').value;
        var hipGroup = document.getElementById('hip_group');
        var hipField = document.getElementById('hip');
        if (gender === 'female') {
            hipGroup.style.display = 'flex';
            hipField.required = true;
        } else {
            hipGroup.style.display = 'none';
            hipField.required = false;
        }
    };
    </script>
</body>
</html>