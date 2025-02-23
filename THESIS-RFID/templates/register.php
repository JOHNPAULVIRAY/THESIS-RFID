<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbrfidsample";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = trim($_POST['name']);
$parent_name = trim($_POST['parent_name']);
$grade_id = $_POST['grade'];
$section_id = $_POST['section'];
$rfid = trim($_POST['rfid']);
$lrn = trim($_POST['lrn']); 

// Check if LRN or RFID is empty
if (empty($lrn) || empty($rfid)) {
    die("Error: LRN and RFID cannot be empty!");
}

// Check if RFID already exists
$checkRFID = $conn->query("SELECT id FROM students WHERE rfid='$rfid'");
if ($checkRFID->num_rows > 0) {
    die("Error: RFID already registered!");
}

// Check if LRN already exists
$checkLRN = $conn->query("SELECT id FROM students WHERE lrn='$lrn'");
if ($checkLRN->num_rows > 0) {
    die("Error: LRN already registered!");
}

// Insert student record
$sql = "INSERT INTO students (name, grade_id, section_id, rfid, lrn) 
        VALUES ('$name', '$grade_id', '$section_id', '$rfid', '$lrn')";

if ($conn->query($sql) === TRUE) {
    $student_id = $conn->insert_id;

    // Create parent account
    $username = strtolower(str_replace(' ', '', $parent_name));
    $default_password = password_hash("default123", PASSWORD_DEFAULT);

    $sqlParent = "INSERT INTO users (name, username, password, role, linked_student_id)
                  VALUES ('$parent_name', '$username', '$default_password', 'parent', '$student_id')";

    if ($conn->query($sqlParent) === TRUE) {
        echo "Student and parent account created successfully!";
    } else {
        echo "Error creating parent account: " . $conn->error;
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
