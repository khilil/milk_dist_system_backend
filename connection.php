<?php
$host = 'mysql.railway.internal';
$port = 3306;
$user = 'root';
$password = 'utGwxQOdvrghdlUigzPKWPZHXDCsSSHA';
$database = 'railway';

// Load the SQL file
$sqlFile = 'milk_dist_database2.sql';  // make sure this file is in the same folder
$sql = file_get_contents($sqlFile);

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (mysqli_multi_query($conn, $sql)) {
    echo "✅ SQL import successful!";
} else {
    echo "❌ Error importing SQL: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
