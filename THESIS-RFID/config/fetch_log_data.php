<?php
// Include the database connection file
include 'db_connect.php';

// Start the session to access the logged-in parent data
session_start();

// Initialize an empty array to store the log data
$logs = [];

// Check if parent is logged in
if (isset($_SESSION['parent_id'])) {
    $parent_id = $_SESSION['parent_id'];
    
    // Query to get the student IDs associated with this parent
    $student_sql = "SELECT student_id FROM students WHERE parent_id = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an array to store student IDs
    $student_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $student_ids[] = $row['student_id'];
    }
    
    // Now, fetch the log data for the students associated with this parent
    if (!empty($student_ids)) {
        // Prepare the placeholders for the IN clause
        $student_ids_placeholders = implode(",", array_fill(0, count($student_ids), "?"));
        
        // SQL query to fetch the logs based on the student IDs
        $log_sql = "SELECT timestamp, gate, action FROM logs WHERE student_id IN ($student_ids_placeholders) ORDER BY timestamp DESC";
        
        // Prepare the statement with dynamic parameters
        $stmt = $conn->prepare($log_sql);
        $stmt->bind_param(str_repeat('i', count($student_ids)), ...$student_ids);
        $stmt->execute();
        $result_log = $stmt->get_result();
        
        // Fetch the log data and add it to the logs array
        while ($row = $result_log->fetch_assoc()) {
            $logs[] = $row;
        }
    }
}

// Return the log data as JSON
header('Content-Type: application/json');
echo json_encode($logs);

// Close the connection
$conn->close();
?>
