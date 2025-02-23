<?php
session_start();
include('../config/functions.php');

// Ensure user is logged in with the correct session key
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = dbConnect();

// Get linked_student_id from users table
$student = [];
$logs = [];

$userQuery = "SELECT linked_student_id FROM users WHERE id = ?";
if ($stmt = $conn->prepare($userQuery)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if ($user && !empty($user['linked_student_id'])) {
    $student_id = $user['linked_student_id'];

    // Fetch student info with grade and section name
    $studentQuery = "SELECT s.name, s.lrn, g.name AS grade, sec.name AS section, s.photo_path
                     FROM students s
                     JOIN grades g ON s.grade_id = g.id
                     JOIN sections sec ON s.section_id = sec.id
                     WHERE s.id = ?";
    if ($stmt = $conn->prepare($studentQuery)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
    }

    // Fetch all logs for this student
    $logQuery = "SELECT l.timestamp, l.gate, l.action, s.name
                FROM logs l
                JOIN students s ON l.student_id = s.id
                WHERE l.student_id = ?
                ORDER BY l.id DESC"; // Removed the LIMIT to get all logs

    $logs = [];

    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $logs[] = $row; // Collect all rows in $logs array
        }

        $stmt->close();
    }
} 
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - RFID Tracking</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../statics/css/parent.css">
</head>

<body>
    <div class="top-nav">
        <img src="../statics/img/logo.png" class="logo" height="60px">
        <div>
            <h2>BUGALLON INTEGRATED SCHOOL</h2>
            <h6>Bugallon, Pangasinan</h6>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($student)): ?>
            <div class="studentinfo">
                <div>
                <img src="<?php echo !empty($student['photo_path']) ? htmlspecialchars($student['photo_path']) : 
                '../statics/uploads/default-avatar.png'; ?>" class="logo" height="120px" alt="Student Photo">
                </div>
                <div>
                    <h1><?php echo htmlspecialchars($student['name']); ?></h1>
                    <h3><?php echo htmlspecialchars($student['lrn']); ?></h3>
                    <h3><?php echo htmlspecialchars($student['grade']); ?></h3>
                    <h3><?php echo htmlspecialchars($student['section']); ?></h3>
                </div>
            </div>
        <?php else: ?>
            <p>No student information available.</p>
        <?php endif; ?>
    </div>

    <div class="container-container">
        <div class="details" id="history">
            <section class="table__header">
                <h1>Log History</h1>
                <div class="input-group">
                    <input type="search" placeholder="Search Data..." id="searchInput">
                    <img src="../statics/img/search.png" alt="search">
                </div>

            </section>

            <section class="table__body">
                <table id="logTable">
                    <thead>
                        <tr>
                            <th>TIMESTAMP</th>
                            <th>STUDENT NAME</th>
                            <th>GATE</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="logTablebody">
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date("F j, Y g:i A", strtotime($log['timestamp'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['gate']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No log records available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
    <script src="../statics/js/scriptparent.js"></script>

    <?php $conn->close(); ?>
</body>
</html>
