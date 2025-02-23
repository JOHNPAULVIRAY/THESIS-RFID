<?php
include('functions.php');  

$conn = dbConnect();  

// Get number of students currently inside the school
$sql_inside = "
    SELECT COUNT(DISTINCT student_id) AS inside 
    FROM logs AS l1 
    WHERE action = 'IN' 
    AND NOT EXISTS (
        SELECT 1 
        FROM logs AS l2 
        WHERE l2.student_id = l1.student_id 
        AND l2.timestamp > l1.timestamp 
        AND l2.action = 'OUT'
    )
";
$result_inside = $conn->query($sql_inside);
$row_inside = $result_inside->fetch_assoc();
$inside_count = $row_inside['inside'] ?? 0;

// Get total unique logins for the current day (only count first login of the day)
$sql_logins = "
    SELECT COUNT(DISTINCT student_id) AS total_login 
    FROM logs 
    WHERE action = 'IN' 
    AND DATE(timestamp) = CURDATE()
";
$result_logins = $conn->query($sql_logins);
$row_logins = $result_logins->fetch_assoc();
$total_logins = $row_logins['total_login'] ?? 0;

// Get total number of registered students
$sql_students = "SELECT COUNT(*) AS total_students FROM students";
$result_students = $conn->query($sql_students);
$row_students = $result_students->fetch_assoc();
$total_students = $row_students['total_students'] ?? 0;

// Return data as JSON
echo json_encode([
    'inside' => $inside_count,
    'total_logins' => $total_logins,
    'total_students' => $total_students
]);
?>
