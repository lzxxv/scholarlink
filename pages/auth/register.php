<?php
require_once("../../config/db.php");
session_start();

$msg = "";

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];

    if ($full_name == "" || $email == "" || $password == "" || $role == "") {
        $msg = "Please fill in all fields.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Provider status should be pending by default
        $status = ($role === "provider") ? "pending" : "active";

        $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $hashed, $role, $status);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Registered successfully! You can now login.";
        } else {
            $msg = "Error: Email might already be used.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register - ScholarLink</title>
</head>
<body>
  <h2>Register</h2>
  <p style="color:red;"><?php echo $msg; ?></p>

  <form method="POST">
    <input type="text" name="full_name" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <label>Role:</label>
    <select name="role" required>
      <option value="">-- Select Role --</option>
      <option value="student">Student</option>
      <option value="provider">Provider</option>
    </select><br><br>

    <button type="submit" name="register">Register</button>
  </form>

  <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>