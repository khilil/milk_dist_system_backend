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
        }if ($path === 'customers') {
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
        } 
        break;

    case 'POST':
        // CREATE customer
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (
            empty($data['Name']) ||
            empty($data['Contact']) ||
            empty($data['Password']) ||
            empty($data['Address_id']) ||
            empty($data['Price']) ||
            empty($data['Date'])
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Sanitize and validate inputs
        $name = mysqli_real_escape_string($conn, trim($data['Name']));
        $contact = mysqli_real_escape_string($conn, trim($data['Contact']));
        $password = password_hash(trim($data['Password']), PASSWORD_BCRYPT);
        $address_id = intval($data['Address_id']);
        $price = floatval($data['Price']);
        $date = mysqli_real_escape_string($conn, trim($data['Date']));

        // Validate phone number (basic check for 10 digits)
        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number"
            ]);
            exit;
        }

        // Validate price
        if ($price <= 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Price must be a positive number"
            ]);
            exit;
        }

        // Validate date format (YYYY-MM-DD)
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid date format"
            ]);
            exit;
        }

        // Check if phone already exists
        $checkQuery = "SELECT Customer_id FROM tbl_customer WHERE Contact = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "s", $contact);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Phone number already exists"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Prepare and execute insert query
        $sql = "INSERT INTO tbl_customer (Name, Contact, Password, Address_id, Price, Date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "sssids", $name, $contact, $password, $address_id, $price, $date);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Customer added successfully",
                "customer_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to add customer: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    case 'PUT':
        // UPDATE customer
        $data = json_decode(file_get_contents("php://input"), true);

        $id = intval($data['Customer_id']);
        $name = mysqli_real_escape_string($conn, trim($data['Name']));
        $contact = mysqli_real_escape_string($conn, trim($data['Contact']));
        $address_id = intval($data['Address_id']);
        $price = floatval($data['Price']);
        $date = mysqli_real_escape_string($conn, trim($data['Date']));

        $sql = "UPDATE tbl_customer SET 
                Name=?, Contact=?, Address_id=?, Price=?, Date=? 
                WHERE Customer_id=?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssidsi", $name, $contact, $address_id, $price, $date, $id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Customer updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'DELETE':
        // DELETE customer
        $data = json_decode(file_get_contents("php://input"), true);
        $id = intval($data['Customer_id']);

        $sql = "DELETE FROM tbl_customer WHERE Customer_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Customer deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}

mysqli_close($conn);
?>
