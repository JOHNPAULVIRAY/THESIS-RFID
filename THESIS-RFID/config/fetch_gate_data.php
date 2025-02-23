<?php
require 'db_connection.php'; // Ensure you connect to your database

function getGateData($conn, $gate) {
    $gateValue = ($gate == 1) ? 'Gate 1' : 'Gate 2';
    $query = "SELECT s.name, s.lrn, g.name AS grade, sec.name AS section, l.action, s.photo_path, l.timestamp 
              FROM logs l 
              JOIN students s ON l.student_id = s.id 
              JOIN grades g ON s.grade_id = g.id 
              JOIN sections sec ON s.section_id = sec.id 
              WHERE l.gate = ? 
              ORDER BY l.id DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $gateValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ?: [];
}

header('Content-Type: application/json');
echo json_encode([
    'gate1' => getGateData($conn, 1),
    'gate2' => getGateData($conn, 2)
]);
