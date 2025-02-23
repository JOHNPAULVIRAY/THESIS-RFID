// Function to fetch counts (inside count, total logins, total students)
function fetchCounts() {
    $.ajax({
        url: '../config/fetch_count.php',  // Endpoint to get count data
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Update the counters in HTML with data from the response
            $('#inside-count').text(data.inside);
            $('#tlog-count').text(data.total_logins);
            $('#tstudents-count').text(data.total_students);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching data:", error);
        }
    });
}

// Fetch data every 5 seconds
setInterval(fetchCounts, 5000);

// Initial call to populate data on page load
fetchCounts();

// Function to update the current date and time
function updateDateTime() {
    var currentTime = new Date();
    var hours = currentTime.getHours().toString().padStart(2, '0');
    var minutes = currentTime.getMinutes().toString().padStart(2, '0');
    var seconds = currentTime.getSeconds().toString().padStart(2, '0');
    
    // Get current day and date information
    var daysOfWeek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    var day = daysOfWeek[currentTime.getDay()];
    var month = months[currentTime.getMonth()];
    var date = currentTime.getDate();
    var year = currentTime.getFullYear();
    
    // Update date and time in the page
    document.getElementById("date").textContent = day + ", " + month + " " + date + ", " + year;
    document.getElementById("time").textContent = hours + ":" + minutes + ":" + seconds;
}

// Update the time and date every second
setInterval(updateDateTime, 1000);
