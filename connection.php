<?php
// Database configuration
$host = 'mysql.railway.internal'; // Docker compose માં db service name
$username = 'root';    
$password = 'JjNPWxcgEQWDFTKWsBLVVpRrIeeYgYrK';   
$database = 'railway';   

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
