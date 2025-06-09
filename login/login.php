<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

if ($path === 'login' && $method === 'POST') {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $contact = isset($input['contact']) ? trim($input['contact']) : null;
    $password = isset($input['password']) ? trim($input['password']) : null;
    $user_type = isset($input['user_type']) ? strtolower(trim($input['user_type'])) : null;

    // Validate input
    if (!$contact || !$password || !$user_type) {
        echo json_encode([
            "status" => "error",
            "message" => "Contact, password, and user_type are required"
        ]);
        exit;
    }

    // Validate user_type
    $valid_user_types = ['customer', 'seller', 'admin'];
    if (!in_array($user_type, $valid_user_types)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid user_type. Must be customer, seller, or admin"
        ]);
        exit;
    }

    try {
        // Determine table and columns based on user_type
        switch ($user_type) {
            case 'customer':
                $table = 'tbl_customer';
                $id_column = 'Customer_id';
                $name_column = 'Name';
                // Address is fetched via join with tbl_address
                break;
            case 'seller':
                $table = 'tbl_seller';
                $id_column = 'Seller_id';
                $name_column = 'Name';
                $address_column = null;
                break;
            case 'admin':
                $table = 'tbl_admin';
                $id_column = 'Admin_id';
                $name_column = null;
                $address_column = null;
                break;
            default:
                throw new Exception("Invalid user type");
        }

        // Prepare SQL query
        if ($user_type === 'customer') {
            // Join with tbl_address for customer
            $sql = "SELECT c.$id_column, c.Password, c.$name_column, a.Address 
                    FROM $table c 
                    LEFT JOIN tbl_address a ON c.Address_id = a.Address_id 
                    WHERE c.Contact = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $contact);
        } else {
            // Original query for seller and admin
            $columns = "$id_column, Password" . ($name_column ? ", $name_column" : "");
            $sql = "SELECT $columns FROM $table WHERE Contact = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $contact);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $stored_password = $row['Password'];
            $user_id = $row[$id_column];
            $user_name = $name_column ? $row[$name_column] : null;
            $user_address = ($user_type === 'customer') ? ($row['Address'] ?? 'N/A') : null;

            // Verify password
            $is_valid_password = false;
            if (password_verify($password, $stored_password)) {
                $is_valid_password = true; // Hashed password
            } elseif ($password === $stored_password && $stored_password !== '') {
                $is_valid_password = true; // Plain text password
            }

            if ($is_valid_password) {
                // Generate a simple token (use JWT in production)
                $token = bin2hex(random_bytes(16));
                $response_data = [
                    "user_id" => $user_id,
                    "user_type" => $user_type,
                    "contact" => $contact,
                    "token" => $token
                ];
                if ($user_name) {
                    $response_data["username"] = $user_name;
                }
                if ($user_address) {
                    $response_data["address"] = $user_address;
                }

                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful",
                    "data" => $response_data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid password"
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "User not found with provided contact"
            ]);
        }

        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Login failed: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => $path === 'login' ? "Invalid request method. Use POST" : "Invalid endpoint"
    ]);
}

mysqli_close($conn);
?>