



<?php
header("Content-Type: application/json");
include '../connection.php';

// Enable error reporting for debugging (remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // READ milk assignments for a specific date
        $date = isset($_GET['date']) ? mysqli_real_escape_string($conn, trim($_GET['date'])) : null;
        
        if (!$date || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            echo json_encode([
                "status" => "error",
                "message" => "Valid date parameter required (YYYY-MM-DD)"
            ]);
            exit;
        }

        $sql = "SELECT ma.Assignment_id, ma.Seller_id, ma.Date, ma.Assigned_quantity, ma.Remaining_quantity, s.Name 
                FROM tbl_milk_assignment ma 
                JOIN tbl_Seller s ON ma.Seller_id = s.Seller_id 
                WHERE ma.Date = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Query failed: " . mysqli_error($conn)
            ]);
            mysqli_stmt_close($stmt);
            exit;
        }

        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode($assignments);
        break;

    case 'POST':
        // CREATE milk assignment
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (
            empty($data['Seller_id']) ||
            empty($data['Assigned_quantity']) ||
            empty($data['Date'])
        ) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Sanitize and validate inputs
        $seller_id = intval($data['Seller_id']);
        $assigned_quantity = floatval($data['Assigned_quantity']);
        $date = mysqli_real_escape_string($conn, trim($data['Date']));
        $remaining_quantity = $assigned_quantity; // Initially same as assigned

        // Validate seller_id exists
        $checkSellerQuery = "SELECT Seller_id FROM tbl_Seller WHERE Seller_id = ?";
        $checkSellerStmt = mysqli_prepare($conn, $checkSellerQuery);
        mysqli_stmt_bind_param($checkSellerStmt, "i", $seller_id);
        mysqli_stmt_execute($checkSellerStmt);
        mysqli_stmt_store_result($checkSellerStmt);

        if (mysqli_stmt_num_rows($checkSellerStmt) === 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid seller ID"
            ]);
            mysqli_stmt_close($checkSellerStmt);
            exit;
        }
        mysqli_stmt_close($checkSellerStmt);

        // Validate quantity
        if ($assigned_quantity <= 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Assigned quantity must be positive"
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

        // Check if assignment exists for this seller and date
        $checkQuery = "SELECT Assignment_id FROM tbl_milk_assignment WHERE Seller_id = ? AND Date = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "is", $seller_id, $date);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Milk already assigned to this seller for this date"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Prepare and execute insert query
        $sql = "INSERT INTO tbl_milk_assignment (Seller_id, Date, Assigned_quantity, Remaining_quantity) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "isdd", $seller_id, $date, $assigned_quantity, $remaining_quantity);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Milk assigned successfully",
                "assignment_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to assign milk: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    case 'DELETE':
        // DELETE milk assignment
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['Assignment_id'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Assignment_id is required"
            ]);
            exit;
        }

        $assignment_id = intval($data['Assignment_id']);

        // Verify assignment exists
        $checkQuery = "SELECT Assignment_id FROM tbl_milk_assignment WHERE Assignment_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $assignment_id);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) === 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid assignment ID"
            ]);
            mysqli_stmt_close($checkStmt);
            exit;
        }
        mysqli_stmt_close($checkStmt);

        // Delete assignment
        $sql = "DELETE FROM tbl_milk_assignment WHERE Assignment_id  = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $assignment_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Assignment deleted successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to delete assignment: " . mysqli_stmt_error($stmt)
            ]);
        }

        mysqli_stmt_close($stmt);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
        break;
}
    
mysqli_close($conn);
?>