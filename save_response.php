<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '使用者未登入']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$user_input = $data['user_input'];
$height = $data['height'];
$weight = $data['weight'];
$dislikes = $data['dislikes'];
$goal = $data['goal'];
$response = $data['response'];

$sql = "INSERT INTO ai_responses (user_id, user_input, height, weight, dislikes, goal, response) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssss", $user_id, $user_input, $height, $weight, $dislikes, $goal, $response);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>