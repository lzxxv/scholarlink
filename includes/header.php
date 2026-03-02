<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ScholarLink</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">ScholarLink</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){ ?>
          <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/pending_providers.php">Pending Providers</a></li>
        <?php } ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'provider'){ ?>
          <li class="nav-item"><a class="nav-link" href="../provider/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="../provider/add_scholarship.php">Add Scholarship</a></li>
          <li class="nav-item"><a class="nav-link" href="../provider/scholarships.php">My Scholarships</a></li>
          <li class="nav-item"><a class="nav-link" href="../provider/applicants.php">Applicants</a></li>
        <?php } ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'student'){ ?>
          <li class="nav-item"><a class="nav-link" href="../student/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="../student/scholarships.php">Browse Scholarships</a></li>
          <li class="nav-item"><a class="nav-link" href="../student/my_applications.php">My Applications</a></li>
        <?php } ?>

      </ul>

      <div class="d-flex align-items-center gap-2">
        <?php if(isset($_SESSION['name'])){ ?>
          <span class="text-white small">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
          <a class="btn btn-sm btn-outline-light" href="../auth/logout.php">Logout</a>
        <?php } else { ?>
          <a class="btn btn-sm btn-outline-light" href="../auth/login.php">Login</a>
        <?php } ?>
      </div>

    </div>
  </div>
</nav>

<div class="container py-4">