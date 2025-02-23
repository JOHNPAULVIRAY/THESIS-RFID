<?php
session_start();
include('../config/functions.php');

$conn = dbConnect();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Function to get the latest user data for a specific gate
function getGateData($conn, $gate) {
    $gateValue = ($gate == 1) ? 'Gate 1' : 'Gate 2';
    $query = "SELECT s.name, s.lrn, g.name AS grade, sec.name AS section, 
                     l.action, s.photo_path, l.timestamp 
              FROM logs l 
              JOIN students s ON l.student_id = s.id 
              JOIN grades g ON s.grade_id = g.id 
              JOIN sections sec ON s.section_id = sec.id 
              WHERE l.gate = ? 
              ORDER BY l.id DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $gateValue);
    if (!$stmt->execute()) {
        return null;
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();
    return $data ?: null;
}

// Fetch data for Gate 1
$gate1Data = getGateData($conn, 1);

// Ensure data is available to prevent errors in HTML
$photoPath = $gate1Data['photo_path'] ?? '../statics/img/image.png';
$name = $gate1Data['name'] ?? 'Waiting...';
$lrn = $gate1Data['lrn'] ?? '---';
$grade = $gate1Data['grade'] ?? '---';
$section = $gate1Data['section'] ?? '---';
$time = isset($gate1Data['timestamp']) ? date('g:i A', strtotime($gate1Data['timestamp'])) : '---';
$action = $gate1Data['action'] ?? '---';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - RFID Tracking</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../statics/css/gate.css">
</head>

<body>
    <div class="top-nav">
        <div class="logo">
            <img src="../statics/img/logo.png" class="psu-logo" height="65px">
        </div>
        <div class="name">
            <h1>RFID-Based Student Tracking System</h1>
            <h3>Bugallon Integrated School</h3>
        </div>
    </div>

    <div class="container-container">
        <div class="details" id="dashboard">
            <section class="table__header">
                <h1>Live Monitoring (GATE 1)</h1>
                <div id="datetime">
                    <div id="date-time"></div>
                </div>
            </section>
            <section class="gateinterface">
                <div class="gateinfo">
                    <div class="gatephoto">
                        <img id="student-photo" src="<?= $photoPath; ?>" alt="Gate 1 Photo">
                    </div>
                    <div class="gatedata">
                        <h1 id="student-name"><?= htmlspecialchars($name); ?></h1>
                        <h2 id="student-lrn"><?= htmlspecialchars($lrn); ?></h2>
                        <h2 id="student-grade"><?= htmlspecialchars($grade); ?></h2>
                        <h2 id="student-section"><?= htmlspecialchars($section); ?></h2>
                        <h2 id="student-time"><?= htmlspecialchars($time); ?></h2>
                        <h1 id="student-action"><?= htmlspecialchars($action); ?></h1>
                    </div>
                </div>
                <div class="gatelive">
                    <div class="live-box" id="inside">
                        <h1 id="inside-count">0</h1>
                        <h4>Students Inside</h4>
                    </div>
                    <div class="live-box" id="tlog">
                        <h1 id="tlog-count">0</h1>
                        <h4>Total Login</h4>
                    </div>
                    <div class="live-box" id="tstudents">
                        <h1 id="tstudents-count">0</h1>
                        <h4>Total Students</h4>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../statics/js/admin.js"></script>
    <script>
        function fetchLatestEntry() {
            $.ajax({
                url: 'rfid_scan_gate.php',
                type: 'POST',
                data: { gate: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.status === "success") {
                        let student = response.student;
                        $("#student-photo").attr("src", student.photo_path);
                        $("#student-name").text(student.name);
                        $("#student-lrn").text(student.lrn);
                        $("#student-grade").text(student.grade);
                        $("#student-section").text(student.section);
                        $("#student-time").text(student.timestamp);
                        $("#student-action").text(student.action);
                    } else {
                        console.warn("No new data available.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                    console.log(xhr.responseText);
                }
            });
        }

        setInterval(fetchLatestEntry, 1000);
        fetchLatestEntry();


        function fetchCounts() {
            $.ajax({
                url: '../config/fetch_count.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#inside-count').text(data.inside);
                    $('#tlog-count').text(data.total_logins);
                    $('#tstudents-count').text(data.total_students);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching counts:", error);
                }
            });
        }

        setInterval(fetchLatestEntry, 1000);
        setInterval(fetchCounts, 1000);
        fetchCounts();
    </script>

    <?php $conn->close(); ?>
</body>
</html>
