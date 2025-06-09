<?php
header("Content-Type: application/json");
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

switch ($path) {
    case 'customers':
        if ($method === 'GET') {
            // Fetch customers, optionally filtered by address_ids
            $address_ids = isset($_GET['address_ids']) ? trim($_GET['address_ids']) : '';
            
            $sql = "SELECT c.Customer_id, c.Name, c.Contact, a.Address AS Address, c.Price 
                    FROM tbl_customer c 
                    LEFT JOIN tbl_address a ON c.Address_id = a.Address_id";
            $params = [];
            if ($address_ids !== '') {
                // Sanitize address_ids
                $address_id_array = array_map('intval', explode(',', $address_ids));
                if (!empty($address_id_array)) {
                    $placeholders = implode(',', array_fill(0, count($address_id_array), '?'));
                    $sql .= " WHERE c.Address_id IN ($placeholders)";
                    $params = $address_id_array;
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Invalid address_ids format"
                    ]);
                    exit;
                }
            }

            $stmt = mysqli_prepare($conn, $sql);
            if (!empty($params)) {
                // Bind address IDs dynamically
                $types = str_repeat('i', count($params));
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                $customers = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $customers[] = $row;
                }
                echo json_encode([
                    "status" => "success",
                    "message" => "Customers fetched successfully",
                    "data" => $customers
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch customers: " . mysqli_error($conn)
                ]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'delivery':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (
                empty($data['seller_id']) ||
                empty($data['customer_id']) ||
                empty($data['quantity']) ||
                empty($data['date'])
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "All fields are required: seller_id, customer_id, quantity, date"
                ]);
                exit;
            }
            $seller_id = (int)$data['seller_id'];
            $customer_id = (int)$data['customer_id'];
            $quantity = (float)$data['quantity'];
            $date = mysqli_real_escape_string($conn, trim($data['date']));
            if ($quantity <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Quantity must be greater than zero"
                ]);
                exit;
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }
            mysqli_begin_transaction($conn);
            try {
                $checkSeller = mysqli_prepare($conn, "SELECT Seller_id FROM tbl_seller WHERE Seller_id = ?");
                mysqli_stmt_bind_param($checkSeller, "i", $seller_id);
                mysqli_stmt_execute($checkSeller);
                mysqli_stmt_store_result($checkSeller);
                if (mysqli_stmt_num_rows($checkSeller) == 0) {
                    mysqli_stmt_close($checkSeller);
                    throw new Exception("Seller not found");
                }
                mysqli_stmt_close($checkSeller);
                $checkCustomer = mysqli_prepare($conn, "SELECT Customer_id FROM tbl_customer WHERE Customer_id = ?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                mysqli_stmt_execute($checkCustomer);
                mysqli_stmt_store_result($checkCustomer);
                if (mysqli_stmt_num_rows($checkCustomer) == 0) {
                    mysqli_stmt_close($checkCustomer);
                    throw new Exception("Customer not found");
                }
                mysqli_stmt_close($checkCustomer);
                $checkAssignment = mysqli_prepare($conn, "SELECT Assignment_id, Remaining_quantity FROM tbl_milk_assignment WHERE Seller_id = ? AND Date = ?");
                mysqli_stmt_bind_param($checkAssignment, "is", $seller_id, $date);
                mysqli_stmt_execute($checkAssignment);
                $result = mysqli_stmt_get_result($checkAssignment);
                $assignment = mysqli_fetch_assoc($result);
                mysqli_stmt_close($checkAssignment);
                if (!$assignment) {
                    throw new Exception("No milk assignment found for the seller on thecommands specified date");
                }
                if ($assignment['Remaining_quantity'] < $quantity) {
                    throw new Exception("Insufficient remaining quantity. Available: " . $assignment['Remaining_quantity']);
                }
                $insertDelivery = mysqli_prepare($conn, "INSERT INTO tbl_milk_delivery (Seller_id, Customer_id, DateTime, Quantity) VALUES (?, ?, NOW(), ?)");
                mysqli_stmt_bind_param($insertDelivery, "iid", $seller_id, $customer_id, $quantity);
                if (!mysqli_stmt_execute($insertDelivery)) {
                    throw new Exception("Failed to record delivery: " . mysqli_stmt_error($insertDelivery));
                }
                $delivery_id = mysqli_insert_id($conn);
                mysqli_stmt_close($insertDelivery);
                $new_remaining_quantity = $assignment['Remaining_quantity'] - $quantity;
                $updateAssignment = mysqli_prepare($conn, "UPDATE tbl_milk_assignment SET Remaining_quantity = ? WHERE Assignment_id = ?");
                mysqli_stmt_bind_param($updateAssignment, "di", $new_remaining_quantity, $assignment['Assignment_id']);
                if (!mysqli_stmt_execute($updateAssignment)) {
                    throw new Exception("Failed to update remaining quantity: " . mysqli_stmt_error($updateAssignment));
                }
                mysqli_stmt_close($updateAssignment);
                mysqli_commit($conn);
                echo json_encode([
                    "status" => "success",
                    "message" => "Delivery recorded successfully",
                    "data" => [
                        "delivery_id" => $delivery_id,
                        "seller_id" => $seller_id,
                        "customer_id" => $customer_id,
                        "quantity" => $quantity,
                        "date" => $date,
                        "remaining_quantity" => $new_remaining_quantity
                    ]
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Delivery POST Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        } elseif ($method === 'DELETE') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (
                empty($data['seller_id']) ||
                empty($data['customer_id']) ||
                empty($data['quantity']) ||
                empty($data['date'])
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "All fields are required: seller_id, customer_id, quantity, date"
                ]);
                exit;
            }
            $delivery_id = isset($data['delivery_id']) ? (int)$data['delivery_id'] : null;
            $seller_id = (int)$data['seller_id'];
            $customer_id = (int)$data['customer_id'];
            $quantity = (float)$data['quantity'];
            $date = mysqli_real_escape_string($conn, trim($data['date']));
            if ($quantity <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Quantity must be greater than zero"
                ]);
                exit;
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }
            mysqli_begin_transaction($conn);
            try {
                $checkSeller = mysqli_prepare($conn, "SELECT Seller_id FROM tbl_seller WHERE Seller_id = ?");
                mysqli_stmt_bind_param($checkSeller, "i", $seller_id);
                mysqli_stmt_execute($checkSeller);
                mysqli_stmt_store_result($checkSeller);
                if (mysqli_stmt_num_rows($checkSeller) == 0) {
                    mysqli_stmt_close($checkSeller);
                    throw new Exception("Seller not found");
                }
                mysqli_stmt_close($checkSeller);
                $checkCustomer = mysqli_prepare($conn, "SELECT Customer_id FROM tbl_customer WHERE Customer_id = ?");
                mysqli_stmt_bind_param($checkCustomer, "i", $customer_id);
                mysqli_stmt_execute($checkCustomer);
                mysqli_stmt_store_result($checkCustomer);
                if (mysqli_stmt_num_rows($checkCustomer) == 0) {
                    mysqli_stmt_close($checkCustomer);
                    throw new Exception("Customer not found");
                }
                mysqli_stmt_close($checkCustomer);
                $checkAssignment = mysqli_prepare($conn, "SELECT Assignment_id, Remaining_quantity FROM tbl_milk_assignment WHERE Seller_id = ? AND Date = ?");
                mysqli_stmt_bind_param($checkAssignment, "is", $seller_id, $date);
                mysqli_stmt_execute($checkAssignment);
                $result = mysqli_stmt_get_result($checkAssignment);
                $assignment = mysqli_fetch_assoc($result);
                mysqli_stmt_close($checkAssignment);
                if (!$assignment) {
                    throw new Exception("No milk assignment found for the seller on the specified date");
                }
                if ($delivery_id) {
                    $deleteDelivery = mysqli_prepare($conn, "DELETE FROM tbl_milk_delivery WHERE Delivery_id = ?");
                    mysqli_stmt_bind_param($deleteDelivery, "i", $delivery_id);
                } else {
                    $deleteDelivery = mysqli_prepare($conn, "DELETE FROM tbl_milk_delivery WHERE Seller_id = ? AND Customer_id = ? AND DATE(DateTime) = ? AND Quantity = ? LIMIT 1");
                    mysqli_stmt_bind_param($deleteDelivery, "iisd", $seller_id, $customer_id, $date, $quantity);
                }
                if (!mysqli_stmt_execute($deleteDelivery)) {
                    throw new Exception("Failed to delete delivery: " . mysqli_stmt_error($deleteDelivery));
                }
                $affected_rows = mysqli_stmt_affected_rows($deleteDelivery);
                mysqli_stmt_close($deleteDelivery);
                if ($affected_rows === 0) {
                    throw new Exception("No matching delivery found to delete");
                }
                $new_remaining_quantity = $assignment['Remaining_quantity'] + $quantity;
                $updateAssignment = mysqli_prepare($conn, "UPDATE tbl_milk_assignment SET Remaining_quantity = ? WHERE Assignment_id = ?");
                mysqli_stmt_bind_param($updateAssignment, "di", $new_remaining_quantity, $assignment['Assignment_id']);
                if (!mysqli_stmt_execute($updateAssignment)) {
                    throw new Exception("Failed to update remaining quantity: " . mysqli_stmt_error($updateAssignment));
                }
                mysqli_stmt_close($updateAssignment);
                mysqli_commit($conn);
                echo json_encode([
                    "status" => "success",
                    "message" => "Delivery deleted successfully",
                    "data" => [
                        "seller_id" => $seller_id,
                        "customer_id" => $customer_id,
                        "quantity" => $quantity,
                        "date" => $date,
                        "remaining_quantity" => $new_remaining_quantity
                    ]
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Delivery DELETE Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'milk_sold':
        if ($method === 'GET') {
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }
            try {
                $sql = "SELECT COALESCE(SUM(Quantity), 0) as total_quantity
                        FROM tbl_milk_delivery
                        WHERE Seller_id = ? AND DATE(DateTime) = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                echo json_encode([
                    "status" => "success",
                    "message" => "Total milk sold fetched successfully",
                    "data" => [
                        "seller_id" => $seller_id,
                        "date" => $date,
                        "total_quantity" => (float)$row['total_quantity']
                    ]
                ]);
            } catch (Exception $e) {
                error_log("Milk Sold GET Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch total milk sold: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'milk_assignment':
        if ($method === 'GET') {
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }
            try {
                $sql = "SELECT Assigned_quantity, Remaining_quantity
                        FROM tbl_milk_assignment
                        WHERE Seller_id = ? AND Date = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                if ($row) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Milk assignment fetched successfully",
                        "data" => [
                            "seller_id" => $seller_id,
                            "date" => $date,
                            "assigned_quantity" => (float)$row['Assigned_quantity'],
                            "remaining_quantity" => (float)$row['Remaining_quantity']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        "status" => "success",
                        "message" => "No assignment found for the specified date",
                        "data" => [
                            "seller_id" => $seller_id,
                            "date" => $date,
                            "assigned_quantity" => 0,
                            "remaining_quantity" => 0
                        ]
                    ]);
                }
            } catch (Exception $e) {
                error_log("Milk Assignment GET Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch milk assignment: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid request method"
            ]);
        }
        break;

    case 'distribution_details':
        if ($method === 'GET') {
            $seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
            $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : '';
            if ($seller_id <= 0 || empty($date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "seller_id and date are required"
                ]);
                exit;
            }
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid date format. Use YYYY-MM-DD"
                ]);
                exit;
            }
            try {
                $sql = "SELECT d.Delivery_id, d.Seller_id, d.Customer_id, d.Quantity, DATE(d.DateTime) as date, c.Name as customer_name
                        FROM tbl_milk_delivery d
                        JOIN tbl_customer c ON d.Customer_id = c.Customer_id
                        WHERE d.Seller_id = ? AND DATE(d.DateTime) = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $seller_id, $date);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $deliveries = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $deliveries[] = [
                        'delivery_id' => (int)$row['Delivery_id'],
                        'seller_id' => (int)$row['Seller_id'],
                        'customer_id' => (int)$row['Customer_id'],
                        'customer_name' => $row['customer_name'],
                        'quantity' => (float)$row['Quantity'],
                        'date' => $row['date']
                    ];
                }
                mysqli_stmt_close($stmt);
                echo json_encode([
                    "status" => "success",
                    "message" => "Distribution details fetched successfully",
                    "data" => $deliveries
                ]);
            } catch (Exception $e) {
                error_log("Distribution Details GET Error: " . $e->getMessage());
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to fetch distribution details: " . $e->getMessage()
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