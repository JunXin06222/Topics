<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 獲取用戶的最新基礎代謝率
$stmt = $conn->prepare("SELECT bmr FROM user_analysis WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bmr = $result->fetch_assoc()['bmr'] ?? 0;
$bmr = floatval($bmr);

// 獲取用戶的最新預期體重
$stmt = $conn->prepare("SELECT expected_weight FROM user_progress WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$latest_expected_weight = $result->fetch_assoc()['expected_weight'] ?? 0;
$latest_expected_weight = floatval($latest_expected_weight);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reset'])) {
        // 還原功能
        $stmt = $conn->prepare("DELETE FROM user_progress WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $reset_message = "所有進度數據已被清除。";
    } elseif (isset($_POST['record'])) {
        // 記錄功能
        $weight = floatval($_POST['weight']);
        $date = $_POST['date'];
        $expected_weight = floatval($_POST['expected_weight']);
        $calories_in = intval($_POST['calories_in']);
        $calories_out = intval($_POST['calories_out']);

        // 如果沒有輸入新的預期體重,使用最新的預期體重
        if ($expected_weight == 0) {
            $expected_weight = $latest_expected_weight;
        }

        $stmt = $conn->prepare("INSERT INTO user_progress (user_id, weight, date, expected_weight, calories_in, calories_out) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsdii", $user_id, $weight, $date, $expected_weight, $calories_in, $calories_out);
        $stmt->execute();
        
        // 添加跳轉
        echo "<script>
            alert('數據已成功記錄！');
            window.location.href = 'progress.php';
        </script>";
        exit;
    } elseif (isset($_POST['delete'])) {
        // 刪除特定記錄
        $date_to_delete = $_POST['delete_date'];
        $stmt = $conn->prepare("DELETE FROM user_progress WHERE user_id = ? AND date = ?");
        $stmt->bind_param("is", $user_id, $date_to_delete);
        $stmt->execute();
        $delete_message = "已刪除 $date_to_delete 的記錄。";
    }
}

// 設置默認日期範圍為過去一周
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 week'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 獲取指定日期範圍的進度數據
$stmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$progress = $result->fetch_all(MYSQLI_ASSOC);

// 如果沒有數據，獲取最近的一周數據
if (empty($progress)) {
    $stmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ? ORDER BY date DESC LIMIT 7");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = array_reverse($result->fetch_all(MYSQLI_ASSOC));
}

// 計算每日推薦熱量
$recommended_calories = $bmr;

// 獲取最新的體重記錄
$stmt = $conn->prepare("SELECT weight FROM user_progress WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_weight = $result->fetch_assoc()['weight'] ?? 0;
$current_weight = floatval($current_weight);

// 計算距離目標體重的差距
$weight_difference = $current_weight - $latest_expected_weight;

// 計算本週平均卡路里攝入量
$stmt = $conn->prepare("SELECT AVG(calories_in) as avg_calories FROM user_progress WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_calories = $result->fetch_assoc()['avg_calories'];
$avg_calories = $avg_calories ? round($avg_calories) : 0; // 如果是 NULL，設為 0

// 計算體重變化趨勢
$weight_trend = "維持";
if (count($progress) > 1) {
    $first_weight = $progress[0]['weight'];
    $last_weight = end($progress)['weight'];
    if ($last_weight < $first_weight) {
        $weight_trend = "下降";
    } elseif ($last_weight > $first_weight) {
        $weight_trend = "上升";
    }
}

// 計算平均每日卡路里盈餘/赤字
$total_net_calories = 0;
foreach ($progress as $entry) {
    $total_net_calories += $entry['calories_in'] - $entry['calories_out'];
}
$avg_net_calories = count($progress) > 0 ? round($total_net_calories / count($progress)) : 0;
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>進度追蹤 - 大肌肌健身平台</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* 进度追踪页面样式 */
        .progress-tracking {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .progress-tracking h1 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }

        .tab-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f1f1f1;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }

        .tab {
            display: flex;
            background-color: transparent;
            border-radius: 0;
            box-shadow: none;
        }

        .tab button {
            background-color: #f1f1f1;
            color: #333;
            padding: 15px 20px;
            transition: 0.3s;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .tab button:hover {
            background-color: #ddd;
        }

        .tab button.active {
            background-color: #007bff;
            color: white;
        }

        .tabcontent {
            padding: 20px;
            border: none;
            background-color: #fff;
            border-radius: 0 0 8px 8px;
        }

        .progress-form, .progress-charts, .summary-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .progress-form input, .progress-form button {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .chart-container {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 250px;
            width: 90%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-item {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .summary-item h3 {
            color: #007bff;
        }

        .summary-item p {
            font-size: 1.4em;
            color: #28a745;
        }

        .buttons button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .buttons button:hover {
            background-color: #0056b3;
        }

        .reset-button-container {
            margin-right: 10px;
        }

        .reset-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .reset-button:hover {
            background-color: #c82333;
        }

        .progress-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .progress-table th, .progress-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .progress-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .progress-table tr:hover {
            background-color: #f5f5f5;
        }

        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .overview {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .overview-item {
            flex: 1;
            min-width: 200px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .overview-item h3 {
            color: #007bff;
            margin-bottom: 10px;
        }

        .overview-item p {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }

        .progress-form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }

        .progress-form input[type="number"],
        .progress-form input[type="date"] {
            width: 100%;
            box-sizing: border-box;
        }

        .date-range-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .date-range-form input,
        .date-range-form button {
            margin: 0 10px;
        }
    </style>
    <script>
        function confirmReset() {
            return confirm('確定要清除所有進度數據嗎？此操作不可逆。');
        }
        function confirmDelete(date) {
            return confirm('確定要刪除 ' + date + ' 的記錄嗎？此操作不可逆。');
        }
        function toggleExpectedWeight() {
            var checkbox = document.getElementById('update_expected_weight');
            var input = document.getElementById('expected_weight');
            input.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                input.value = "<?php echo $latest_expected_weight; ?>";
            }
        }
        function queryProgress() {
            var startDate = document.getElementById('start_date').value;
            var endDate = document.getElementById('end_date').value;
            
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(this.responseText);
                    updateCharts(response);
                }
            };
            xhr.open("GET", "get_progress.php?start_date=" + startDate + "&end_date=" + endDate, true);
            xhr.send();
        }

        function updateCharts(data) {
            // 更新體重圖表
            weightChart.data.labels = data.dates;
            weightChart.data.datasets[0].data = data.weights;
            weightChart.data.datasets[1].data = data.expected_weights;
            weightChart.data.datasets[2].data = data.weights; // 趨勢線
            weightChart.update();

            // 更新卡路里圖表
            caloriesChart.data.labels = data.dates;
            caloriesChart.data.datasets[0].data = data.calories_in;
            caloriesChart.data.datasets[1].data = data.calories_out;
            caloriesChart.data.datasets[2].data = data.recommended_calories;caloriesChart.data.datasets[3].data = data.net_calories;
            caloriesChart.update();

            // 更新表格
            var tableBody = document.querySelector('.progress-table tbody');
            tableBody.innerHTML = ''; // 清空現有的表格內容
            
            data.table_data.forEach(function(entry) {
                var row = tableBody.insertRow();
                row.insertCell(0).textContent = entry.date;
                row.insertCell(1).textContent = entry.weight;
                row.insertCell(2).textContent = entry.expected_weight;
                row.insertCell(3).textContent = entry.calories_in;
                row.insertCell(4).textContent = entry.calories_out;
                row.insertCell(5).textContent = entry.net_calories;
                row.insertCell(6).textContent = entry.calorie_difference;
                
                // 添加刪除按鈕
                var deleteCell = row.insertCell(7);
                var deleteForm = document.createElement('form');
                deleteForm.method = 'post';
                deleteForm.className = 'delete-form';
                deleteForm.onsubmit = function() { return confirmDelete(entry.date); };
                
                var deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_date';
                deleteInput.value = entry.date;
                
                var deleteButton = document.createElement('button');
                deleteButton.type = 'submit';
                deleteButton.name = 'delete';
                deleteButton.className = 'delete-button';
                deleteButton.textContent = '刪除';
                
                deleteForm.appendChild(deleteInput);
                deleteForm.appendChild(deleteButton);
                deleteCell.appendChild(deleteForm);
            });
        }
    </script>
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

    <main style="padding: 0 20px;">
        <h1 style="color: black; text-align: center;">進度追蹤</h1>
        
        <div class="tab-container">
            <div class="tab">
                <button class="tablinks" onclick="openTab(event, 'Record')" id="defaultOpen">記錄進度</button>
                <button class="tablinks" onclick="openTab(event, 'View')">查看進度</button>
            </div>
            <div class="reset-button-container">
                <form method="post" onsubmit="return confirmReset()">
                    <button type="submit" name="reset" class="reset-button">還原所有數據</button>
                </form>
            </div>
        </div>

        <div id="Record" class="tabcontent">
            <h2>記錄新的進度</h2>
            <div class="progress-form">
                <form method="post">
                    <label for="weight">體重 (kg):</label>
                    <input type="number" step="0.1" id="weight" name="weight" required>

                    <label for="update_expected_weight">更新預期體重?</label>
                    <input type="checkbox" id="update_expected_weight" onchange="toggleExpectedWeight()">

                    <label for="expected_weight">預期體重 (kg):</label>
                    <input type="number" step="0.1" id="expected_weight" name="expected_weight" value="<?php echo htmlspecialchars($latest_expected_weight); ?>" disabled>

                    <label for="calories_in">攝入熱量 (卡路里):</label>
                    <input type="number" id="calories_in" name="calories_in" required>

                    <label for="calories_out">消耗熱量 (卡路里):</label>
                    <input type="number" id="calories_out" name="calories_out" required>

                    <label for="date">日期:</label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">

                    <div class="buttons">
                        <button type="submit" name="record">記錄</button>
                    </div>
                </form>

                <?php if (isset($success_message)): ?>
                    <p class="success-message"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <?php if (isset($reset_message)): ?>
                    <p class="reset-message"><?php echo $reset_message; ?></p>
                <?php endif; ?>

                <?php if (isset($delete_message)): ?>
                    <p class="delete-message"><?php echo $delete_message; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div id="View" class="tabcontent">
            <h2>查看進度</h2>
            <div class="overview">
                <div class="overview-item">
                    <h3>當前體重</h3>
                    <p><?php echo number_format($current_weight,1); ?> kg</p>
                </div>
                <div class="overview-item">
                    <h3>距離目標體重</h3>
                    <p><?php echo number_format($weight_difference, 1); ?> kg</p>
                </div>
                <div class="overview-item">
                    <h3>本週平均卡路里攝入</h3>
                    <p><?php echo $avg_calories; ?> 卡路里</p>
                </div>
                <div class="overview-item">
                    <h3>體重變化趨勢</h3>
                    <p class="trend-<?php echo strtolower($weight_trend); ?>"><?php echo $weight_trend; ?></p>
                </div>
                <div class="overview-item">
                    <h3>平均每日卡路里盈餘/赤字</h3>
                    <p><?php echo $avg_net_calories; ?> 卡路里</p>
                </div>
            </div>

            <div class="date-range-form">
                <form onsubmit="event.preventDefault(); queryProgress();">
                    <label for="start_date">開始日期:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">

                    <label for="end_date">結束日期:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">

                    <button type="submit">查詢</button>
                </form>
            </div>

            <div class="progress-charts">
                <div class="chart-container">
                    <canvas id="weightChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="caloriesChart"></canvas>
                </div>
            </div>

            <table class="progress-table">
                <thead>
                    <tr>
                        <th>日期</th>
                        <th>實際體重 (kg)</th>
                        <th>預期體重 (kg)</th>
                        <th>攝入熱量 (卡路里)</th>
                        <th>消耗熱量 (卡路里)</th>
                        <th>淨熱量 (卡路里)</th>
                        <th>與推薦攝入量差異</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress as $entry): 
                        $net_calories = $entry['calories_in'] - $entry['calories_out'];
                        $calorie_difference = round($entry['calories_in'] - $recommended_calories, 2);
                    ?>
                    <tr>
                        <td><?php echo $entry['date']; ?></td>
                        <td><?php echo $entry['weight']; ?></td>
                        <td><?php echo $entry['expected_weight']; ?></td>
                        <td><?php echo $entry['calories_in']; ?></td>
                        <td><?php echo $entry['calories_out']; ?></td>
                        <td><?php echo $net_calories; ?></td>
                        <td><?php echo number_format($calorie_difference, 2); ?></td>
                        <td>
                            <form method="post" class="delete-form" onsubmit="return confirmDelete('<?php echo $entry['date']; ?>')">
                                <input type="hidden" name="delete_date" value="<?php echo $entry['date']; ?>">
                                <button type="submit" name="delete" class="delete-button">刪除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            function openTab(evt, tabName) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";
            }

            document.getElementById("defaultOpen").click();

            var ctx = document.getElementById('weightChart').getContext('2d');
            var weightChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($progress, 'date')); ?>,
                    datasets: [{
                        label: '實際體重 (kg)',
                        data: <?php echo json_encode(array_column($progress, 'weight')); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    },
                    {
                        label: '預期體重 (kg)',
                        data: <?php echo json_encode(array_column($progress, 'expected_weight')); ?>,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    },
                    {
                        label: '體重趨勢',
                        data: <?php echo json_encode(array_column($progress, 'weight')); ?>,
                        borderColor: 'rgb(54, 162, 235)',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });

            var ctxCalories = document.getElementById('caloriesChart').getContext('2d');
            var caloriesChart = new Chart(ctxCalories, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($progress, 'date')); ?>,
                    datasets: [{
                        label: '攝入熱量',
                        data: <?php echo json_encode(array_column($progress, 'calories_in')); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    },
                    {
                        label: '消耗熱量',
                        data: <?php echo json_encode(array_column($progress, 'calories_out')); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    },
                    {
                        label: '推薦攝入量',
                        data: <?php echo json_encode(array_fill(0, count($progress), $recommended_calories)); ?>,
                        type: 'line',
                        borderColor: 'rgb(54, 162, 235)',
                        fill: false
                    },
                    {
                        label: '淨卡路里',
                        data: <?php echo json_encode(array_map(function($entry) { return $entry['calories_in'] - $entry['calories_out']; }, $progress)); ?>,
                        type: 'line',
                        borderColor: 'rgb(255, 159, 64)',
                        fill: false
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
        </script>
    </main>
</body>
</html>