<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 獲取用戶資料
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI菜單 - 大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .qa-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .profile-card h3 {
            color: #007bff;
            margin-top: 0;
        }
        #qaForm {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        #answer {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            min-height: 100px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .welcome {
    font-weight: bold;
    color: #007bff;
    max-width: 150px; /* 或其他適當的寬度 */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.welcome {
    font-weight: bold;
    color: #007bff;
    max-width: 100px; /* 根據需要設置適當寬度 */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* 文字過長時顯示省略號 */
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

    
    <div class="container">
        <div class="qa-container">
            <h2 style="text-align: center; color: #333;">AI菜單</h2>
            <div id="userProfile" class="profile-card">
                <h3>個人資料</h3>
                <p>身高: <?= htmlspecialchars($profile['height'] ?? '') ?> cm</p>
                <p>體重: <?= htmlspecialchars($profile['weight'] ?? '') ?> kg</p>
                <p>飲食偏好: <?= htmlspecialchars($profile['dietary_preferences'] ?? '') ?></p>
                <p>不喜歡的食物: <?= htmlspecialchars($profile['dislikes'] ?? '') ?></p>
                <p>目標: <?= htmlspecialchars($profile['goal'] ?? '') ?></p>
            </div>
            <form id="qaForm">
                <label for="question" style="display: block; margin-bottom: 10px;">提出你的問題：</label>
                <input type="text" id="question" name="question" placeholder="輸入你的問題" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                <button type="submit" class="btn-primary">發問</button>
            </form>
            <div id="answer"></div>
        </div>
    </div>

    <script>
    function formatAIResponse(response) {
        response = response.replace(/[\*\#\-]/g, '');
        var paragraphs = response.split('\n\n');
        var formattedResponse = '';
        paragraphs.forEach(function(paragraph) {
            if (paragraph.includes('|')) {
                formattedResponse += formatTable(paragraph);
            } else {
                formattedResponse += '<p>' + paragraph.trim() + '</p>';
            }
        });
        return formattedResponse;
    }

    function formatTable(tableString) {
        var rows = tableString.trim().split('\n');
        var html = '<table border="1"><thead><tr>';
        var headers = rows[0].split('|');
        headers.forEach(function(header) {
            html += '<th>' + header.trim() + '</th>';
        });
        html += '</tr></thead><tbody>';
        for (var i = 1; i < rows.length; i++) {
            html += '<tr>';
            var cells = rows[i].split('|');
            cells.forEach(function(cell) {
                html += '<td>' + cell.trim() + '</td>';
            });
            html += '</tr>';
        }
        html += '</tbody></table>';
        return html;
    }

    document.getElementById('qaForm').addEventListener('submit', function(event) {
        event.preventDefault();
        var question = document.getElementById('question').value;
        var height = '<?= $profile['height'] ?>';
        var weight = '<?= $profile['weight'] ?>';
        var dislikes = '<?= $profile['dislikes'] ?>';
        var goal = '<?= $profile['goal'] ?>';
        var userId = '<?= $_SESSION['user_id'] ?>';

        var askButton = document.querySelector('button[type="submit"]');
        var buttonDisplayStyle = askButton.style.display;

        askButton.style.display = 'none';
        document.getElementById('answer').innerHTML = '載入中...';

        fetch('https://101f-163-17-135-93.ngrok-free.app/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_input: question,
                height: height,
                weight: weight,
                dislikes: dislikes,
                goal: goal,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('answer').innerHTML = formatAIResponse(data.response);
            
            return fetch('/save_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_input: question,
                    height: height,
                    weight: weight,
                    dislikes: dislikes,
                    goal: goal,
                    response: data.response,
                    user_id: userId
                })
            });
        })
        .then(saveResponse => saveResponse.json())
        .then(saveData => {
            if (saveData.status === 'success') {
                console.log('資料已成功存儲');
            } else {
                console.error('資料存儲失敗: ' + saveData.message);
            }
            askButton.style.display = buttonDisplayStyle;
        })
        .catch(error => {
            console.error('錯誤:', error);
            askButton.style.display = buttonDisplayStyle;
        });
    });
    </script>
</body>
</html>