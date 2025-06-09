<?php
header("Content-Type: application/json");
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

try {
    switch ($method) {
        case 'GET':
            if ($path === 'areas') {
                $query = "SELECT Address_id, Address AS Area_name FROM tbl_address";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();
                $areas = [];
                while ($row = $result->fetch_assoc()) {
                    $areas[] = [
                        'Address_id' => (int)$row['Address_id'],
                        'Area_name' => $row['Area_name']
                    ];
                }
                echo json_encode(["status" => "success", "data" => $areas]);
                $stmt->close();
            } elseif ($path === 'customers_by_area') {
                $address_ids = isset($_GET['address_ids']) ? json_decode($_GET['address_ids'], true) : [];
                if (empty($address_ids)) {
                    echo json_encode(["status" => "error", "message" => "No address IDs provided"]);
                    exit;
                }
                $address_ids = array_map('intval', $address_ids);
                $placeholders = implode(',', array_fill(0, count($address_ids), '?'));
                $query = "SELECT c.Customer_id, c.Name, c.Contact, c.Price, a.Address
                          FROM tbl_customer c
                          LEFT JOIN tbl_address a ON c.Address_id = a.Address_id
                          WHERE c.Address_id IN ($placeholders)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param(str_repeat('i', count($address_ids)), ...$address_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                $customers = [];
                while ($row = $result->fetch_assoc()) {
                    $customers[] = $row;
                }
                echo json_encode(["status" => "success", "data" => $customers]);
                $stmt->close();
            } elseif ($path === 'payment_history') {
                $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
                if (!$customer_id) {
                    echo json_encode(["status" => "error", "message" => "Customer ID required"]);
                    exit;
                }
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_customer WHERE Customer_id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                $stmt->close();
                if ($count == 0) {
                    echo json_encode(["status" => "error", "message" => "Customer not found"]);
                    exit;
                }
                $query = "SELECT S_payment_id, Amount_collected, Payment_status, Payment_date, Method
                          FROM seller_payment
                          WHERE Customer_id = ?
                          ORDER BY Payment_date DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $payments = [];
                while ($row = $result->fetch_assoc()) {
                    $payments[] = $row;
                }
                echo json_encode(["status" => "success", "data" => $payments]);
                $stmt->close();
            }
            break;

        case 'POST':
            if ($path === 'record_payment') {
                $data = json_decode(file_get_contents("php://input"), true);
                $customer_id = isset($data['customer_id']) ? intval($data['customer_id']) : 0;
                $amount_collected = isset($data['amount_collected']) ? floatval($data['amount_collected']) : 0;
                $payment_status = isset($data['payment_status']) ? trim($data['payment_status']) : 'Pending';
                $payment_date = isset($data['payment_date']) ? trim($data['payment_date']) : date('Y-m-d');
                $method = isset($data['method']) ? trim($data['method']) : '';

                if (!$customer_id || $amount_collected <= 0 || !$payment_date) {
                    echo json_encode(["status" => "error", "message" => "Invalid input data"]);
                    exit;
                }
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_customer WHERE Customer_id = ?");
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                $stmt->close();
                if ($count == 0) {
                    echo json_encode(["status" => "error", "message" => "Customer not found"]);
                    exit;
                }
                $valid_statuses = ['Pending', 'Paid'];
                if (!in_array($payment_status, $valid_statuses)) {
                    echo json_encode(["status" => "error", "message" => "Invalid payment status"]);
                    exit;
                }
                $query = "INSERT INTO seller_payment (Customer_id, Date, Amount_collected, Payment_status, Payment_date, Method)
                          VALUES (?, CURDATE(), ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("idsss", $customer_id, $amount_collected, $payment_status, $payment_date, $method);
                if ($stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Payment recorded successfully"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to record payment"]);
                }
                $stmt->close();
            }
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
mysqli_close($conn);
?>