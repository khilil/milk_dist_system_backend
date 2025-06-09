<?php
// Database configuration
$host = "localhost";       // or 127.0.0.1
$username = "root";        // your MySQL username
$password = "";            // your MySQL password
$database = "milk_dist_database2";  // change this to your database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
