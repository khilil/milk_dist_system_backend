<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

switch ($method) {
    case 'GET':
        if ($path === 'addresses') {
            // Fetch all addresses
            $query = "SELECT Address_id, Address FROM tbl_address";
            $result = mysqli_query($conn, $query);

            if ($result) {
                $addresses = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $addresses[] = $row;
                }
                echo json_encode([
                    "status" => "success",
                    "data" => $addresses
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch addresses: " . mysqli_error($conn)
                ]);
            }
            mysqli_free_result($result);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}

mysqli_close($conn);
?>