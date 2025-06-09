<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get the date from query parameter, default to today
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid date format. Use YYYY-MM-DD."
        ]);
        exit;
    }

    // Query to fetch delivery details with seller, customer, and price information
    $sql = "SELECT 
                d.Delivery_id,
                d.DateTime,
                d.Quantity,
                s.Name AS Seller_name,
                s.Vehicle_no,
                c.Name AS Customer_name,
                c.Contact AS Customer_contact,
                c.Price AS Customer_price,
                a.Address AS Customer_address
            FROM tbl_milk_delivery d
            JOIN tbl_seller s ON d.Seller_id = s.Seller_id
            JOIN tbl_customer c ON d.Customer_id = c.Customer_id
            JOIN tbl_address a ON c.Address_id = a.Address_id
            WHERE DATE(d.DateTime) = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare statement: " . mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $deliveries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $deliveries[] = [
            "delivery_id" => $row['Delivery_id'],
            "date_time" => $row['DateTime'],
            "quantity" => floatval($row['Quantity']),
            "seller_name" => $row['Seller_name'],
            "vehicle_no" => $row['Vehicle_no'],
            "customer_name" => $row['Customer_name'],
            "customer_contact" => $row['Customer_contact'],
            "customer_price" => floatval($row['Customer_price']),
            "customer_address" => $row['Customer_address']
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $deliveries
    ]);

    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>