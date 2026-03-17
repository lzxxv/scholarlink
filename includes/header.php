<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? null;
$display_name = 'User';

/* Student first name from student_profiles */
if ($role === 'student' && isset($_SESSION['user_id']) && isset($conn)) {
    $student_id = (int) $_SESSION['user_id'];

    $stmtHeaderProfile = mysqli_prepare(
        $conn,
        "SELECT first_name FROM student_profiles WHERE user_id=? LIMIT 1"
    );

    if ($stmtHeaderProfile) {
        mysqli_stmt_bind_param($stmtHeaderProfile, "i", $student_id);
        mysqli_stmt_execute($stmtHeaderProfile);
        $resHeaderProfile = mysqli_stmt_get_result($stmtHeaderProfile);

        if ($resHeaderProfile && mysqli_num_rows($resHeaderProfile) > 0) {
            $profileRow = mysqli_fetch_assoc($resHeaderProfile);
            $display_name = trim($profileRow['first_name'] ?? 'User');
        }
    }
} elseif ($role === 'provider' && isset($_SESSION['user_id']) && isset($conn)) {
    $provider_id = (int) $_SESSION['user_id'];

    $stmtProviderProfile = mysqli_prepare(
        $conn,
        "SELECT organization_name FROM provider_profiles WHERE user_id=? LIMIT 1"
    );

    if ($stmtProviderProfile) {
        mysqli_stmt_bind_param($stmtProviderProfile, "i", $provider_id);
        mysqli_stmt_execute($stmtProviderProfile);
        $resProviderProfile = mysqli_stmt_get_result($stmtProviderProfile);

        if ($resProviderProfile && mysqli_num_rows($resProviderProfile) > 0) {
            $providerRow = mysqli_fetch_assoc($resProviderProfile);
            $display_name = trim($providerRow['organization_name'] ?? 'Provider');
        }
    }
} else {
    $email = trim($_SESSION['email'] ?? '');
    if ($email !== '') {
        $display_name = explode('@', $email)[0];
    }
}

$profile_link = "../auth/login.php";
if ($role === 'student') {
    $profile_link = "../student/profile.php";
} elseif ($role === 'provider') {
    $profile_link = "../provider/profile.php";
} elseif ($role === 'admin') {
    $profile_link = "../admin/dashboard.php";
}

$initials = strtoupper(substr($display_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ScholarLink</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/scholarlink/pages/assets/css/provider.css">
  <link rel="stylesheet" href="/scholarlink/pages/assets/css/header.css">
</head>
<body>

<nav class="navbar navbar-expand-lg sl-navbar">
  <div class="container sl-nav-container">
    <a class="navbar-brand sl-brand" href="#">ScholarLink</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav mx-auto">

        <?php if ($role === 'admin') { ?>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">Dashboard</a>
          </li>

          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>" href="../admin/user_management.php">User Management</a>
          </li>
        <?php } ?>

        <?php if ($role === 'provider') { ?>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../provider/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'scholarships.php' ? 'active' : ''; ?>" href="../provider/scholarships.php">My Scholarships</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'add_scholarship.php' ? 'active' : ''; ?>" href="../provider/add_scholarship.php">Add Scholarship</a>
          </li>
        <?php } ?>

        <?php if ($role === 'student') { ?>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../student/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'scholarships.php' ? 'active' : ''; ?>" href="../student/scholarships.php">Browse Scholarships</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'my_applications.php' ? 'active' : ''; ?>" href="../student/my_applications.php">My Applications</a>
          </li>
        <?php } ?>

      </ul>

      <div class="sl-account-wrap">
        <?php if (!empty($_SESSION['role'])) { ?>
          <div class="dropdown sl-user-dropdown">
            <button
              class="btn sl-user-chip dropdown-toggle"
              type="button"
              data-bs-toggle="dropdown"
              data-bs-display="static"
              aria-expanded="false"
            >
              <span class="sl-avatar"><?php echo htmlspecialchars($initials); ?></span>
              <span class="sl-user-name"><?php echo htmlspecialchars($display_name); ?></span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end sl-profile-menu">
              <li>
                <a class="dropdown-item" href="<?php echo $profile_link; ?>">My Profile</a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a>
              </li>
            </ul>
          </div>
        <?php } else { ?>
          <a class="btn btn-sl-outline btn-sm" href="../auth/login.php">Login</a>
        <?php } ?>
      </div>
    </div>
  </div>
</nav>

<div class="container sl-page-wrap">