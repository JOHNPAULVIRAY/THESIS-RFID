<?php
include('functions.php');

$conn = dbConnect(); // Ensure you have a valid DB connection

// Check if LRN is provided in the URL
if (isset($_GET['lrn'])) {
    $lrn = $_GET['lrn'];
    $lrn = mysqli_real_escape_string($conn, $lrn);

    // Corrected query with proper column names and joins
    $query = "
        SELECT students.id, students.name, grades.name AS grade_name 
        FROM students 
        JOIN grades ON students.grade_id = grades.id
        JOIN sections ON students.section_id = sections.id
        WHERE students.lrn = '$lrn'
    ";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Display student details
        echo "<h2>Student Details</h2>";
        echo "ID: " . htmlspecialchars($student['id']) . "<br>";
        echo "LRN: " . htmlspecialchars($student['lrn']) . "<br>";
        echo "Name: " . htmlspecialchars($student['name']) . "<br>"; 
        echo "Grade: " . htmlspecialchars($student['grade_name']) . "<br>"; 
        echo "Section: " . htmlspecialchars($student['section_name']) . "<br>"; 
        echo "RFID Tag: " . htmlspecialchars($student['rfid']) . "<br>";
    } else {
        echo "Student not found!";
    }
} else {
    echo "Invalid request! LRN is missing.";
}

$conn->close();
?>
