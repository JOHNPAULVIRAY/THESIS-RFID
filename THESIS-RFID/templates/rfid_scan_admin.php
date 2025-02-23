<?php
include('../config/functions.php');
$conn = dbConnect();

function getLatestEntry($conn, $gateValue) {
    $query = "SELECT s.name, s.lrn, g.name AS grade, sec.name AS section, 
                     l.action, s.photo_path, l.timestamp 
              FROM logs l 
              JOIN students s ON l.student_id = s.id 
              JOIN grades g ON s.grade_id = g.id 
              JOIN sections sec ON s.section_id = sec.id 
              WHERE l.gate = ? 
              ORDER BY l.timestamp DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ["status" => "error", "message" => "Query preparation failed"];
    }

    $stmt->bind_param("s", $gateValue);
    if (!$stmt->execute()) {
        return ["status" => "error", "message" => "Query execution failed"];
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return $data ? [
        "name" => $data['name'],
        "lrn" => $data['lrn'],
        "grade" => $data['grade'],
        "section" => $data['section'],
        "timestamp" => date('g:i A', strtotime($data['timestamp'])),
        "action" => $data['action'],
        "photo_path" => $data['photo_path'] ?? '../statics/img/image.png'
    ] : null;
}

$gate1_data = getLatestEntry($conn, "Gate 1");
$gate2_data = getLatestEntry($conn, "Gate 2");

$conn->close();

echo json_encode([
    "status" => "success",
    "gate1" => $gate1_data,
    "gate2" => $gate2_data
]);
?>
