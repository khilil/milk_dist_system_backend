<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // READ sellers
        $sql = "SELECT * FROM tbl_Seller";
        $result = mysqli_query($conn, $sql);

        $sellers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sellers[] = $row;
        }
        echo json_encode($sellers);
        break;

    case 'POST':
        // CREATE seller
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (
            empty($data['Name']) ||
            empty($data['Contact']) ||
            empty($data['Password']) ||
            empty($data['Vehicle_no'])
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
        $vehicle_no = mysqli_real_escape_string($conn, trim($data['Vehicle_no']));

        // Validate phone number (basic check for 10 digits)
        if (!preg_match("/^[0-9]{10}$/", $contact)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid phone number"
            ]);
            exit;
        }

        // Validate vehicle number (basic check for alphanumeric with optional spaces/hyphens)
        if (!preg_match("/^[A-Za-z0-9\s\-]{1,20}$/", $vehicle_no)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid vehicle number"
            ]);
            exit;
        }

        // Check if phone or vehicle number already exists
        $checkQuery = "SELECT Seller_id FROM tbl_Seller WHERE Contact = ? OR Vehicle_no = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ss", $contact, $vehicle_no);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Phone number or vehicle number already exists"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Prepare and execute insert query
        $sql = "INSERT INTO tbl_Seller (Name, Contact, Password, Vehicle_no) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $name, $contact, $password, $vehicle_no);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Seller added successfully",
                "seller_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to add seller: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    case 'PUT':
        // UPDATE seller
        $data = json_decode(file_get_contents("php://input"), true);

        $id = $data['Seller_id'];
        $name = mysqli_real_escape_string($conn, $data['Name']);
        $contact = mysqli_real_escape_string($conn, $data['Contact']);
        $vehicle_no = mysqli_real_escape_string($conn, $data['Vehicle_no']);

        $sql = "UPDATE tbl_Seller SET 
                Name='$name', Contact='$contact', Vehicle_no='$vehicle_no' 
                WHERE Seller_id=$id";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Seller updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    case 'DELETE':
        // DELETE seller
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['Seller_id'];

        $sql = "DELETE FROM tbl_Seller WHERE Seller_id=$id";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Seller deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}

mysqli_close($conn);
?>
