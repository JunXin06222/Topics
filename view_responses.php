<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 獲取AI回應
$sql = "SELECT *, SUBSTRING_INDEX(user_input, ' ', 3) AS input_summary FROM ai_responses WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 處理刪除請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $response_id = $_POST['response_id'];
    $stmt = $conn->prepare("DELETE FROM ai_responses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $response_id, $user_id);
    $stmt->execute();
    // 在刪除後重定向到同一頁面，這將刷新頁面
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function formatAIResponse($response) {
    $response = str_replace(['*', '#'], '', $response);
    $paragraphs = explode("\n\n", $response);
    $formattedResponse = '';
    foreach ($paragraphs as $paragraph) {
        if (strpos($paragraph, '|') !== false) {
            $formattedResponse .= formatTable($paragraph);
        } else {
            $formattedResponse .= "<p>" . trim($paragraph) . "</p>";
        }
    }
    return $formattedResponse;
}

function formatTable($tableString) {
    $rows = explode("\n", trim($tableString));
    $html = "<table border='1'><thead><tr>";
    $headers = explode('|', trim($rows[0]));
    foreach ($headers as $header) {
        $html .= "<th>" . trim($header) . "</th>";
    }
    $html .= "</tr></thead><tbody>";
    for ($i = 1; $i < count($rows); $i++) {
        $html .= "<tr>";
        $cells = explode('|', trim($rows[$i]));
        foreach ($cells as $cell) {
            $html .= "<td>" . trim($cell) . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";
    return $html;
}

function parseWorkoutPlan($response) {
    $lines = explode("\n", $response);
    $workoutPlan = [];
    $currentDay = '';
    foreach ($lines as $line) {
        if (preg_match('/^(星期[一-日]|Week\s*\d+)/', $line)) {
            $currentDay = trim($line);
            $workoutPlan[$currentDay] = [];
        } elseif ($currentDay && trim($line)) {
            $workoutPlan[$currentDay][] = trim($line);
        }
    }
    return $workoutPlan;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看儲存的AI回應 - 大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: white;
            color: #333;
        }
        .response-container {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .response-container h3 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .delete-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .delete-btn:hover {
            background-color: #ff3333;
        }
        details {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        summary {
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .response-details {
            margin-top: 10px;
        }
        .start-workout-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
            margin-top: 10px;
        }
        .start-workout-btn:hover {
            background-color: #45a049;
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

    <section class="responses">
        <div class="container">
            <h2>查看健身菜單紀錄</h2>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <details>
                        <summary>
                            <?= date('Y-m-d H:i', strtotime($row['created_at'])) ?>
                            <?= htmlspecialchars($row['input_summary']) ?>
                        </summary>
                        <div class="response-details">
                            <p>完整用戶輸入: <?= htmlspecialchars(trim($row['user_input'], '-')) ?></p>
                            <p>身高: <?= htmlspecialchars($row['height']) ?> cm</p>
                            <p>體重: <?= htmlspecialchars($row['weight']) ?> kg</p>
                            <p>不喜歡的食物: <?= htmlspecialchars($row['dislikes']) ?></p>
                            <p>目標: <?= htmlspecialchars($row['goal']) ?></p>
                            <h4>AI 回應:</h4>
                            <div class="ai-response">
                                <?= formatAIResponse($row['response']) ?>
                            </div>
                            <form method="post" onsubmit="return confirm('確定要刪除這個回應嗎？');">
                                <input type="hidden" name="response_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete" class="delete-btn">刪除</button>
                            </form>
                        </div>
                    </details>
                <?php endwhile; ?>
            <?php else: ?>
                <p>目前沒有儲存的回應。</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
