<?php
require_once("../../config/db.php");
require_once("../../config/mailer.php");
session_start();

$msg      = "";
$msg_type = "error";

function clean($value) {
    return trim($value ?? "");
}

function validate_password($password) {
    $errs = [];
    if (strlen($password) < 8)                                         $errs[] = "At least 8 characters";
    if (!preg_match('/[A-Z]/', $password))                             $errs[] = "At least 1 uppercase letter";
    if (!preg_match('/[0-9]/', $password))                             $errs[] = "At least 1 number";
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)) $errs[] = "At least 1 special character";
    return $errs;
}

$field_errors = [
    'first_name'        => '',
    'last_name'         => '',
    'address_student'   => '',
    'organization_name' => '',
    'contact_person'    => '',
    'address'           => '',
    'phone_number'      => '',
    'email'             => '',
    'password'          => '',
    'confirm_password'  => '',
    'terms'             => '',
];

$posted_role = clean($_POST['role'] ?? 'student');

if (isset($_POST['register'])) {

    $role             = clean($_POST['role'] ?? '');
    $email            = clean($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Separate phone field names per role
    $phone_number = $role === 'student'
        ? clean($_POST['phone_student'] ?? '')
        : clean($_POST['phone_provider'] ?? '');

    $first_name      = clean($_POST['first_name'] ?? '');
    $last_name       = clean($_POST['last_name'] ?? '');
    $address_student = clean($_POST['address_student'] ?? '');

    $organization_name = clean($_POST['organization_name'] ?? '');
    $contact_person    = clean($_POST['contact_person'] ?? '');
    $address           = clean($_POST['address'] ?? '');

    // Student validation
    if ($role === 'student') {
        if ($first_name === '')      $field_errors['first_name']      = "First name is required.";
        if ($last_name === '')       $field_errors['last_name']       = "Last name is required.";
        if ($address_student === '') $field_errors['address_student'] = "Address is required.";
    }

    // Provider validation
    if ($role === 'provider') {
        if ($organization_name === '') $field_errors['organization_name'] = "Organization name is required.";
        if ($contact_person === '')    $field_errors['contact_person']    = "Contact person is required.";
        if ($address === '')           $field_errors['address']           = "Address is required.";
    }

    // Phone
    if ($phone_number === '') {
        $field_errors['phone_number'] = "Phone number is required.";
    } elseif (!preg_match('/^09[0-9]{9}$/', $phone_number)) {
        $field_errors['phone_number'] = "Enter a valid PH number (e.g. 09123456789).";
    } else {
        // Check duplicate phone
        $phone_check1 = mysqli_prepare($conn, "SELECT user_id FROM student_profiles WHERE phone_number = ? LIMIT 1");
        mysqli_stmt_bind_param($phone_check1, "s", $phone_number);
        mysqli_stmt_execute($phone_check1);
        $phone_res1 = mysqli_stmt_get_result($phone_check1);

        $phone_check2 = mysqli_prepare($conn, "SELECT user_id FROM provider_profiles WHERE phone_number = ? LIMIT 1");
        mysqli_stmt_bind_param($phone_check2, "s", $phone_number);
        mysqli_stmt_execute($phone_check2);
        $phone_res2 = mysqli_stmt_get_result($phone_check2);

        if (($phone_res1 && mysqli_num_rows($phone_res1) > 0) || ($phone_res2 && mysqli_num_rows($phone_res2) > 0)) {
            $field_errors['phone_number'] = "This phone number is already registered.";
        }
    }

    // Email
    if ($email === '') {
        $field_errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors['email'] = "Enter a valid email address.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        $check_result = mysqli_stmt_get_result($check);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $field_errors['email'] = "This email is already registered.";
        }
    }

    // Password
    if ($password === '') {
        $field_errors['password'] = "Password is required.";
    } else {
        $pw_errors = validate_password($password);
        if (!empty($pw_errors)) {
            $field_errors['password'] = implode(", ", $pw_errors) . ".";
        }
    }

    // Confirm password
    if ($confirm_password === '') {
        $field_errors['confirm_password'] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $field_errors['confirm_password'] = "Passwords do not match.";
    }

    // Terms
    if (!isset($_POST['terms'])) {
        $field_errors['terms'] = "You must agree to the terms and conditions.";
    }

    $has_errors = !empty(array_filter($field_errors));

    if (!$has_errors) {
        mysqli_begin_transaction($conn);

        try {
            $hashed               = password_hash($password, PASSWORD_DEFAULT);
            $status               = "active";
            $verification_code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $verification_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $resend_at            = date('Y-m-d H:i:s');

            $stmtUser = mysqli_prepare(
                $conn,
                "INSERT INTO users (email, password, role, status, verification_code, verification_expires_at, email_verified, resend_at)
                 VALUES (?, ?, ?, ?, ?, ?, 0, ?)"
            );
            mysqli_stmt_bind_param($stmtUser, "sssssss", $email, $hashed, $role, $status, $verification_code, $verification_expires, $resend_at);

            if (!mysqli_stmt_execute($stmtUser)) {
                throw new Exception("Failed to create user account: " . mysqli_stmt_error($stmtUser));
            }

            $user_id = mysqli_insert_id($conn);

            if ($role === "student") {
                $stmtStudent = mysqli_prepare(
                    $conn,
                    "INSERT INTO student_profiles (user_id, first_name, last_name, phone_number, address)
                     VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmtStudent, "issss", $user_id, $first_name, $last_name, $phone_number, $address_student);

                if (!mysqli_stmt_execute($stmtStudent)) {
                    throw new Exception("Failed to save student profile: " . mysqli_stmt_error($stmtStudent));
                }

            } else {
                $verification_status = "pending";
                $stmtProvider = mysqli_prepare(
                    $conn,
                    "INSERT INTO provider_profiles (user_id, organization_name, contact_person, phone_number, address, verification_status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmtProvider, "isssss", $user_id, $organization_name, $contact_person, $phone_number, $address, $verification_status);

                if (!mysqli_stmt_execute($stmtProvider)) {
                    throw new Exception("Failed to save provider profile: " . mysqli_stmt_error($stmtProvider));
                }
            }

            mysqli_commit($conn);

            $result = send_verification_email($email, $verification_code);

            $_SESSION['pending_user_id']            = $user_id;
            $_SESSION['pending_verification_email'] = $email;

            if ($result['success']) {
                header("Location: verify_email.php?msg=sent");
                exit;
            } else {
                header("Location: verify_email.php?msg=email_failed");
                exit;
            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $msg = $e->getMessage();
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
  <style>
    .field-error {
      color: #dc2626;
      font-size: 12px;
      margin-top: 5px;
      display: block;
    }
    .input-field.is-invalid {
      border-color: #dc2626 !important;
      background: #fff5f5;
    }
    .input-field.is-valid {
      border-color: #16a34a !important;
    }
    .pw-strength {
      margin-top: 8px;
    }
    .pw-strength-bar {
      height: 4px;
      border-radius: 4px;
      background: #e2e8f0;
      margin-bottom: 4px;
      overflow: hidden;
    }
    .pw-strength-fill {
      height: 100%;
      border-radius: 4px;
      transition: all 0.3s ease;
      width: 0%;
    }
    .pw-strength-text {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .pw-req {
      font-size: 11px;
      margin-top: 4px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2px 8px;
      line-height: 1.8;
    }
    .pw-req span { display: block; }
    .pw-req span.met   { color: #16a34a; }
    .pw-req span.unmet { color: #94a3b8; }
  </style>
</head>
<body class="auth-body">

  <div class="auth-container">
    <div class="auth-left">
      <span class="auth-brand">ScholarLink</span>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Register as a student or provider to start using ScholarLink.</p>

      <?php if (!empty($msg)) : ?>
        <div class="alert-box alert-error"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <form method="POST" action="" class="auth-form" id="registerForm">
        <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($posted_role); ?>">

        <!-- Role selector -->
        <div class="form-group">
          <label>Register As</label>
          <div class="role-switch">
            <div class="role-card" data-role="student">
              <h4>Student</h4>
              <p>Browse and apply for scholarships.</p>
            </div>
            <div class="role-card" data-role="provider">
              <h4>Provider</h4>
              <p>Manage your organization and scholarship postings.</p>
            </div>
          </div>
        </div>

        <!-- ===== STUDENT FIELDS ===== -->
        <div id="student-fields">
          <div class="form-row">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" name="first_name"
                     class="input-field <?php echo $field_errors['first_name'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter your first name"
                     value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                     autocomplete="given-name">
              <?php if ($field_errors['first_name']) : ?>
                <span class="field-error"><?php echo $field_errors['first_name']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" name="last_name"
                     class="input-field <?php echo $field_errors['last_name'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter your last name"
                     value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                     autocomplete="family-name">
              <?php if ($field_errors['last_name']) : ?>
                <span class="field-error"><?php echo $field_errors['last_name']; ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone Number</label>
              <input type="text" name="phone_student" id="phone_student"
                     class="input-field <?php echo ($field_errors['phone_number'] && $posted_role === 'student') ? 'is-invalid' : ''; ?>"
                     placeholder="09123456789"
                     maxlength="11"
                     value="<?php echo htmlspecialchars($_POST['phone_student'] ?? ''); ?>"
                     autocomplete="tel">
              <?php if ($field_errors['phone_number'] && $posted_role === 'student') : ?>
                <span class="field-error"><?php echo $field_errors['phone_number']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="address_student"
                     class="input-field <?php echo $field_errors['address_student'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter your address"
                     value="<?php echo htmlspecialchars($_POST['address_student'] ?? ''); ?>"
                     autocomplete="street-address">
              <?php if ($field_errors['address_student']) : ?>
                <span class="field-error"><?php echo $field_errors['address_student']; ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- ===== PROVIDER FIELDS ===== -->
        <div id="provider-fields" style="display:none">
          <div class="form-row">
            <div class="form-group">
              <label>Organization Name</label>
              <input type="text" name="organization_name"
                     class="input-field <?php echo $field_errors['organization_name'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter your organization name"
                     value="<?php echo htmlspecialchars($_POST['organization_name'] ?? ''); ?>"
                     autocomplete="organization">
              <?php if ($field_errors['organization_name']) : ?>
                <span class="field-error"><?php echo $field_errors['organization_name']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Contact Person</label>
              <input type="text" name="contact_person"
                     class="input-field <?php echo $field_errors['contact_person'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter contact person's name"
                     value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>"
                     autocomplete="name">
              <?php if ($field_errors['contact_person']) : ?>
                <span class="field-error"><?php echo $field_errors['contact_person']; ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone Number</label>
              <input type="text" name="phone_provider" id="phone_provider"
                     class="input-field <?php echo ($field_errors['phone_number'] && $posted_role === 'provider') ? 'is-invalid' : ''; ?>"
                     placeholder="09123456789"
                     maxlength="11"
                     value="<?php echo htmlspecialchars($_POST['phone_provider'] ?? ''); ?>"
                     autocomplete="tel">
              <?php if ($field_errors['phone_number'] && $posted_role === 'provider') : ?>
                <span class="field-error"><?php echo $field_errors['phone_number']; ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="address"
                     class="input-field <?php echo $field_errors['address'] ? 'is-invalid' : ''; ?>"
                     placeholder="Enter your address"
                     value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                     autocomplete="street-address">
              <?php if ($field_errors['address']) : ?>
                <span class="field-error"><?php echo $field_errors['address']; ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- ===== EMAIL ===== -->
        <div class="form-group full-width-group">
          <label>Email Address</label>
          <input type="email" name="email"
                 class="input-field <?php echo $field_errors['email'] ? 'is-invalid' : ''; ?>"
                 placeholder="Enter your email address"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 autocomplete="email">
          <?php if ($field_errors['email']) : ?>
            <span class="field-error"><?php echo $field_errors['email']; ?></span>
          <?php endif; ?>
        </div>

        <!-- ===== PASSWORD ===== -->
        <div class="form-row">
          <div class="form-group">
            <label>Password</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password"
                     class="input-field <?php echo $field_errors['password'] ? 'is-invalid' : ''; ?>"
                     placeholder="Create a password"
                     autocomplete="new-password">
              <button type="button" class="toggle-password" data-target="password">Show</button>
            </div>
            <?php if ($field_errors['password']) : ?>
              <span class="field-error"><?php echo $field_errors['password']; ?></span>
            <?php endif; ?>
            <div class="pw-strength" id="pwStrength" style="display:none">
              <div class="pw-strength-bar">
                <div class="pw-strength-fill" id="pwStrengthFill"></div>
              </div>
              <div class="pw-strength-text" id="pwStrengthText"></div>
              <div class="pw-req">
                <span id="req-len">✗ 8+ characters</span>
                <span id="req-upper">✗ Uppercase</span>
                <span id="req-num">✗ Number</span>
                <span id="req-special">✗ Special char</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Confirm Password</label>
            <div class="password-wrap">
              <input type="password" id="confirm_password" name="confirm_password"
                     class="input-field <?php echo $field_errors['confirm_password'] ? 'is-invalid' : ''; ?>"
                     placeholder="Confirm your password"
                     autocomplete="new-password">
              <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
            </div>
            <?php if ($field_errors['confirm_password']) : ?>
              <span class="field-error"><?php echo $field_errors['confirm_password']; ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- ===== TERMS ===== -->
        <div class="auth-options">
          <input type="checkbox" id="terms" name="terms"
                 <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
          <span>By registering, you confirm that the information you provided is correct and you agree to the platform terms and conditions.</span>
        </div>
        <?php if ($field_errors['terms']) : ?>
          <span class="field-error"><?php echo $field_errors['terms']; ?></span>
        <?php endif; ?>

        <input type="submit" name="register" id="submitBtn" class="auth-btn" value="Create Account">
      </form>

      <div class="auth-footer">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-side-content">
        <h2 class="auth-side-title">Start Your Journey</h2>
        <p class="auth-side-text">
          Join ScholarLink and discover opportunities, connect with trusted scholarship providers, and build a better future with ease.
        </p>
      </div>
      <div class="auth-image-wrap">
        <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
      </div>
    </div>
  </div>

  <script>
  const roleInput      = document.getElementById('roleInput');
  const studentFields  = document.getElementById('student-fields');
  const providerFields = document.getElementById('provider-fields');
  const roleCards      = document.querySelectorAll('.role-card');
  const submitBtn      = document.getElementById('submitBtn');

  function setRole(role) {
    roleInput.value = role;
    roleCards.forEach(card => card.classList.toggle('active', card.dataset.role === role));
    if (role === 'student') {
      studentFields.style.display  = 'block';
      providerFields.style.display = 'none';
    } else {
      studentFields.style.display  = 'none';
      providerFields.style.display = 'block';
    }
  }

  setRole(roleInput.value || 'student');

  roleCards.forEach(card => {
    card.addEventListener('click', function () { setRole(this.dataset.role); });
  });

  // Toggle password show/hide
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
      const t      = document.getElementById(this.dataset.target);
      t.type       = t.type === 'password' ? 'text' : 'password';
      this.textContent = t.type === 'password' ? 'Show' : 'Hide';
    });
  });

  // Phone — numbers only
  document.querySelectorAll('#phone_student, #phone_provider').forEach(input => {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
    });
  });

  // Password strength
  const passwordInput = document.getElementById('password');
  const pwStrength    = document.getElementById('pwStrength');
  const pwFill        = document.getElementById('pwStrengthFill');
  const pwText        = document.getElementById('pwStrengthText');
  const reqLen        = document.getElementById('req-len');
  const reqUpper      = document.getElementById('req-upper');
  const reqNum        = document.getElementById('req-num');
  const reqSpecial    = document.getElementById('req-special');

  passwordInput.addEventListener('input', function () {
    const val = this.value;
    pwStrength.style.display = val.length > 0 ? 'block' : 'none';

    const hasLen     = val.length >= 8;
    const hasUpper   = /[A-Z]/.test(val);
    const hasNum     = /[0-9]/.test(val);
    const hasSpecial = /[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/.test(val);

    const upd = (el, ok, label) => {
      el.className   = ok ? 'met' : 'unmet';
      el.textContent = (ok ? '✓ ' : '✗ ') + label;
    };

    upd(reqLen,     hasLen,     '8+ characters');
    upd(reqUpper,   hasUpper,   'Uppercase');
    upd(reqNum,     hasNum,     'Number');
    upd(reqSpecial, hasSpecial, 'Special char');

    const score  = [hasLen, hasUpper, hasNum, hasSpecial].filter(Boolean).length;
    const colors = ['', '#ef4444', '#f97316', '#eab308', '#16a34a'];
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

    pwFill.style.width      = (score * 25) + '%';
    pwFill.style.background = colors[score];
    pwText.style.color      = colors[score];
    pwText.textContent      = labels[score];
  });

  // Confirm password live match
  const confirmInput = document.getElementById('confirm_password');
  confirmInput.addEventListener('input', function () {
    const match = this.value === passwordInput.value;
    this.classList.toggle('is-invalid', this.value.length > 0 && !match);
    this.classList.toggle('is-valid',   this.value.length > 0 && match);
  });

  // Loading state
  document.getElementById('registerForm').addEventListener('submit', function () {
    setTimeout(function () {
      submitBtn.disabled = true;
      submitBtn.value    = 'Creating account...';
    }, 300);
  });
  </script>

</body>
</html>