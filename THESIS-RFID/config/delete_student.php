<?php
include('functions.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "DELETE FROM students WHERE id='$id'";
    if ($conn->query($query) === TRUE) {
        echo "Student deleted successfully!";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>
<a href="students_list.php">Back to Students List</a>
