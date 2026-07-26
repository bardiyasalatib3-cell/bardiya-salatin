<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    echo json_encode(['error' => 'دسترسی غیرمجاز']);
    exit();
}

require_once 'config.php';

$student_id = (int) $_GET['id'];

$phone_result = $conn->query("SELECT phone FROM users WHERE id = $student_id AND role = 'student'");
if ($phone_result && $phone_result->num_rows > 0) {
    $phone = $phone_result->fetch_assoc()['phone'];
    $scores_result = $conn->query("SELECT score, exam_date FROM exam_results WHERE user_phone = '$phone' ORDER BY exam_date DESC LIMIT 15");
    $scores = [];
    while ($row = $scores_result->fetch_assoc()) {
        $score_from_20 = round($row['score'] / 5, 1);
        $scores[] = ['score' => $score_from_20, 'date' => date('Y/m/d H:i', strtotime($row['exam_date']))];
    }
    echo json_encode(['scores' => $scores]);
} else {
    echo json_encode(['scores' => []]);
}
$conn->close();
?>