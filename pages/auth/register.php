<?php
require_once("../../config/db.php");
session_start();

$msg = "";
$msg_type = "error";

function clean($value) {
    return trim($value ?? "");
}

if (isset($_POST['register'])) {
    $role             = clean($_POST['role'] ?? '');
    $email            = clean($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Student fields
    $first_name = clean($_POST['first_name'] ?? '');
    $last_name  = clean($_POST['last_name'] ?? '');
    $school     = clean($_POST['school'] ?? '');
    $course     = clean($_POST['course'] ?? '');
    $year_level = clean($_POST['year_level'] ?? '');

    // Provider fields
    $organization_name = clean($_POST['organization_name'] ?? '');
    $contact_person    = clean($_POST['contact_person'] ?? '');
    $address           = clean($_POST['address'] ?? '');

    // Common
    $phone_number = clean($_POST['phone_number'] ?? '');

    if ($role === '' || $email === '' || $password === '' || $confirm_password === '' || $phone_number === '') {
        $msg = "Please fill in all required fields.";
    } elseif (!in_array($role, ['student', 'provider'])) {
        $msg = "Invalid role selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone_number)) {
        $msg = "Please enter a valid phone number.";
    } elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $msg = "Password and confirm password do not match.";
    } else {
        if ($role === "student") {
            if ($first_name === '' || $last_name === '' || $school === '' || $course === '' || $year_level === '') {
                $msg = "Please complete all student information.";
            }
        }

        if ($role === "provider") {
            if ($organization_name === '' || $contact_person === '' || $address === '') {
                $msg = "Please complete all provider information.";
            }
        }

        if ($msg === '') {
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);
            $check_result = mysqli_stmt_get_result($check);

            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $msg = "Email is already registered.";
            } else {
                mysqli_begin_transaction($conn);

                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $status = "active";

                    $stmtUser = mysqli_prepare(
                        $conn,
                        "INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmtUser, "ssss", $email, $hashed, $role, $status);

                    if (!mysqli_stmt_execute($stmtUser)) {
                        throw new Exception("Failed to create user account.");
                    }

                    $user_id = mysqli_insert_id($conn);

                    if ($role === "student") {
                        $stmtStudent = mysqli_prepare(
                            $conn,
                            "INSERT INTO student_profiles (user_id, first_name, last_name, phone_number, school, course, year_level)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        mysqli_stmt_bind_param(
                            $stmtStudent,
                            "issssss",
                            $user_id,
                            $first_name,
                            $last_name,
                            $phone_number,
                            $school,
                            $course,
                            $year_level
                        );

                        if (!mysqli_stmt_execute($stmtStudent)) {
                            throw new Exception("Failed to save student profile.");
                        }

                        $msg = "Registered successfully! You can now login.";
                    } else {
                        $verification_status = "pending";

                        $stmtProvider = mysqli_prepare(
                            $conn,
                            "INSERT INTO provider_profiles (user_id, organization_name, contact_person, phone_number, address, verification_status)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        mysqli_stmt_bind_param(
                            $stmtProvider,
                            "isssss",
                            $user_id,
                            $organization_name,
                            $contact_person,
                            $phone_number,
                            $address,
                            $verification_status
                        );

                        if (!mysqli_stmt_execute($stmtProvider)) {
                            throw new Exception("Failed to save provider profile.");
                        }

                        $msg = "Registered successfully! You can now login, but posting scholarships will stay locked until your provider account is approved.";
                    }

                    mysqli_commit($conn);
                    $msg_type = "success";
                    $_POST = [];
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $msg = $e->getMessage();
                }
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
      <p class="auth-subtitle">Register as a student or provider to start using ScholarLink.</p>

      <?php if (!empty($msg)) : ?>
        <div class="alert-box <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
          <?php echo htmlspecialchars($msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form" id="registerForm" novalidate>
        <input type="hidden" name="role" id="role" value="<?php echo isset($_POST['role']) ? htmlspecialchars($_POST['role']) : 'student'; ?>">

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

        <!-- Student Fields -->
        <div id="student-fields">
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" class="input-field" placeholder="Enter your first name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" class="input-field" placeholder="Enter your last name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="school">School / University</label>
              <input type="text" id="school" name="school" class="input-field" placeholder="Enter your school" value="<?php echo htmlspecialchars($_POST['school'] ?? ''); ?>">
            </div>

            <div class="form-group">
              <label for="course">Course / Strand</label>
              <input type="text" id="course" name="course" class="input-field" placeholder="Enter your course or strand" value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="year_level">Year Level</label>
              <input type="text" id="year_level" name="year_level" class="input-field" placeholder="e.g. 1st Year" value="<?php echo htmlspecialchars($_POST['year_level'] ?? ''); ?>">
            </div>

            <div class="form-group">
              <label for="phone_number_student">Phone Number</label>
              <input type="text" id="phone_number_student" name="phone_number" class="input-field" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <!-- Provider Fields -->
        <div id="provider-fields" class="hidden-role-fields">
          <div class="form-row">
            <div class="form-group">
              <label for="organization_name">Organization Name</label>
              <input type="text" id="organization_name" name="organization_name" class="input-field" placeholder="Enter your organization name" value="<?php echo htmlspecialchars($_POST['organization_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
              <label for="contact_person">Contact Person</label>
              <input type="text" id="contact_person" name="contact_person" class="input-field" placeholder="Enter contact person's name" value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phone_number_provider">Phone Number</label>
              <input type="text" id="phone_number_provider" name="phone_number" class="input-field" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" class="input-field" placeholder="Enter your address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <div class="form-group full-width-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="input-field" placeholder="Enter your email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password" class="input-field" placeholder="Create a password" required>
              <button type="button" class="toggle-password" data-target="password">Show</button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-wrap">
              <input type="password" id="confirm_password" name="confirm_password" class="input-field" placeholder="Confirm your password" required>
              <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
            </div>
          </div>
        </div>

        <div class="auth-options">
          <input type="checkbox" id="terms" required>
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
          Join ScholarLink and discover opportunities, connect with trusted scholarship providers, and build a better future with ease.
        </p>
      </div>

      <div class="auth-image-wrap">
        <img src="../assets/Background.jpg" alt="ScholarLink Illustration" class="auth-illustration">
      </div>
    </div>
  </div>

  <script>
  const roleInput = document.getElementById('role');
  const studentFields = document.getElementById('student-fields');
  const providerFields = document.getElementById('provider-fields');
  const roleCards = document.querySelectorAll('.role-card');
  const form = document.getElementById('registerForm');

  function toggleFieldState(container, isEnabled) {
    const fields = container.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
      field.disabled = !isEnabled;
    });
  }

  function setRole(role) {
    roleInput.value = role;

    roleCards.forEach(card => {
      card.classList.toggle('active', card.dataset.role === role);
    });

    if (role === 'student') {
      studentFields.classList.remove('hidden-role-fields');
      providerFields.classList.add('hidden-role-fields');

      toggleFieldState(studentFields, true);
      toggleFieldState(providerFields, false);
    } else {
      providerFields.classList.remove('hidden-role-fields');
      studentFields.classList.add('hidden-role-fields');

      toggleFieldState(providerFields, true);
      toggleFieldState(studentFields, false);
    }
  }

  roleCards.forEach(card => {
    card.addEventListener('click', function () {
      setRole(this.dataset.role);
    });
  });

  setRole(roleInput.value || 'student');

  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function () {
      const target = document.getElementById(this.dataset.target);
      const isPassword = target.type === 'password';
      target.type = isPassword ? 'text' : 'password';
      this.textContent = isPassword ? 'Hide' : 'Show';
    });
  });

  form.addEventListener('submit', function (e) {
    const role = roleInput.value;
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;

    let phone = "";
    let errors = [];

    if (role === 'student') {
      const firstName = document.getElementById('first_name').value.trim();
      const lastName = document.getElementById('last_name').value.trim();
      const school = document.getElementById('school').value.trim();
      const course = document.getElementById('course').value.trim();
      const yearLevel = document.getElementById('year_level').value.trim();
      phone = document.getElementById('phone_number_student').value.trim();

      if (!firstName || !lastName || !school || !course || !yearLevel || !phone) {
        errors.push("Please complete all student fields.");
      }
    }

    if (role === 'provider') {
      const organization = document.getElementById('organization_name').value.trim();
      const contact = document.getElementById('contact_person').value.trim();
      const address = document.getElementById('address').value.trim();
      phone = document.getElementById('phone_number_provider').value.trim();

      if (!organization || !contact || !address || !phone) {
        errors.push("Please complete all provider fields.");
      }
    }

    if (!email) {
      errors.push("Email is required.");
    } else {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(email)) {
        errors.push("Please enter a valid email address.");
      }
    }

    if (!phone) {
      errors.push("Phone number is required.");
    } else {
      const phonePattern = /^[0-9+\-\s]{7,20}$/;
      if (!phonePattern.test(phone)) {
        errors.push("Please enter a valid phone number.");
      }
    }

    if (!password || !confirmPassword) {
      errors.push("Password and confirm password are required.");
    } else {
      if (password.length < 6) {
        errors.push("Password must be at least 6 characters long.");
      }
      if (password !== confirmPassword) {
        errors.push("Password and confirm password do not match.");
      }
    }

    if (!terms) {
      errors.push("Please agree to the terms and conditions.");
    }

    if (errors.length > 0) {
      e.preventDefault();
      alert(errors[0]);
    }
  });
</script>

</body>
</html>