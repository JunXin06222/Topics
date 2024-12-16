<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .hero {
            background-color: #ecf0f1;
            padding: 4rem 0;
        }
        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .hero-text {
            flex: 1;
            padding-right: 2rem;
        }
        .hero-image {
            flex: 1;
            text-align: right;
        }
        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .features {
            background-color: #f4f4f9;
            padding: 4rem 0;
        }
        .feature-item {
            margin-bottom: 2rem;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-5px);
        }
        .feature-image {
            float: left;
            width: 40%;
            height: 200px;
            overflow: hidden;
        }
        .feature-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .feature-content {
            float: right;
            width: 60%;
            padding: 1.5rem;
            box-sizing: border-box;
        }
        .feature-content h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .feature-item::after {
            content: "";
            display: table;
            clear: both;
        }
        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            text-align: center;
            padding: 1rem 0;
        }
        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
            }
            .hero-text, .hero-image {
                flex: none;
                width: 100%;
                text-align: center;
                padding-right: 0;
                margin-bottom: 2rem;
            }
            .feature-image, .feature-content {
                float: none;
                width: 100%;
            }
            .feature-image {
                height: 200px;
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

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>歡迎來到大肌肌健身平台</h2>
                    <p>這裡是您的個人健身助手，幫助您達成健身目標。我們提供量身定制的計劃、進度追蹤和指導，讓您的健身之旅更加有效和愉快。無論您是初學者還是有在健身的人，都能幫助你改善健身計畫。立即開始，邁向更健康的自己！</p>
                </div>
                <div class="hero-image">
                    <img src="1.jpg" alt="健身圖片">
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>網站特色</h2>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="AI.jpg" alt="AI 智能健身飲食計畫">
                </div>
                <div class="feature-content">
                    <h3>AI 智慧健身飲食計畫</h3>
                    <p>運用人工智慧技術，根據您的身體數據、健身目標和飲食偏好，提供量身定制的健身和飲食計劃，確保計劃符合您的需求。</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="追蹤.jpg" alt="進度追蹤">
                </div>
                <div class="feature-content">
                    <h3>進度追蹤</h3>
                    <p>通過圖表和數據分析，記錄和展示您的健身進度。這不僅能幫助您看到自己的進步，還能激勵您持續前進。</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="分析.jpg" alt="身體分析">
                </div>
                <div class="feature-content">
                    <h3>身體分析</h3>
                    <p>對您的身體指數進行分析。我們提供BMI、體脂率、代謝率等基本指標。讓您對自己的身體狀況有更深入的了解，從而制定更有效的健身策略。</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="歷史資料.jpg" alt="歷史資料">
                </div>
                <div class="feature-content">
                    <h3>歷史資料</h3>
                    <p>查看您過去的健身飲食記錄。歷史數據功能方便您回顧過去，了解哪些計畫適合您。幫助您做出更好的健身策略。</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="計時.jpg" alt="運動計時器">
                </div>
                <div class="feature-content">
                    <h3>運動計時器</h3>
                    <p>運動計時器能幫助您控制運動時間，還能根據您的運動類型自動調整間歇時間。它還能與您的健身計劃同步，提醒您完成每日運動目標。讓您的每次訓練都高效而有規律。</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2023 大肌肌健身平台. All rights reserved.</p>
        </div>
    </footer>

    <script>
    function checkReminder() {
        var lastUse = localStorage.getItem('lastUse');
        var now = new Date().getTime();
        
        if (!lastUse || now - lastUse > 7 * 24 * 60 * 60 * 1000) {
            alert('別忘了更新你的進度並使用 AI 菜單！');
        }
        
        localStorage.setItem('lastUse', now);
    }

    window.onload = checkReminder;
    </script>
</body>
</html>