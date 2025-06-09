<?php
header("Content-Type: application/json");
include '../connection.php'; // Adjust path to connection.php

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
        }
        break;

    case 'POST':
        // Add new address
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['Address'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Address is required"
            ]);
            exit;
        }

        $address = mysqli_real_escape_string($conn, trim($data['Address']));

        $sql = "INSERT INTO tbl_address (Address) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "s", $address);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Address added successfully",
                "address_id" => mysqli_insert_id($conn)
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to add address: " . mysqli_stmt_error($stmt)
            ]);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'PUT':
        // Update existing address
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['Address_id']) || empty($data['Address'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Address ID and Address are required"
            ]);
            exit;
        }

        $address_id = intval($data['Address_id']);
        $address = mysqli_real_escape_string($conn, trim($data['Address']));

        $sql = "UPDATE tbl_address SET Address = ? WHERE Address_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "si", $address, $address_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Address updated successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to update address: " . mysqli_stmt_error($stmt)
            ]);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'DELETE':
        // Delete address
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['Address_id'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Address ID is required"
            ]);
            exit;
        }

        $address_id = intval($data['Address_id']);

        $sql = "DELETE FROM tbl_address WHERE Address_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to prepare statement: " . mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $address_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "success",
                "message" => "Address deleted successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to delete address: " . mysqli_stmt_error($stmt)
            ]);
        }
        mysqli_stmt_close($stmt);
        break;

    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid request method"
        ]);
        break;
}

mysqli_close($conn);
?>