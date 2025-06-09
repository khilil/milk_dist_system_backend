<?php
// Database configuration
$host = 'containers-us-west-12.railway.app'; // Docker compose માં db service name
$username = 'railway';    
$password = 'password';   
$database = 'railway';   

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
