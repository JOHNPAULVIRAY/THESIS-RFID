<?php
include('../config/functions.php');
$conn = dbConnect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gate = $_POST['gate'] ?? 1;
    $gateValue = ($gate == 1) ? 'Gate 1' : 'Gate 2';

    $query = "SELECT s.name, s.lrn, g.name AS grade, sec.name AS section, 
                 l.action, s.photo_path, l.timestamp 
          FROM logs l 
          JOIN students s ON l.student_id = s.id 
          JOIN grades g ON s.grade_id = g.id 
          JOIN sections sec ON s.section_id = sec.id 
          WHERE l.gate = ? 
          ORDER BY l.timestamp DESC LIMIT 1";  // Order by timestamp


    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Query preparation failed"]);
        exit();
    }

    $stmt->bind_param("s", $gateValue);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Query execution failed"]);
        exit();
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    if ($data) {
        echo json_encode([
            "status" => "success",
            "student" => [
                "name" => $data['name'],
                "lrn" => $data['lrn'],
                "grade" => $data['grade'],
                "section" => $data['section'],
                "timestamp" => date('g:i A', strtotime($data['timestamp'])),
                "action" => $data['action'],
                "photo_path" => $data['photo_path'] ?? '../statics/img/image.png'
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No data found"]);
    }
}
?>
