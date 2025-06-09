<?php
// Database configuration
$host = 'db'; // Docker compose માં db service name
$username = 'user';        // 👈 યુઝરનામ define કરો
$password = 'password';    // 👈 પાસવર્ડ define કરો
$database = 'milk_dist';   // 👈 DB નું નામ

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
