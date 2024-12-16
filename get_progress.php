<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '未登錄']);
    exit;
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 week'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 獲取進度數據
$stmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$progress = $result->fetch_all(MYSQLI_ASSOC);

// 處理數據
$dates = array_column($progress, 'date');
$weights = array_map(function($w) { return round(floatval($w), 2); }, array_column($progress, 'weight'));
$expected_weights = array_map(function($w) { return round(floatval($w), 2); }, array_column($progress, 'expected_weight'));
$calories_in = array_map('intval', array_column($progress, 'calories_in'));
$calories_out = array_map('intval', array_column($progress, 'calories_out'));
$net_calories = array_map(function($in, $out) { return $in - $out; }, $calories_in, $calories_out);

// 獲取推薦熱量
$stmt = $conn->prepare("SELECT bmr FROM user_analysis WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bmr = round(floatval($result->fetch_assoc()['bmr'] ?? 0), 2);
$recommended_calories = array_fill(0, count($progress), $bmr);

// 計算進度百分比
$latest_expected_weight = end($expected_weights);
$current_weight = end($weights);
$initial_weight = $weights[0];
if ($initial_weight != $latest_expected_weight) {
    $progress_percentage = ($initial_weight - $current_weight) / ($initial_weight - $latest_expected_weight) * 100;
    $progress_percentage = round(max(0, min(100, $progress_percentage)), 2);
} else {
    $progress_percentage = 0;
}

// 準備表格數據
$table_data = array_map(function($entry) use ($bmr) {
    $net_calories = intval($entry['calories_in']) - intval($entry['calories_out']);
    $calorie_difference = intval($entry['calories_in']) - $bmr;
    return [
        'date' => $entry['date'],
        'weight' => round(floatval($entry['weight']), 2),
        'expected_weight' => round(floatval($entry['expected_weight']), 2),
        'calories_in' => intval($entry['calories_in']),
        'calories_out' => intval($entry['calories_out']),
        'net_calories' => $net_calories,
        'calorie_difference' => $calorie_difference
    ];
}, $progress);

// 計算其他統計數據
$avg_calories = count($calories_in) > 0 ? round(array_sum($calories_in) / count($calories_in), 2) : 0;
$weight_trend = count($weights) > 1 ? ($weights[count($weights)-1] < $weights[0] ? "下降" : ($weights[count($weights)-1] > $weights[0] ? "上升" : "維持")) : "維持";
$avg_net_calories = count($net_calories) > 0 ? round(array_sum($net_calories) / count($net_calories), 2) : 0;
$weight_difference = round($current_weight - $latest_expected_weight, 2);

$response = [
    'dates' => $dates,
    'weights' => $weights,
    'expected_weights' => $expected_weights,
    'calories_in' => $calories_in,
    'calories_out' => $calories_out,
    'net_calories' => $net_calories,
    'recommended_calories' => $recommended_calories,
    'progress_percentage' => $progress_percentage,
    'table_data' => $table_data,
    'current_weight' => $current_weight,
    'weight_difference' => $weight_difference,
    'avg_calories' => $avg_calories,
    'weight_trend' => $weight_trend,
    'avg_net_calories' => $avg_net_calories
];

header('Content-Type: application/json');
echo json_encode($response);