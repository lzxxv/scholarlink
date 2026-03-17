<?php
require_once("../../config/db.php");
session_start();

$msg = "";

function clean($value) {
    return trim($value ?? "");
}

if (isset($_POST['login'])) {
    $email = clean($_POST['email']);
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $msg = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, email, password, role, status FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (!password_verify($password, $user['password'])) {
                $msg = "Invalid email or password.";
            } elseif ($user['status'] !== 'active') {
                $msg = "Your account is currently disabled.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'provider') {
                    $providerStmt = mysqli_prepare($conn, "SELECT verification_status FROM provider_profiles WHERE user_id = ? LIMIT 1");
                    mysqli_stmt_bind_param($providerStmt, "i", $user['id']);
                    mysqli_stmt_execute($providerStmt);
                    $providerResult = mysqli_stmt_get_result($providerStmt);

                    if ($providerResult && mysqli_num_rows($providerResult) === 1) {
                        $provider = mysqli_fetch_assoc($providerResult);
                        $_SESSION['verification_status'] = $provider['verification_status'];
                    } else {
                        $_SESSION['verification_status'] = 'pending';
                    }
                }

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                    exit;
                } elseif ($user['role'] === 'student') {
                    header("Location: ../student/dashboard.php");
                    exit;
                } elseif ($user['role'] === 'provider') {
                    header("Location: ../provider/dashboard.php");
                    exit;
                } else {
                    $msg = "Invalid user role.";
                    session_unset();
                    session_destroy();
                }
            }
        } else {
            $msg = "Invalid email or password.";
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
      <p class="auth-subtitle">Login to continue exploring scholarships and managing your account.</p>

      <?php if (!empty($msg)) : ?>
        <div class="alert-box alert-error">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="form-group full-width-group">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            class="input-field"
            placeholder="Enter your email address"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required
          >
        </div>

        <div class="form-group full-width-group">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input
              type="password"
              id="password"
              name="password"
              class="input-field"
              placeholder="Enter your password"
              required
            >
            <button type="button" class="toggle-password" data-target="password">Show</button>
          </div>
        </div>

        <button type="submit" name="login" class="auth-btn">Login</button>
      </form>

      <div class="auth-footer">
        Don’t have an account? <a href="register.php">Register</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-side-content">
        <h2 class="auth-side-title">Access Your Opportunities</h2>
        <p class="auth-side-text">
          Sign in to ScholarLink and stay connected with scholarship opportunities, applications, and provider tools in one place.
        </p>
      </div>

      <div class="auth-image-wrap">
        <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.target);
        const isPassword = target.type === 'password';
        target.type = isPassword ? 'text' : 'password';
        this.textContent = isPassword ? 'Hide' : 'Show';
      });
    });
  </script>

</body>
</html>