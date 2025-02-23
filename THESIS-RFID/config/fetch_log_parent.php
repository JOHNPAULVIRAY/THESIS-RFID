<?php
session_start();
include('functions.php');  

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = dbConnect();

// Get linked_student_id
$userQuery = "SELECT linked_student_id FROM users WHERE id = ?";
$student_id = null;

if ($stmt = $conn->prepare($userQuery)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['linked_student_id'])) {
        $student_id = $user['linked_student_id'];
    }
}

// Fetch logs if student_id exists
$logs = [];

if ($student_id) {
    $logQuery = "SELECT l.timestamp, l.gate, l.action, s.name
                 FROM logs l
                 JOIN students s ON l.student_id = s.id
                 WHERE l.student_id = ?
                 ORDER BY l.id DESC";

    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        $stmt->close();
    }
}

$conn->close();
echo json_encode($logs);
?>
