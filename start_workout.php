<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "00519";
$dbname = "fitnessplatform";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 檢查用戶是否已登錄
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = date('Y-m-d H:i:s');
    $type = $_POST['type'];
    $duration = $_POST['duration'];
    $actions = $_POST['actions'];

    $sql = "INSERT INTO exercises (user_id, date, type, duration, actions) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis", $user_id, $date, $type, $duration, $actions);
    $stmt->execute();
    $stmt->close();
}

// 獲取運動記錄
$sql = "SELECT * FROM exercises WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$exercises = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $exercises[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>進階運動計時器</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="style.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f0f4f8;
            transition: background-color 0.5s ease;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        h1::after, h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background-color: #3498db;
            margin: 10px auto 0;
        }
        .exercise-types-explanation, .setup, .timer, .calendar, .stats {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .exercise-types-explanation {
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .setup, .timer {
            height: auto;
            overflow-y: visible;
        }
        .setup:hover, .timer:hover, .calendar:hover, .stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .control-buttons button {
            padding: 12px 24px;
            font-size: 16px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .control-buttons button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        #timer {
            font-size: 72px;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
            color: #2c3e50;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        #phase, #action, #cycle, #set {
            font-size: 20px;
            margin: 15px 0;
            text-align: center;
            font-weight: 300;
        }
        .exercise-types-explanation h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .exercise-types-explanation p {
            margin-bottom: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            #timer {
                font-size: 72px;
            }
            .control-buttons button {
                padding: 10px 20px;
                font-size: 14px;
            }
        }

        #calendar {
            height: 600px;
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin: 20px 0;
        }

        .action-input {
            margin-bottom: 10px;
        }
        #actionList {
            margin-bottom: 20px;
        }
        .control-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .control-buttons button {
            margin: 0 10px;
        }
        .yellow { color: #f39c12; }
        .green { color: #2ecc71; }
        .blue { color: #3498db; }
        body.prep { background-color: #FFF9C4; }
        body.exercise { background-color: #C8E6C9; }
        body.rest { background-color: #BBDEFB; }
        
        .setup {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-list {
            margin-bottom: 20px;
        }

        .setup-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-add, .btn-start {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
        }

        .btn-start {
            background-color: #007bff;
            color: white;
        }

        .sets-input {
            display: flex;
            align-items: center;
        }

        .sets-input label {
            margin-right: 10px;
        }

        .sets-input input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .setup-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .setup-controls > * {
                margin-bottom: 10px;
            }
        }

        .action-input {
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            transition: box-shadow 0.3s ease;
        }

        .action-input:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-input label {
            display: inline-block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }

        .action-input select,
        .action-input input[type="text"],
        .action-input input[type="number"] {
            width: calc(100% - 16px);
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }

        .action-input select:focus,
        .action-input input[type="text"]:focus,
        .action-input input[type="number"]:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
        <h1>進階運動計時器</h1>
        
        <div class="exercise-types-explanation">
            <h3>運動類型說明：</h3>
            <p><strong>有氧運動：</strong> 長時間、低強度的運動，能提高心肺功能，如慢跑、游泳、騎自行車等。</p>
            <p><strong>無氧運動：</strong> 短時間、高強度的運動，能增加肌肉力量和爆發力，如舉重、短跑、深蹲等。</p>
        </div>

        <div class="setup">
            <h2>運動設置</h2>
            <div id="actionList" class="action-list">
                <!-- 動作列表將在這裡動態生成 -->
            </div>
            <div class="setup-controls">
                <button onclick="addAction()" class="btn btn-add">添加動作</button>
                <div class="sets-input">
                    <label for="sets">總組數:</label>
                    <input type="number" id="sets" value="1" min="1">
                </div>
                <button onclick="startTimer()" class="btn btn-start">開始運動</button>
            </div>
        </div>
        
        <div class="timer">
            <h2>計時器</h2>
            <div id="timer">00:00</div>
            <div id="phase">準備開始</div>
            <div id="action">動作: </div>
            <div id="cycle">循環: 0 / 0</div>
            <div id="set">組數: 0 / 0</div>
            <div class="control-buttons">
                <button onclick="pauseTimer()">暫停</button><button onclick="resumeTimer()">繼續</button>
                <button onclick="resetTimer()">重置</button>
            </div>
        </div>
        
        <div class="calendar">
            <h2>運動日曆</h2>
            <div id="calendar"></div>
        </div>
        
        <div class="stats">
            <h2>數據統計</h2>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        let timer;
        let isPaused = false;
        let currentPhase = 'prep';
        let currentTime = 0;
        let currentAction = 0;
        let currentCycle = 0;
        let currentSet = 0;
        let totalSets = 0;
        let actions = [];function addAction() {
        const actionList = document.getElementById('actionList');
        const actionIndex = actions.length;
        const actionHtml = `
            <div class="action-input">
                <label for="type${actionIndex}">運動類型:</label>
                <select id="type${actionIndex}" style="width: 100%;">
                    <option value="aerobic">有氧</option>
                    <option value="anaerobic">無氧</option>
                </select>
                <label for="name${actionIndex}">動作名稱:</label>
                <input type="text" id="name${actionIndex}" placeholder="例如：深蹲">
                <label for="prepTime${actionIndex}">預備時間 (秒):</label>
                <input type="number" id="prepTime${actionIndex}" value="15">
                <label for="exerciseTime${actionIndex}">運動時間 (秒):</label>
                <input type="number" id="exerciseTime${actionIndex}" value="60">
                <label for="restTime${actionIndex}">休息時間 (秒):</label>
                <input type="number" id="restTime${actionIndex}" value="60">
                <label for="cycles${actionIndex}">循環次數:</label>
                <input type="number" id="cycles${actionIndex}" value="3">
            </div>
        `;
        actionList.insertAdjacentHTML('beforeend', actionHtml);
        actions.push({});
    }

    function startTimer() {
        clearInterval(timer);
        isPaused = false;
        actions = [];
        const actionInputs = document.querySelectorAll('.action-input');
        actionInputs.forEach((actionInput, index) => {
            actions.push({
                type: document.getElementById(`type${index}`).value,
                name: document.getElementById(`name${index}`).value,
                prepTime: parseInt(document.getElementById(`prepTime${index}`).value),
                exerciseTime: parseInt(document.getElementById(`exerciseTime${index}`).value),
                restTime: parseInt(document.getElementById(`restTime${index}`).value),
                cycles: parseInt(document.getElementById(`cycles${index}`).value)
            });
        });
        currentPhase = 'prep';
        currentAction = 0;
        currentTime = actions[0].prepTime;
        currentCycle = 1;
        currentSet = 1;
        totalSets = parseInt(document.getElementById('sets').value);
        updateDisplay();
        timer = setInterval(updateTimer, 1000);
    }

    function pauseTimer() {
        isPaused = true;
        clearInterval(timer);
    }

    function resumeTimer() {
        if (isPaused) {
            isPaused = false;
            timer = setInterval(updateTimer, 1000);
        }
    }

    function resetTimer() {
        clearInterval(timer);
        currentPhase = 'prep';
        currentTime = 0;
        currentAction = 0;
        currentCycle = 0;
        currentSet = 0;
        updateDisplay();
    }

    function updateTimer() {
        if (isPaused) return;
        currentTime--;
        if (currentTime <= 0) {
            switchPhase();
        }
        updateDisplay();
    }

    function switchPhase() {
        const currentActionData = actions[currentAction];
        if (currentPhase === 'prep') {
            currentPhase = 'exercise';
            currentTime = currentActionData.exerciseTime;
        } else if (currentPhase === 'exercise') {
            if (currentCycle < currentActionData.cycles) {
                currentPhase = 'rest';
                currentTime = currentActionData.restTime;
                currentCycle++;
            } else {
                currentAction++;
                if (currentAction < actions.length) {
                    currentPhase = 'prep';
                    currentTime = actions[currentAction].prepTime;
                    currentCycle = 1;
                } else if (currentSet < totalSets) {
                    currentAction = 0;
                    currentPhase = 'prep';
                    currentTime = actions[0].prepTime;
                    currentSet++;
                    currentCycle = 1;
                } else {
                    clearInterval(timer);
                    alert('運動完成!');
                    recordExercise();
                    return;
                }
            }
        } else if (currentPhase === 'rest') {
            currentPhase = 'exercise';
            currentTime = currentActionData.exerciseTime;
        }
    }

    function updateDisplay() {
        document.getElementById('timer').textContent = formatTime(currentTime);
        document.getElementById('phase').textContent = getPhaseText();
        document.getElementById('action').textContent = `動作: ${actions[currentAction].name}`;
        document.getElementById('cycle').textContent = `循環: ${currentCycle} / ${actions[currentAction].cycles}`;
        document.getElementById('set').textContent = `組數: ${currentSet} / ${totalSets}`;
        
        document.getElementById('timer').className = 
            currentPhase === 'prep' ? 'yellow' : 
            currentPhase === 'exercise' ? 'green' : 'blue';
        
        document.body.className = currentPhase;
        
        playSound(currentPhase);
    }

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function getPhaseText() {
        switch(currentPhase) {
            case 'prep': return '預備';
            case 'exercise': return '運動';
            case 'rest': return '休息';
        }
    }

    function playSound(phase) {
        const audio = new Audio();
        switch(phase) {
            case 'prep':
                audio.src = 'prep_sound.mp3';
                break;
            case 'exercise':
                audio.src = 'exercise_sound.mp3';
                break;
            case 'rest':
                audio.src = 'rest_sound.mp3';
                break;
        }
        audio.play();
    }

    function recordExercise() {
        const totalDuration = actions.reduce((sum, action) => 
            sum + (action.prepTime + action.exerciseTime + action.restTime) * action.cycles, 0) * totalSets;
        
        const form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';

        const typeInput = document.createElement('input');
        typeInput.name = 'type';
        typeInput.value = actions.map(a => a.type).join(', ');
        form.appendChild(typeInput);

        const durationInput = document.createElement('input');
        durationInput.name = 'duration';
        durationInput.value = totalDuration;
        form.appendChild(durationInput);

        const actionsInput = document.createElement('input');
        actionsInput.name = 'actions';
        actionsInput.value = JSON.stringify(actions);
        form.appendChild(actionsInput);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeCalendar();
        updateCharts();
        addAction(); // 添加第一個動作輸入
    });

    function initializeCalendar() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'zh-tw',
            buttonText: {
                today: '今天'
            },
            events: getCalendarEvents(),
            dateClick: function(info) {
                const details = getExerciseDetails(info.dateStr);
                alert(`${info.dateStr} 的運動詳情:\n\n${details}`);
            }
        });
        calendar.render();
    }

    function getExerciseDetails(date) {
    const exercises = <?php echo json_encode($exercises); ?>;
    const dailyExercises = exercises.filter(exercise => exercise.date.startsWith(date));
    
    if (dailyExercises.length === 0) {
        return "這天沒有記錄運動";
    }
    
    let details = "";
    dailyExercises.forEach((exercise, index) => {
        details += `運動 ${index + 1}:\n`;
        details += `類型: ${exercise.type.replace('aerobic', '有氧').replace('anaerobic', '無氧')}\n`;
        details += `時長: ${exercise.duration} 秒\n`;
        details += `動作:\n`;
        
        const actions = JSON.parse(exercise.actions);
        actions.forEach(action => {
            details += `  - ${action.name}（${action.type === 'aerobic' ? '有氧' : '無氧'}）:\n`;
            details += `    準備: ${action.prepTime}秒, `;
            details += `運動: ${action.exerciseTime}秒, `;
            details += `休息: ${action.restTime}秒, `;
            details += `循環: ${action.cycles}次\n`;
        });
        
        details += "\n";
    });
    
    return details;
}

    function getCalendarEvents() {
        const exercises = <?php echo json_encode($exercises); ?>;
        let eventCounts = {};

        exercises.forEach(exercise => {
            const date = exercise.date.split(' ')[0]; // 只取日期部分
            const types = exercise.type.split(', ');

            if (!eventCounts[date]) {
                eventCounts[date] = { aerobic: 0, anaerobic: 0 };
            }

            types.forEach(type => {
                if (type === 'aerobic') eventCounts[date].aerobic++;
                if (type === 'anaerobic') eventCounts[date].anaerobic++;
            });
        });

        return Object.entries(eventCounts).map(([date, counts]) => {
            let title = '';
            if (counts.aerobic > 0) title += `有氧:${counts.aerobic} `;
            if (counts.anaerobic > 0) title += `無氧:${counts.anaerobic}`;

            return {
                title: title.trim(),
                start: date,
                allDay: true
            };
        });
    }

    function updateCharts() {
        const exercises = <?php echo json_encode($exercises); ?>;
        updateWeeklyChart(exercises);
        updateMonthlyChart(exercises);
        updateTypeChart(exercises);
    }

    function updateWeeklyChart(exercises) {
        const weekDays = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];
        const weeklyCounts = new Array(7).fill(0);

        exercises.forEach(exercise => {
            const day = new Date(exercise.date).getDay();
            weeklyCounts[day]++;
        });

        const ctx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: weekDays,
                datasets: [{
                    label: '每週運動次數',
                    data: weeklyCounts,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function updateMonthlyChart(exercises) {
        const monthNames = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'];
        const monthlyDurations = new Array(12).fill(0);

        exercises.forEach(exercise => {
            const month = new Date(exercise.date).getMonth();
            monthlyDurations[month] += exercise.duration / 60; // 轉換為分鐘
        });

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthNames,
                datasets: [{
                    label: '每月運動時長 (分鐘)',
                    data: monthlyDurations,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function updateTypeChart(exercises) {
        const typeCounts = {
            '有氧': 0,
            '無氧': 0
        };

        exercises.forEach(exercise => {
            const types = exercise.type.split(', ');
            types.forEach(type => {
                if (type === 'aerobic') typeCounts['有氧']++;
                if (type === 'anaerobic') typeCounts['無氧']++;
            });
        });

        const ctx = document.getElementById('typeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['有氧', '無氧'],
                datasets: [{
                    label: '運動類型次數',
                    data: [typeCounts['有氧'], typeCounts['無氧']],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>
</body>
</html>