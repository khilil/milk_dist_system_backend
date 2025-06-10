<?php
$host = 'mysql.railway.internal';
$port = 3306;
$user = 'root';
$password = 'utGwxQOdvrghdlUigzPKWPZHXDCsSSHA';
$database = 'railway';

// Load the SQL file
$sqlFile = __DIR__ . '/milk_dist_database2.sql'; // make sure this file is in the same folder
$sql = file_get_contents($sqlFile);

$conn = mysqli_connect($host, $user, $password, $database, $port);

// connection.php (improved logic)
$conn = mysqli_connect($host, $user, $password, $database, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// OPTIONAL: Import only once
if (!tableExists($conn, 'tbl_customer')) { // ✅ Change to a table you expect
    $sqlFile = __DIR__ . '/milk_dist_database2.sql';
    $sql = file_get_contents($sqlFile);

    if ($sql === false) {
        die("❌ Could not read the SQL file.");
    }

    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        echo "✅ SQL imported.";
    } else {
        echo "❌ SQL import error: " . mysqli_error($conn);
    }
}


// Function to check if a table exists
function tableExists($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($check) > 0;
}
