<?php
require_once("../../config/db.php");
session_start();

$msg = "";
$msg_type = "error";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === "" || $password === "") {
        $msg = "Please enter your email and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {

                if ($user['role'] === "provider" && $user['status'] !== "active") {
                    $msg = "Your provider account is still " . $user['status'] . ". Please wait for admin approval.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role']    = $user['role'];
                    $_SESSION['name']    = $user['full_name'];

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - ScholarLink</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

  <div class="auth-container">
    <div class="auth-left">
      <span class="auth-brand">ScholarLink</span>
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-subtitle">Login to continue accessing scholarships, applications, and account features.</p>

      <?php if (!empty($msg)) : ?>
        <div class="alert-box <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
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
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            class="input-field"
            placeholder="Enter your password"
            required
          >
        </div>

        <button type="submit" name="login" class="auth-btn">Login</button>
      </form>

      <div class="auth-footer">
        Don’t have an account? <a href="register.php">Register</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-side-content">
        <h2 class="auth-side-title">Find the Right Opportunity</h2>
        <p class="auth-side-text">
          ScholarLink helps students discover scholarships and lets providers manage postings, applicants, and updates in one place.
        </p>
      </div>

      <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
    </div>
  </div>

</body>
</html>