<?php
// Database configuration
$host = 'mysql.railway.internal'; // Docker compose માં db service name
$username = 'root';    
$password = 'utGwxQOdvrghdlUigzPKWPZHXDCsSSHA';   
$database = 'railway';   
$port = 3306;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}
?>
