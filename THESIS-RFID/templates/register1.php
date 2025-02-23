<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbrfidsample";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch grades for dropdown
$grades = $conn->query("SELECT id, name FROM grades");

// Handle AJAX request for sections
if (isset($_GET['grade_id'])) {
    $grade_id = $_GET['grade_id'];
    $sections = $conn->query("SELECT id, name FROM sections WHERE grade_id='$grade_id'");

    echo "<option value=''>Select Section</option>";
    while ($row = $sections->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['name']}</option>";
    }
    exit();
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lrn = trim($_POST['lrn']);
    $name = trim($_POST['name']);
    $parent_name = trim($_POST['parent_name']);
    $grade_id = $_POST['grade'];
    $section_id = $_POST['section'];
    $rfid = $_POST['rfid'];

    // Check if LRN or RFID already exists
    $checkLRN = $conn->query("SELECT id FROM students WHERE lrn='$lrn'");
    if ($checkLRN->num_rows > 0) {
        die("LRN already registered!");
    }

    $checkRFID = $conn->query("SELECT id FROM students WHERE rfid='$rfid'");
    if ($checkRFID->num_rows > 0) {
        die("RFID already registered!");
    }

    // Default photo path
    $photo_path = "uploads/default-avatar.png";

    // Insert student first
    $sql = "INSERT INTO students (lrn, name, grade_id, section_id, rfid, photo_path) 
            VALUES ('$lrn', '$name', '$grade_id', '$section_id', '$rfid', '$photo_path')";
    
    if ($conn->query($sql) === TRUE) {
        $student_id = $conn->insert_id; // Get last inserted student ID

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = "../statics/uploads/";

            // Ensure the directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                die("Invalid file type! Only JPG and PNG allowed.");
            }

            // Limit file size (2MB max)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                die("File too large! Maximum size is 2MB.");
            }

            // Set filename using LRN
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $photo_name = $lrn . "." . $file_extension;
            $photo_path = $upload_dir . $photo_name;

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $photo_path)) {
                // Save correct path in the database
                $photo_db_path = "statics/uploads/" . $photo_name;
                $updatePhotoSQL = "UPDATE students SET photo_path='$photo_db_path' WHERE id='$student_id'";
                
                if (!$conn->query($updatePhotoSQL)) {
                    die("Database update failed: " . $conn->error);
                }
            } else {
                die("File upload failed. Check file permissions.");
            }
        }

        // Create Parent Account
        $username = strtolower(str_replace(' ', '', $parent_name));
        $default_password = password_hash("default123", PASSWORD_DEFAULT);

        $sqlParent = "INSERT INTO users (name, username, password, role, linked_student_id)
                      VALUES ('$parent_name', '$username', '$default_password', 'parent', '$student_id')";

        if ($conn->query($sqlParent) !== TRUE) {
            echo "<script>alert('Error creating parent account: " . $conn->error . "');</script>";
        } else {
            echo "<script>alert('Student registered successfully!');</script>";
        }
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration with RFID</title>
    <script>
        function openModal() {
            document.getElementById("rfidModal").style.display = "block";
            document.getElementById("modalRfidInput").focus();
        }

        function closeModal() {
            document.getElementById("rfidModal").style.display = "none";
        }

        function fetchSections(gradeId) {
            if (gradeId === "") {
                document.getElementById("section").innerHTML = "<option value=''>Select Section</option>";
                return;
            }
            fetch(`?grade_id=${gradeId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById("section").innerHTML = data;
                })
                .catch(error => console.error("Error fetching sections:", error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            const modalRfidInput = document.getElementById("modalRfidInput");
            const mainRfidInput = document.getElementById("rfid");

            modalRfidInput.addEventListener("input", function() {
                if (modalRfidInput.value.length >= 8) {
                    mainRfidInput.value = modalRfidInput.value;
                    closeModal();
                }
            });
        });
    </script>
    <link rel="stylesheet" href="../statics/css/live.css">
    <link rel="stylesheet" href="../statics/css/table.css">
</head>
<body>

    <div class="details" id="management">
        <section class="table__body">
                    <table>
                        <div class="containers">
                            <h1>STUDENT REGISTRATION</h1>
                            <form id="registerForm" action="register.php" method="POST" enctype="multipart/form-data">
                                <div class="input-box">
                                    <label for="lrn">Student Name</label><br>
                                    <input class="input-field" type="text" name="name" placeholder="Enter student name" required />
                                </div>
                                <div class="input-box">
                                    <label>Parent Name</label><br>
                                    <input class="input-field" type="text" name="parent_name" placeholder="Enter parent name" required />
                                </div>
                                <div class="input-box">
                                        <label for="lrn">Learner Reference Number (LRN)</label><br>
                                        <input class="input-field" type="text" name="lrn" id="lrn" placeholder="Enter Learner Reference Number" required />
                                </div>
                                <div class="column">
                                    <div class="select-box">
                                        <label for="grade">Grade</label><br>
                                        <select name="grade" id="grade" onchange="fetchSections(this.value)">
                                            <option value="">Select Grade</option>
                                            <?php while ($row = $grades->fetch_assoc()): ?>
                                                <option value="<?= $row['id']; ?>"><?= $row['name']; ?></option>
                                            <?php endwhile; ?>
                                        </select><br>
                                        <label for="section">Section</label><br>
                                        <select name="section" id="section" required>
                                            <option value="">Select Section</option>
                                        </select><br>
                                    </div>
                                </div>
                                <div class="column">
                                    <label for="rfid">RFID</label><br>
                                    <input class="input-field-label" type="text" id="rfid" name="rfid">
                                    <button class="scanbutton" type="button" onclick="openModal()">Scan ID</button><br>
                                </div>
    
                                <div class="column">
                                    <label for="photo">Upload Photo</label><br>
                                    <input  type="file" id="photo" name="image" accept="image/*" required hidden>
                                    <button class="scanbutton" type="button" onclick="document.getElementById('photo').click()">Choose File</button>
                                    <span id="file-name">No file chosen</span>
                                </div>
                                                                        
                                <div class="input-box">
                                    <button type="submit" class="input-submit">
                                        <span>Submit</span>
                                    </button>
                                </div>
                            </form>
                        
                        </div>  
                    </table>         
                </section>
    </div> 
</body>
</html>
<?php $conn->close(); ?>
