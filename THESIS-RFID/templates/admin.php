<?php
include('../config/functions.php');

// Establish the database connection
$conn = dbConnect();


// ------------------------------------------------------------------------------------------------------------------
// Function to get the latest user data for a gate
function getGateData($conn, $gate) {
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

    $stmt->bind_param("s", $gate);
    if (!$stmt->execute()) {
        return null;
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();
    return $data ?: null;
}

// Fetch data for both gates
$gate1Data = getGateData($conn, 'Gate 1');
$gate2Data = getGateData($conn, 'Gate 2');

// Ensure data is available to prevent errors in HTML
$defaultPhoto = '../statics/img/image.png';

// Gate 1
$photoPath1 = $gate1Data['photo_path'] ?? $defaultPhoto;
$name1 = $gate1Data['name'] ?? 'Waiting...';
$lrn1 = $gate1Data['lrn'] ?? '---';
$grade1 = $gate1Data['grade'] ?? '---';
$section1 = $gate1Data['section'] ?? '---';
$time1 = isset($gate1Data['timestamp']) ? date('g:i A', strtotime($gate1Data['timestamp'])) : '---';
$action1 = $gate1Data['action'] ?? '---';

// Gate 2
$photoPath2 = $gate2Data['photo_path'] ?? $defaultPhoto;
$name2 = $gate2Data['name'] ?? 'Waiting...';
$lrn2 = $gate2Data['lrn'] ?? '---';
$grade2 = $gate2Data['grade'] ?? '---';
$section2 = $gate2Data['section'] ?? '---';
$time2 = isset($gate2Data['timestamp']) ? date('g:i A', strtotime($gate2Data['timestamp'])) : '---';
$action2 = $gate2Data['action'] ?? '---';
// ------------------------------------------------------------------------------------------------------------------
// SQL query to fetch logs related to RFID scan
$sql_logs = "SELECT s.lrn, s.name, l.timestamp, l.gate, l.action
             FROM logs l
             JOIN students s ON l.student_id = s.id
             ORDER BY l.timestamp DESC"; 
$result_log = $conn->query($sql_logs);

// SQL query to fetch grade and section data
$sql_grades = "SELECT g.name AS grade, s.name AS section_name 
               FROM sections s
               JOIN grades g ON s.grade_id = g.id
               ORDER BY g.name ASC"; 
$result_grades = $conn->query($sql_grades);

$sql_students = "
    SELECT 
        s.lrn,
        s.name AS student_name,
        g.name AS grade,
        sec.name AS section_name,
        s.rfid
    FROM students s
    JOIN grades g ON s.grade_id = g.id
    JOIN sections sec ON s.section_id = sec.id
    ORDER BY g.name ASC, sec.name ASC
";
$result_students = $conn->query($sql_students);

$sql_logs = "
    SELECT 
        s.lrn,
        s.name AS student_name,
        g.name AS grade,
        sec.name AS section_name,
        l.timestamp,
        l.gate,
        l.action
    FROM logs l
    JOIN students s ON l.student_id = s.id
    JOIN grades g ON s.grade_id = g.id
    JOIN sections sec ON s.section_id = sec.id
    ORDER BY l.timestamp DESC
";
$result_logs = $conn->query($sql_logs);

$sql_users = "
    SELECT 
        u.name,
        u.username,
        u.password,
        u.role,
        s.lrn AS student_id
    FROM users u
    LEFT JOIN students s ON u.linked_student_id = s.id
";
$result_users = $conn->query($sql_users);

// Check if the logout button is clicked
if (isset($_POST['logout'])) {
    logoutUser();
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID-Based Student Tracking System</title>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../statics/css/styles.css">
    <link rel="stylesheet" href="../statics/css/table.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.18/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.3/xlsx.full.min.js"></script>

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
    <div class="container">
        <div class="left-nav">
            <div class="profile">
                <img src="../statics/img/bg.jpg" class="profile-image">
                <h2>Admin</h2>
            </div>
            <div class="navs">
                <a href="#dashboard">
                    <div class="dashboard-nav nav">
                        <i class='bx bxs-dashboard icon'></i>
                        <h6>Dashboard</h6>
                    </div>
                </a>
                <a href="#sections">
                    <div class="sections-nav nav">
                        <i class='bx bxs-time icon' ></i>
                        <h6>Sections</h6>
                    </div>
                </a>
                <a href="#students">
                    <div class="students-nav nav">
                        <i class='bx bxs-square-rounded icon'></i>
                        <h6>Students</h6>
                    </div>
                </a>
                <a href="#history">
                    <div class="history-nav nav">
                        <i class='bx bxs-square-rounded icon'></i>
                        <h6>Log History</h6>
                    </div>
                </a>
                <a href="#monitoring">
                    <div class="monitoring-nav nav">
                        <i class='bx bxs-square-rounded icon'></i>
                        <h6>Monitoring</h6>
                    </div>
                </a>
                <a href="#users">
                    <div class="users-nav nav">
                        <i class='bx bxs-square-rounded icon'></i>
                        <h6>Users</h6>
                    </div>
                </a>
            </div>
            <div class="logout">
                 <form action="" method="post">
                    <button type="submit" name="logout" class="input-logout">
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </div>
        <div class="container-container">
            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="dashboard">
                <section class="table__header">
                    <h1>Dashboard</h1>
                    <div class="input-group">
                        <input type="search" placeholder="Search Data...">
                        <img src="../statics/img/search.png" alt="search">
                    </div>
                </section>
                <section class="live">
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
                </section>
                <section class="table__body-dashboard">
                    <table>
                        <thead>
                            <tr>
                                <th> LRN <span class="icon-arrow">&UpArrow;</span></th>
                                <th> NAME <span class="icon-arrow">&UpArrow;</span></th>
                                <th> TIMESTAMP <span class="icon-arrow">&UpArrow;</span></th>
                                <th> GATE <span class="icon-arrow">&UpArrow;</span></th>
                                <th> ACTION <span class="icon-arrow">&UpArrow;</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Check if any records were returned
                                if ($result_log->num_rows > 0) {
                                    // Output data of each row
                                    while($row = $result_log->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row["lrn"] . "</td>";
                                        echo "<td>" . $row["name"] . "</td>";
                                        echo "<td>" . date("F j, Y g:i A", strtotime($row["timestamp"])) . "</td>";
                                        echo "<td>" . $row["gate"] . "</td>";
                                        echo "<td>" . $row["action"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No data found</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="sections">
                <section class="table__header">
                    <h1>Sections</h1>
                    <div class="input-group">
                        <input type="search" placeholder="Search Data...">
                        <img src="../statics/img/search.png" alt="search">
                    </div>
                </section>
                <section class="table__body-dashboard">
                    <table>
                        <thead>
                            <tr>
                                <th> GRADE LEVEL <span class="icon-arrow">&UpArrow;</span></th>
                                <th> SECTION <span class="icon-arrow">&UpArrow;</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Check if any records were returned
                                if ($result_grades->num_rows > 0) {
                                    // Output data of each row
                                    while($row = $result_grades->fetch_assoc()) {
                                        $formatted_grade = "" . $row["grade"]; 
                                        echo "<tr>";
                                        echo "<td>" . $formatted_grade . "</td>";
                                        echo "<td>" . $row["section_name"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2'>No data found</td></tr>";
                                }
                            ?>    
                        </tbody>
                    </table>
                </section>
            </div>
            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="students">
                <section class="table__header">
                    <h1>Students List</h1>
                    <div class="input-group">
                        <input type="search" placeholder="Search Data...">
                        <img src="../statics/img/search.png" alt="search">
                    </div>
                </section>
                <section class="table__body-dashboard">
                    <table>
                        <thead>
                            <tr>
                                <th> LRN <span class="icon-arrow">&UpArrow;</span></th>
                                <th> NAME <span class="icon-arrow">&UpArrow;</span></th>
                                <th> GRADE <span class="icon-arrow">&UpArrow;</span></th>
                                <th> SECTION <span class="icon-arrow">&UpArrow;</span></th>
                                <th> RFID TAG <span class="icon-arrow">&UpArrow;</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Check if any records were returned
                                if ($result_students->num_rows > 0) {
                                    // Output data of each row
                                    while ($row = $result_students->fetch_assoc()) {
                                        // Format grade as "GRADE X"
                                        $formatted_grade = "" . $row["grade"];
                                        echo "<tr>";
                                        echo "<td>" . $row["lrn"] . "</td>";
                                        echo "<td>" . $row["student_name"] . "</td>";
                                        echo "<td>" . $formatted_grade . "</td>";
                                        echo "<td>" . $row["section_name"] . "</td>";
                                        echo "<td>" . $row["rfid"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No data found</td></tr>";
                                }
                            ?>   
                        </tbody>
                    </table>
                </section>
            </div>

            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="monitoring">
                <section class="table__header">
                    <h1>Live Monitoring</h1>
                    <div id="datetime">
                        <div id="date-time">
                            
                        </div>
                    </div>
                </section>
                <section class="live">
                    <div class="live-box" id="inside">
                        <h1 id="inside-count-1">0</h1>
                        <h4>Students Inside</h4>
                    </div>
                    <div class="live-box" id="tlog">
                        <h1 id="tlog-count-1">0</h1>
                        <h4>Total Login</h4>
                    </div>
                    <div class="live-box" id="tstudents">
                        <h1 id="tstudents-count-1">0</h1>
                        <h4>Total Students</h4>
                    </div>
                </section>
                <section class="admininterface">
                    <div class="gatetitle">
                        <div><h1>GATE 1</h1></div>
                        <div><h1>GATE 2<h1></div>
                    </div>
                    <div class="gatetitleinfo">
                        <!-- Gate 1 Form -->
                        <div class="gateone">
                            <section class="gateinterface">
                                <div class="gateinfo">
                                    <div class="gatephoto">
                                        <img id="student-photo-1" src="<?= $photoPath1; ?>" alt="Gate 1 Photo">
                                    </div>
                                    <div class="gatedata">
                                        <h1 id="student-name-1"><?= htmlspecialchars($name1); ?></h1>
                                        <h2 id="student-lrn-1"><?= htmlspecialchars($lrn1); ?></h2>
                                        <h2 id="student-grade-1"><?= htmlspecialchars($grade1); ?></h2>
                                        <h2 id="student-section-1"><?= htmlspecialchars($section1); ?></h2>
                                        <h2 id="student-time-1"><?= htmlspecialchars($time1); ?></h2>
                                        <h1 id="student-action-1"><?= htmlspecialchars($action1); ?></h1>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- Gate 2 Form -->
                        <div class="gatetwo">
                            <section class="gateinterface">
                                <div class="gateinfo">
                                    <div class="gatephoto">
                                        <img id="student-photo-2" src="<?= $photoPath2; ?>" alt="Gate 2 Photo">
                                    </div>
                                    <div class="gatedata">
                                        <h1 id="student-name-2"><?= htmlspecialchars($name2); ?></h1>
                                        <h2 id="student-lrn-2"><?= htmlspecialchars($lrn2); ?></h2>
                                        <h2 id="student-grade-2"><?= htmlspecialchars($grade2); ?></h2>
                                        <h2 id="student-section-2"><?= htmlspecialchars($section2); ?></h2>
                                        <h2 id="student-time-2"><?= htmlspecialchars($time2); ?></h2>
                                        <h1 id="student-action-2"><?= htmlspecialchars($action2); ?></h1>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                </section>
            </div>
            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="history">
                <section class="table__header">
                    <h1>Log History</h1>
                    <div class="input-group">
                        <input type="search" placeholder="Search Data...">
                        <img src="../statics/img/search.png" alt="search">
                    </div>
                    <div class="export__file">
                        <label for="export-file" class="export__file-btn" title="Export File"></label>
                        <input type="checkbox" id="export-file">
                        <div class="export__file-options">
                            <label>Export As &nbsp; &#10140;</label>
                            <label id="toPDF-history">PDF <img src="../statics/img/pdf.png" alt=""></label>
                            <label id="toEXCEL-history">EXCEL <img src="../statics/img/excel.png" alt=""></label>
                        </div>
                    </div>
                </section>
                <section class="table__body">
                    <table>
                        <thead>
                            <tr>
                                <th> LRN <span class="icon-arrow">&UpArrow;</span></th>
                                <th> NAME <span class="icon-arrow">&UpArrow;</span></th>
                                <th> GRADE <span class="icon-arrow">&UpArrow;</span></th>
                                <th> SECTION <span class="icon-arrow">&UpArrow;</span></th>
                                <th> TIMESTAMP <span class="icon-arrow">&UpArrow;</span></th>
                                <th> GATE <span class="icon-arrow">&UpArrow;</span></th>
                                <th> ACTION <span class="icon-arrow">&UpArrow;</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Check if any records were returned
                                if ($result_logs->num_rows > 0) {
                                    // Output data of each row
                                    while ($row = $result_logs->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row["lrn"] . "</td>";
                                        echo "<td>" . $row["student_name"] . "</td>";
                                        echo "<td>" . "" . $row["grade"] . "</td>";
                                        echo "<td>" . $row["section_name"] . "</td>";
                                        echo "<td>" . date("F j, Y g:i A", strtotime($row["timestamp"])) . "</td>";
                                        echo "<td>" . $row["gate"] . "</td>";
                                        echo "<td>" . $row["action"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>No log data found</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
            <!------------------------------------------------------------------------------------------------------------------>
            <div class="details" id="users">
                <section class="table__header">
                    <h1>Users</h1>
                    <div class="input-group">
                        <input type="search" placeholder="Search Data...">
                        <img src="../statics/img/search.png" alt="search">
                    </div>
                </section>
                <section class="table__body-dashboard">
                    <table>
                        <thead>
                            <tr>
                                <th> NAME <span class="icon-arrow">&UpArrow;</span></th>
                                <th> USERNAME <span class="icon-arrow">&UpArrow;</span></th>
                                <th> PASSWORD <span class="icon-arrow">&UpArrow;</span></th>
                                <th> ROLE <span class="icon-arrow">&UpArrow;</span></th>
                                <th> STUDENT ID <span class="icon-arrow">&UpArrow;</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Check if any records were returned
                                if ($result_users->num_rows > 0) {
                                    // Output data of each row
                                    while ($row = $result_users->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row["name"] . "</td>";
                                        echo "<td>" . $row["username"] . "</td>";
                                        echo "<td>" . $row["password"] . "</td>";
                                        echo "<td>" . $row["role"] . "</td>";
                                        echo "<td>" . $row["student_id"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No user data found</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../statics/js/scriptadmin.js"></script>
    <script>
       function fetchLatestEntries() {
            $.ajax({
                url: 'rfid_scan_admin.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === "success") {
                        let student1 = response.gate1;
                        let student2 = response.gate2;

                        if (student1) {
                            $("#student-photo-1").attr("src", student1.photo_path + "?" + new Date().getTime()); // Prevent caching
                            $("#student-name-1").text(student1.name);
                            $("#student-lrn-1").text(student1.lrn);
                            $("#student-grade-1").text(student1.grade);
                            $("#student-section-1").text(student1.section);
                            $("#student-time-1").text(student1.timestamp);
                            $("#student-action-1").text(student1.action);
                        }

                        if (student2) {
                            $("#student-photo-2").attr("src", student2.photo_path + "?" + new Date().getTime());
                            $("#student-name-2").text(student2.name);
                            $("#student-lrn-2").text(student2.lrn);
                            $("#student-grade-2").text(student2.grade);
                            $("#student-section-2").text(student2.section);
                            $("#student-time-2").text(student2.timestamp);
                            $("#student-action-2").text(student2.action);
                        }
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

        // Update every 1 second (real-time refresh)
        setInterval(fetchLatestEntries, 1000);
        fetchLatestEntries();

        
        function fetchCounts() {
            $.ajax({
                url: '../config/fetch_count.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log("Fetched Data:", data); // Debugging: Check response

                    // Ensure values are numbers before updating UI
                    $('#inside-count, #inside-count-1').text(Number(data.inside));
                    $('#tlog-count, #tlog-count-1').text(Number(data.total_logins));
                    $('#tstudents-count, #tstudents-count-1').text(Number(data.total_students));
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    console.log("Response Text:", xhr.responseText);
                }
            });
        }

        fetchCounts();
        setInterval(fetchCounts, 1000);
    </script>
    <?php
    // Close connection
    $conn->close();
    ?>
</body>
</html>
