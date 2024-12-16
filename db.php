<?php
$servername = "localhost";
$username = "root";
$password = "00519";
$dbname = "fitnessplatform";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}
?>
