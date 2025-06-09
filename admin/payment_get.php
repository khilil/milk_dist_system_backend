<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include '../connection.php'; // Adjust path to your database connection

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$user_role = isset($_GET['user_role']) ? trim($_GET['user_role']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

try {
    if ($method !== 'GET') {
        throw new Exception("Invalid Request Method");
    }

    switch ($path) {
        case 'sellers':
            if ($user_role !== 'admin') {
                throw new Exception("Access denied: Admin role required");
            }
            $query = "SELECT Seller_id, Name, Contact FROM tbl_seller";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $sellers = [];
            while ($row = $result->fetch_assoc()) {
                $sellers[] = [
                    'Seller_id' => (int)$row['Seller_id'],
                    'Name' => $row['Name'],
                    'Contact' => $row['Contact']
                ];
            }
            echo json_encode(["status" => "success", "data" => $sellers]);
            $stmt->close();
            break;

        case 'payments_by_seller':
            if ($user_role !== 'admin') {
                throw new Exception("Access denied: Admin role required");
            }
            $seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;
            if (!$seller_id) {
                throw new Exception("Seller ID required");
            }
            $query = "SELECT sp.S_payment_id, sp.Seller_id, s.Name AS Seller_name, s.Contact AS Seller_contact,
                             c.Name AS Customer_name, c.Contact AS Customer_contact, sp.Amount_collected,
                             sp.Payment_status, sp.Payment_date, sp.Method, a.Address
                      FROM seller_payment sp
                      LEFT JOIN tbl_seller s ON sp.Seller_id = s.Seller_id
                      LEFT JOIN tbl_customer c ON sp.Customer_id = c.Customer_id
                      LEFT JOIN tbl_address a ON c.Address_id = a.Address_id
                      WHERE sp.Seller_id = ?
                      ORDER BY sp.Payment_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = [
                    'S_payment_id' => (int)$row['S_payment_id'],
                    'Seller_id' => (int)$row['Seller_id'],
                    'Seller_name' => $row['Seller_name'],
                    'Seller_contact' => $row['Seller_contact'],
                    'Customer_name' => $row['Customer_name'],
                    'Customer_contact' => $row['Customer_contact'],
                    'Amount_collected' => (float)$row['Amount_collected'],
                    'Payment_status' => $row['Payment_status'],
                    'Payment_date' => $row['Payment_date'],
                    'Method' => $row['Method'],
                    'Address' => $row['Address']
                ];
            }
            echo json_encode(["status" => "success", "data" => $payments]);
            $stmt->close();
            break;

        case 'payments_for_seller':
            if ($user_role !== 'seller') {
                throw new Exception("Access denied: Seller role required");
            }
            if (!$user_id) {
                throw new Exception("User ID required");
            }
            $query = "SELECT sp.S_payment_id, sp.Seller_id, s.Name AS Seller_name, s.Contact AS Seller_contact,
                             c.Name AS Customer_name, c.Contact AS Customer_contact, sp.Amount_collected,
                             sp.Payment_status, sp.Payment_date, sp.Method, a.Address
                      FROM seller_payment sp
                      LEFT JOIN tbl_seller s ON sp.Seller_id = s.Seller_id
                      LEFT JOIN tbl_customer c ON sp.Customer_id = c.Customer_id
                      LEFT JOIN tbl_address a ON c.Address_id = a.Address_id
                      WHERE sp.Seller_id = ?
                      ORDER BY sp.Payment_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = [
                    'S_payment_id' => (int)$row['S_payment_id'],
                    'Seller_id' => (int)$row['Seller_id'],
                    'Seller_name' => $row['Seller_name'],
                    'Seller_contact' => $row['Seller_contact'],
                    'Customer_name' => $row['Customer_name'],
                    'Customer_contact' => $row['Customer_contact'],
                    'Amount_collected' => (float)$row['Amount_collected'],
                    'Payment_status' => $row['Payment_status'],
                    'Payment_date' => $row['Payment_date'],
                    'Method' => $row['Method'],
                    'Address' => $row['Address']
                ];
            }
            echo json_encode(["status" => "success", "data" => $payments]);
            $stmt->close();
            break;

        default:
            throw new Exception("Invalid path");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    mysqli_close($conn);
}
?>