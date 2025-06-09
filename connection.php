<?php
// Database configuration
$host = 'containers-us-west-12.railway.app'; // Docker compose માં db service name
$username = 'railway';        // 👈 યુઝરનામ define કરો
$password = 'password';    // 👈 પાસવર્ડ define કરો
$database = 'railway';   // 👈 DB નું નામ

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
