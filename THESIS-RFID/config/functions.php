<?php
// Function to connect to the database
function dbConnect() {
    $conn = mysqli_connect("localhost", "root", "", "dbrfid", 3306);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// Handle the login process
function handleLogin($username, $password) {
    // Connect to the database
    $conn = dbConnect();

    // Sanitize the inputs to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query to get the user by username
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    // If user exists, verify the password
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify the password
        if ($password === $user['password']) {
            // Set session variables based on the user role
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to different pages based on the role
            if ($user['role'] == 'admin') {
                header('Location: admin.php');
                exit();
            } elseif ($user['role'] == 'parent') {
                header('Location: parent.php');
                exit();
            } elseif ($user['role'] == 'gate1') {
                header('Location: gate1.php');
                exit();
            } elseif ($user['role'] == 'gate2') {
                header('Location: gate2.php');
                exit();
            }
            
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found with that username!";
    }

    // Close the database connection
    mysqli_close($conn);
}




// Logout function
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_destroy(); // Destroy the session
    header("location: ../templates/login.php"); // Redirect to login page
    exit();
}


?>
