<?php
include('functions.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM students WHERE id = '$id'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        echo "Student not found!";
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['student_name'];
    $grade = $_POST['grade'];
    $section = $_POST['section_name'];
    $rfid = $_POST['rfid'];

    $update_query = "UPDATE students SET student_name='$name', grade='$grade', section_name='$section', rfid='$rfid' WHERE id='$id'";

    if ($conn->query($update_query) === TRUE) {
        echo "Student updated successfully!";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
<form method="POST">
    Name: <input type="text" name="student_name" value="<?php echo $student['student_name']; ?>"><br>
    Grade: <input type="text" name="grade" value="<?php echo $student['grade']; ?>"><br>
    Section: <input type="text" name="section_name" value="<?php echo $student['section_name']; ?>"><br>
    RFID Tag: <input type="text" name="rfid" value="<?php echo $student['rfid']; ?>"><br>
    <input type="submit" value="Update">
</form>
