<?php
require_once("../../config/db.php");
require_once("../../config/mailer.php");
session_start();

$msg      = "";
$msg_type = "error";

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: register.php");
    exit;
}

$user_id = (int) $_SESSION['pending_user_id'];
$email   = $_SESSION['pending_verification_email'] ?? '';

$fetchStmt = mysqli_prepare($conn,
    "SELECT verification_code, verification_expires_at, resend_at FROM users WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($fetchStmt, "i", $user_id);
mysqli_stmt_execute($fetchStmt);
$fetchRes = mysqli_stmt_get_result($fetchStmt);
$userData = mysqli_fetch_assoc($fetchRes);

$cooldown_seconds = 60;
$resend_wait      = 0;
$can_resend       = true;

if (!empty($userData['resend_at'])) {
    $last_resend = strtotime($userData['resend_at']);
    $elapsed     = time() - $last_resend;
    $resend_wait = max(0, $cooldown_seconds - $elapsed);
    $can_resend  = $resend_wait <= 0;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'sent') {
        $msg      = "Verification code sent! Check your email inbox (or spam folder).";
        $msg_type = "success";
    } elseif ($_GET['msg'] === 'email_failed') {
        $msg      = "Account created but we failed to send the verification email. Please use the Resend button below.";
        $msg_type = "warning";
    }
}

if (isset($_POST['verify'])) {
    $entered = trim($_POST['code'] ?? '');

    if (empty($entered)) {
        $msg      = "Please enter the verification code.";
        $msg_type = "error";
    } elseif (!$userData) {
        $msg      = "Invalid session. Please register again.";
        $msg_type = "error";
    } elseif ($entered !== $userData['verification_code']) {
        $msg      = "Incorrect verification code. Please check your email and try again.";
        $msg_type = "error";
    } elseif (strtotime($userData['verification_expires_at']) < time()) {
        $msg      = "Your code has expired. Please click Resend to get a new one.";
        $msg_type = "warning";
    } else {
        $update = mysqli_prepare($conn,
            "UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = ?"
        );
        mysqli_stmt_bind_param($update, "i", $user_id);
        mysqli_stmt_execute($update);

        unset($_SESSION['pending_user_id'], $_SESSION['pending_verification_email']);
        header("Location: login.php?msg=verified");
        exit;
    }
}

if (isset($_POST['resend'])) {
    if (!$can_resend) {
        $msg      = "Please wait {$resend_wait} seconds before requesting a new code.";
        $msg_type = "warning";
    } else {
        $new_code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $new_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $now         = date('Y-m-d H:i:s');

        $upd = mysqli_prepare($conn,
            "UPDATE users SET verification_code = ?, verification_expires_at = ?, resend_at = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "sssi", $new_code, $new_expires, $now, $user_id);
        mysqli_stmt_execute($upd);

        $resend_wait = $cooldown_seconds;
        $can_resend  = false;

        $result = send_verification_email($email, $new_code);

        if ($result['success']) {
            $msg      = "A new verification code has been sent to your email.";
            $msg_type = "success";
        } else {
            $msg      = "Failed to send email. Error: " . htmlspecialchars($result['error']);
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email - ScholarLink</title>
  <link rel="stylesheet" href="auth.css">
  <link rel="stylesheet" href="../assets/css/verify-email.css">
</head>
<body class="auth-body">

  <div class="auth-container">
    <div class="auth-left">
      <span class="auth-brand">ScholarLink</span>
      <h1 class="auth-title">Verify Your Email</h1>
      <p class="auth-subtitle">
        We sent a 6-digit code to:<br>
        <strong><?php echo htmlspecialchars($email); ?></strong><br><br>
        Enter the code below to activate your account. Also check your <strong>spam folder</strong> if you don't see it.
      </p>

      <?php if (!empty($msg)) : ?>
        <?php
          $box_class = 'alert-error';
          if ($msg_type === 'success') $box_class = 'alert-success';
          if ($msg_type === 'warning') $box_class = 'alert-warning';
        ?>
        <div class="alert-box <?php echo $box_class; ?>">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <!-- Verify Form -->
      <form method="POST" class="auth-form">
        <div class="form-group full-width-group">
          <label for="code">Verification Code</label>
          <input
            type="text"
            id="code"
            name="code"
            class="input-field verify-code-input"
            maxlength="6"
            placeholder="000000"
            autocomplete="one-time-code"
            inputmode="numeric"
          >
        </div>
        <button type="submit" name="verify" class="auth-btn">Verify Account</button>
      </form>

      <!-- Resend Section -->
      <div class="resend-section">
        <p class="resend-label">Didn't receive the code?</p>

        <?php if ($can_resend) : ?>
          <form method="POST" class="resend-form-inline">
            <button type="submit" name="resend" class="resend-link-btn">
              Click here to resend
            </button>
          </form>
        <?php else : ?>
          <span class="resend-countdown" id="resendBtn">
            Resend available in <span id="countdown"><?php echo $resend_wait; ?></span>s
          </span>
        <?php endif; ?>
      </div>

      <div class="auth-footer verify-footer">
        Wrong email? <a href="register.php">Register again</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-side-content">
        <h2 class="auth-side-title">Almost There!</h2>
        <p class="auth-side-text">
          Just one more step. Verify your email to unlock full access to ScholarLink and start exploring scholarship opportunities.
        </p>
      </div>
      <div class="auth-image-wrap">
        <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
      </div>
    </div>
  </div>

  <script>
    const codeInput = document.getElementById('code');
    if (codeInput) {
      codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
      });
    }

    const countdownEl = document.getElementById('countdown');
    const resendBtn   = document.getElementById('resendBtn');

    if (countdownEl && resendBtn) {
      let seconds = parseInt(countdownEl.textContent);
      const timer = setInterval(function () {
        seconds--;
        if (seconds <= 0) {
          clearInterval(timer);
          location.reload();
        } else {
          countdownEl.textContent = seconds;
        }
      }, 1000);
    }
  </script>

</body>
</html>