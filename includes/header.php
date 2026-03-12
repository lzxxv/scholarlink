<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ScholarLink</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../pages/assets/css/provider.css">
</head>
<body>

<nav class="navbar navbar-expand-lg sl-navbar">
  <div class="container">
    <a class="navbar-brand sl-brand" href="#">ScholarLink</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto me-auto">

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){ ?>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'pending_providers.php' ? 'active' : ''; ?>" href="../admin/pending_providers.php">Pending Providers</a>
          </li>
        <?php } ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'provider'){ ?>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../provider/dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'scholarships.php' ? 'active' : ''; ?>" href="../provider/scholarships.php">My Scholarships</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'applicants.php' ? 'active' : ''; ?>" href="../provider/applicants.php">Applicants</a>
          </li>
          <li class="nav-item">
            <a class="nav-link sl-nav-link <?php echo $current_page == 'add_scholarship.php' ? 'active' : ''; ?>" href="../provider/add_scholarship.php">Add Scholarship</a>
          </li>
        <?php } ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'student'){ ?>
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

      <div class="d-flex align-items-center gap-2">
        <?php if(isset($_SESSION['name'])){ ?>
          <span class="sl-user-text small">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
          <a class="btn btn-sl-outline btn-sm" href="../auth/logout.php">Logout</a>
        <?php } else { ?>
          <a class="btn btn-sl-outline btn-sm" href="../auth/login.php">Login</a>
        <?php } ?>
      </div>
    </div>
  </div>
</nav>

<div class="container sl-page-wrap">