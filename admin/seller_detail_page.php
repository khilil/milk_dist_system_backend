<?php
header("Content-Type: application/json");
include '../connection.php'; // Adjust path if connection.php is elsewhere

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Log request details for debugging
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . print_r($_GET, true));

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

error_log("Parsed Path: " . $path);

switch ($path) {
    case 'seller_deliveries':
        if ($method === 'GET') {
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

            error_log("Seller ID: $seller_id, Date: $date");

            if ($seller_id === 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Seller ID is required"
                ]);
                exit;
            }

            // Validate date format (YYYY-MM-DD)
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }

            try {
                // Fetch daily delivery records for the seller
                $sql = "SELECT d.DateTime as date, c.Name as customer_name, a.Address as address, d.Quantity as quantity
                        FROM tbl_milk_delivery d
                        LEFT JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                        LEFT JOIN tbl_address a ON c.Address_id = a.Address_id
                        WHERE d.Seller_id = ? AND DATE(d.DateTime) = ?
                        ORDER BY d.DateTime DESC";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $daily_records = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $daily_records[] = [
                        'date' => $row['date'],
                        'customer_name' => $row['customer_name'] ?? 'Unknown',
                        'address' => $row['address'] ?? 'N/A',
                        'quantity' => (float)$row['quantity']
                    ];
                }
                mysqli_stmt_close($stmt);

                // Fetch total quantity for the day
                $sql_total = "SELECT COALESCE(SUM(Quantity), 0) as total_quantity
                              FROM tbl_milk_delivery
                              WHERE Seller_id = ? AND DATE(DateTime) = ?";
                $stmt_total = mysqli_prepare($conn, $sql_total);
                mysqli_stmt_bind_param($stmt_total, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt_total);
                $result_total = mysqli_stmt_get_result($stmt_total);
                $total_quantity = (float)mysqli_fetch_assoc($result_total)['total_quantity'];
                mysqli_stmt_close($stmt_total);

                echo json_encode([
                    "status" => "success",
                    "message" => "Daily deliveries fetched successfully",
                    "data" => $daily_records,
                    "total_quantity" => $total_quantity
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch deliveries: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid endpoint",
            "received_path" => $path
        ]);
        break;
}

mysqli_close($conn);
?>