<?php
require_once("../../config/db.php");
session_start();

$msg = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {

            // Provider must be approved first
            if ($user['role'] === "provider" && $user['status'] !== "active") {
                $msg = "Your provider account is still ".$user['status'].". Please wait for admin approval.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['full_name'];

                // Redirect by role
                if ($user['role'] === "admin") {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === "provider") {
                    header("Location: ../provider/dashboard.php");
                } else {
                    header("Location: ../student/dashboard.php");
                }
                exit();
            }

        } else {
            $msg = "Invalid password.";
        }
    } else {
        $msg = "No account found for this email.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login - ScholarLink</title>
</head>
<body>
  <h2>Login</h2>
  <p style="color:red;"><?php echo $msg; ?></p>

  <form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit" name="login">Login</button>
  </form>

  <p>No account yet? <a href="register.php">Register</a></p>
</body>
</html>