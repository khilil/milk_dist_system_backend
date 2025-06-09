<?php
// Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

switch ($method) {
    case 'GET':
        if ($path === 'customers') {
            // Fetch all customers with address
            $sql = "SELECT c.Customer_id, c.Name, c.Contact, c.Price, c.Date, a.Address 
                    FROM tbl_customer c 
                    LEFT JOIN tbl_address a ON c.Address_id = a.Address_id";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $customers = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $customers[] = $row;
                }
                echo json_encode([
                    "status" => "success",
                    "data" => $customers
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch customers: " . mysqli_error($conn)
                ]);
            }
            mysqli_free_result($result);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid endpoint"
            ]);
        }
        break;

    case 'DELETE':
        if ($path === 'customers') {
            // Delete customer
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['Customer_id']) || !is_numeric($data['Customer_id'])) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid or missing Customer_id"
                ]);
                exit;
            }

            $id = intval($data['Customer_id']);
            $sql = "DELETE FROM tbl_customer WHERE Customer_id = ?";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Customer deleted successfully"
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Failed to delete customer: " . mysqli_stmt_error($stmt)
                    ]);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to prepare statement: " . mysqli_error($conn)
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid endpoint"
            ]);
        }
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid Request Method"
        ]);
        break;
}

// Close database connection
mysqli_close($conn);
?>