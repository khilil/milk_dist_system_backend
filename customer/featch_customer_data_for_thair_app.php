<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 3;

switch ($path) {
    case 'monthly_consumption':
        if ($method === 'GET') {
            $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

            if ($customer_id === 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Customer ID is required"
                ]);
                exit;
            }

            try {
                // Current month
                $sql_current = "SELECT COALESCE(SUM(d.Quantity), 0) as total_quantity, c.Price
                               FROM tbl_milk_delivery d
                               LEFT JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                               WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?";
                $stmt_current = mysqli_prepare($conn, $sql_current);
                mysqli_stmt_bind_param($stmt_current, "iii", $customer_id, $year, $month);
                mysqli_stmt_execute($stmt_current);
                $result_current = mysqli_stmt_get_result($stmt_current);
                $row_current = mysqli_fetch_assoc($result_current);
                $current_quantity = (float)$row_current['total_quantity'];
                $price_per_liter = $row_current['Price'] ? (float)$row_current['Price'] : 64.0;
                mysqli_stmt_close($stmt_current);

                // Check payment status for current month
                $sql_current_payment = "SELECT COUNT(*) as paid_count
                                       FROM seller_payment
                                       WHERE Customer_id = ? AND YEAR(Payment_date) = ? AND MONTH(Payment_date) = ? 
                                       AND Payment_status = 'Paid'";
                $stmt_current_payment = mysqli_prepare($conn, $sql_current_payment);
                mysqli_stmt_bind_param($stmt_current_payment, "iii", $customer_id, $year, $month);
                mysqli_stmt_execute($stmt_current_payment);
                $result_current_payment = mysqli_stmt_get_result($stmt_current_payment);
                $current_payment = mysqli_fetch_assoc($result_current_payment)['paid_count'] > 0;
                mysqli_stmt_close($stmt_current_payment);

                // Previous month
                $prev_month = $month - 1;
                $prev_year = $year;
                if ($prev_month === 0) {
                    $prev_month = 12;
                    $prev_year--;
                }
                $sql_prev = "SELECT COALESCE(SUM(d.Quantity), 0) as total_quantity
                             FROM tbl_milk_delivery d
                             WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?";
                $stmt_prev = mysqli_prepare($conn, $sql_prev);
                mysqli_stmt_bind_param($stmt_prev, "iii", $customer_id, $prev_year, $prev_month);
                mysqli_stmt_execute($stmt_prev);
                $result_prev = mysqli_stmt_get_result($stmt_prev);
                $row_prev = mysqli_fetch_assoc($result_prev);
                $prev_quantity = (float)$row_prev['total_quantity'];
                mysqli_stmt_close($stmt_prev);

                // Check payment status for previous month
                $sql_prev_payment = "SELECT COUNT(*) as paid_count
                                     FROM seller_payment
                                     WHERE Customer_id = ? AND YEAR(Payment_date) = ? AND MONTH(Payment_date) = ? 
                                     AND Payment_status = 'Paid'";
                $stmt_prev_payment = mysqli_prepare($conn, $sql_prev_payment);
                mysqli_stmt_bind_param($stmt_prev_payment, "iii", $customer_id, $prev_year, $prev_month);
                mysqli_stmt_execute($stmt_prev_payment);
                $result_prev_payment = mysqli_stmt_get_result($stmt_prev_payment);
                $prev_payment = mysqli_fetch_assoc($result_prev_payment)['paid_count'] > 0;
                mysqli_stmt_close($stmt_prev_payment);

                // Next month
                $next_month = $month + 1;
                $next_year = $year;
                if ($next_month === 13) {
                    $next_month = 1;
                    $next_year++;
                }
                $sql_next = "SELECT COALESCE(SUM(d.Quantity), 0) as total_quantity
                             FROM tbl_milk_delivery d
                             WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?";
                $stmt_next = mysqli_prepare($conn, $sql_next);
                mysqli_stmt_bind_param($stmt_next, "iii", $customer_id, $next_year, $next_month);
                mysqli_stmt_execute($stmt_next);
                $result_next = mysqli_stmt_get_result($stmt_next);
                $row_next = mysqli_fetch_assoc($result_next);
                $next_quantity = (float)$row_next['total_quantity'];
                mysqli_stmt_close($stmt_next);

                // Check payment status for next month
                $sql_next_payment = "SELECT COUNT(*) as paid_count
                                     FROM seller_payment
                                     WHERE Customer_id = ? AND YEAR(Payment_date) = ? AND MONTH(Payment_date) = ? 
                                     AND Payment_status = 'Paid'";
                $stmt_next_payment = mysqli_prepare($conn, $sql_next_payment);
                mysqli_stmt_bind_param($stmt_next_payment, "iii", $customer_id, $next_year, $next_month);
                mysqli_stmt_execute($stmt_next_payment);
                $result_next_payment = mysqli_stmt_get_result($stmt_next_payment);
                $next_payment = mysqli_fetch_assoc($result_next_payment)['paid_count'] > 0;
                mysqli_stmt_close($stmt_next_payment);

                // Daily records for current month
                $sql_daily = "SELECT DATE(d.DateTime) as date, d.Quantity
                              FROM tbl_milk_delivery d
                              WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?
                              ORDER BY d.DateTime DESC";
                $stmt_daily = mysqli_prepare($conn, $sql_daily);
                mysqli_stmt_bind_param($stmt_daily, "iii", $customer_id, $year, $month);
                mysqli_stmt_execute($stmt_daily);
                $result_daily = mysqli_stmt_get_result($stmt_daily);
                $daily_records = [];
                while ($row = mysqli_fetch_assoc($result_daily)) {
                    $daily_records[] = [
                        'date' => $row['date'],
                        'quantity' => (float)$row['Quantity']
                    ];
                }
                mysqli_stmt_close($stmt_daily);

                // Daily records for previous month
                $sql_prev_daily = "SELECT DATE(d.DateTime) as date, d.Quantity
                                  FROM tbl_milk_delivery d
                                  WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?
                                  ORDER BY d.DateTime DESC";
                $stmt_prev_daily = mysqli_prepare($conn, $sql_prev_daily);
                mysqli_stmt_bind_param($stmt_prev_daily, "iii", $customer_id, $prev_year, $prev_month);
                mysqli_stmt_execute($stmt_prev_daily);
                $result_prev_daily = mysqli_stmt_get_result($stmt_prev_daily);
                $prev_daily_records = [];
                while ($row = mysqli_fetch_assoc($result_prev_daily)) {
                    $prev_daily_records[] = [
                        'date' => $row['date'],
                        'quantity' => (float)$row['Quantity']
                    ];
                }
                mysqli_stmt_close($stmt_prev_daily);

                // Daily records for next month
                $sql_next_daily = "SELECT DATE(d.DateTime) as date, d.Quantity
                                  FROM tbl_milk_delivery d
                                  WHERE d.Customer_id = ? AND YEAR(d.DateTime) = ? AND MONTH(d.DateTime) = ?
                                  ORDER BY d.DateTime DESC";
                $stmt_next_daily = mysqli_prepare($conn, $sql_next_daily);
                mysqli_stmt_bind_param($stmt_next_daily, "iii", $customer_id, $next_year, $next_month);
                mysqli_stmt_execute($stmt_next_daily);
                $result_next_daily = mysqli_stmt_get_result($stmt_next_daily);
                $next_daily_records = [];
                while ($row = mysqli_fetch_assoc($result_next_daily)) {
                    $next_daily_records[] = [
                        'date' => $row['date'],
                        'quantity' => (float)$row['Quantity']
                    ];
                }
                mysqli_stmt_close($stmt_next_daily);

                echo json_encode([
                    "status" => "success",
                    "message" => "Monthly consumption fetched successfully",
                    "data" => [
                        "current_month" => [
                            "year" => $year,
                            "month" => $month,
                            "total_quantity" => $current_quantity,
                            "total_price" => $current_quantity * $price_per_liter,
                            "price_per_liter" => $price_per_liter,
                            "daily_records" => $daily_records,
                            "paid" => $current_payment
                        ],
                        "previous_month" => [
                            "year" => $prev_year,
                            "month" => $prev_month,
                            "total_quantity" => $prev_quantity,
                            "total_price" => $prev_quantity * $price_per_liter,
                            "price_per_liter" => $price_per_liter,
                            "daily_records" => $prev_daily_records,
                            "paid" => $prev_payment
                        ],
                        "next_month" => [
                            "year" => $next_year,
                            "month" => $next_month,
                            "total_quantity" => $next_quantity,
                            "total_price" => $next_quantity * $price_per_liter,
                            "price_per_liter" => $price_per_liter,
                            "daily_records" => $next_daily_records,
                            "paid" => $next_payment
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch monthly consumption: " . $e->getMessage()
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
            "message" => "Invalid endpoint"
        ]);
        break;
}

mysqli_close($conn);
?>