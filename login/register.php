<?php
include '../connection.php';

// Initialize variables
$contact = '';
$password = '';
$message = '';
$action = 'Register';
$admin_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate inputs
    if (empty($contact) || empty($password)) {
        $message = "Contact and password are required.";
    } elseif (!preg_match('/^[0-9]{1,10}$/', $contact)) {
        $message = "Contact must be a numeric value up to 10 digits.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if contact exists
            $sql_check = "SELECT Admin_id, Contact FROM tbl_admin WHERE Contact = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "s", $contact);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($row = mysqli_fetch_assoc($result_check)) {
                // Contact exists, update password
                $admin_id = $row['Admin_id'];
                $sql_update = "UPDATE tbl_admin SET Password = ? WHERE Admin_id = ?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "si", $hashed_password, $admin_id);
                mysqli_stmt_execute($stmt_update);
                $message = "Admin password updated successfully.";
                mysqli_stmt_close($stmt_update);
            } else {
                // Contact doesn't exist, insert new admin
                $sql_insert = "INSERT INTO tbl_admin (Contact, Password) VALUES (?, ?)";
                $stmt_insert = mysqli_prepare($conn, $sql_insert);
                mysqli_stmt_bind_param($stmt_insert, "ss", $contact, $hashed_password);
                mysqli_stmt_execute($stmt_insert);
                $admin_id = mysqli_insert_id($conn);
                $message = "Admin registered successfully.";
                mysqli_stmt_close($stmt_insert);
            }
            mysqli_stmt_close($stmt_check);
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Load existing admin data if contact is provided (e.g., after refresh or update)
if (isset($_GET['contact']) && preg_match('/^[0-9]{1,10}$/', $_GET['contact'])) {
    $contact = $_GET['contact'];
    $sql_load = "SELECT Admin_id, Contact FROM tbl_admin WHERE Contact = ?";
    $stmt_load = mysqli_prepare($conn, $sql_load);
    mysqli_stmt_bind_param($stmt_load, "s", $contact);
    mysqli_stmt_execute($stmt_load);
    $result_load = mysqli_stmt_get_result($stmt_load);
    if ($row = mysqli_fetch_assoc($result_load)) {
        $admin_id = $row['Admin_id'];
        $contact = $row['Contact'];
        $action = 'Update';
    }
    mysqli_stmt_close($stmt_load);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; }
        .form-container { padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .message { color: green; margin-bottom: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Admin <?php echo $action; ?></h2>
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') === false ? 'message' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="contact">Contact (Numeric, up to 10 digits):</label>
                <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password (Minimum 6 characters):</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="<?php echo $action; ?> Admin">
        </form>
    </div>
</body>
</html>