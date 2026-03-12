<?php
require_once("../../config/db.php");
session_start();

$msg = "";
$msg_type = "error";

if (isset($_POST['register'])) {
    $full_name        = trim($_POST['full_name']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role             = trim($_POST['role']);

    if ($full_name === "" || $email === "" || $password === "" || $confirm_password === "" || $role === "") {
        $msg = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $msg = "Password and confirm password do not match.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        $check_result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($check_result) > 0) {
            $msg = "Email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $status = ($role === "provider") ? "pending" : "active";

            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $hashed, $role, $status);

            if (mysqli_stmt_execute($stmt)) {
                if ($role === "provider") {
                    $msg = "Registered successfully! Your provider account is pending admin approval.";
                } else {
                    $msg = "Registered successfully! You can now login.";
                }
                $msg_type = "success";

                $_POST = [];
            } else {
                $msg = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - ScholarLink</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

  <div class="auth-container">
    <div class="auth-left">
      <span class="auth-brand">ScholarLink</span>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Register to explore scholarships or manage scholarship postings as a provider.</p>

      <?php if (!empty($msg)) : ?>
        <div class="alert-box <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input
            type="text"
            id="full_name"
            name="full_name"
            class="input-field"
            placeholder="Enter your full name"
            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            class="input-field"
            placeholder="Enter your email address"
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="role">Register As</label>
          <select id="role" name="role" class="select-field" required>
            <option value="">-- Select Role --</option>
            <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
            <option value="provider" <?php echo (isset($_POST['role']) && $_POST['role'] === 'provider') ? 'selected' : ''; ?>>Provider</option>
          </select>
          <div class="role-note">
            Students can browse and apply for scholarships. Providers can post scholarships and manage applicants.
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            class="input-field"
            placeholder="Create a password"
            required
          >
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            class="input-field"
            placeholder="Confirm your password"
            required
          >
        </div>

        <div class="auth-options">
          <input type="checkbox" required>
          <span>By registering, you confirm that the information you provided is correct and you agree to the platform terms and conditions.</span>
        </div>

        <button type="submit" name="register" class="auth-btn">Create Account</button>
      </form>

      <div class="auth-footer">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-side-content">
        <h2 class="auth-side-title">Start Your Journey</h2>
        <p class="auth-side-text">
          Join ScholarLink to connect students with scholarship opportunities and help providers manage applications more efficiently.
        </p>
      </div>

      <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
    </div>
  </div>

</body>
</html>