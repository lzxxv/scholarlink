<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

/* Counts */
$pending_providers = 0;
$active_providers = 0;
$open_scholarships = 0;
$total_students = 0;

$q1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='provider' AND status='pending'");
if ($q1) {
    $pending_providers = (int)(mysqli_fetch_assoc($q1)['total'] ?? 0);
}

$q2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='provider' AND status='active'");
if ($q2) {
    $active_providers = (int)(mysqli_fetch_assoc($q2)['total'] ?? 0);
}

$q3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE status='open'");
if ($q3) {
    $open_scholarships = (int)(mysqli_fetch_assoc($q3)['total'] ?? 0);
}

$q4 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='student'");
if ($q4) {
    $total_students = (int)(mysqli_fetch_assoc($q4)['total'] ?? 0);
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/admin-dashboard.css">

<div class="admin-dashboard-page">
  <div class="container py-4">

    <div class="admin-hero mb-4">
      <div class="admin-hero-shape admin-hero-shape-1"></div>
      <div class="admin-hero-shape admin-hero-shape-2"></div>

      <div class="row align-items-center g-4 position-relative">
        <div class="col-lg-8">
          <span class="admin-badge">Admin Panel</span>
          <h2>Admin Dashboard</h2>
          <p>
            Monitor provider approvals, scholarship records, and system activity from one central dashboard.
          </p>
        </div>

        <div class="col-lg-4">
          <div class="admin-hero-panel">
            <div class="admin-panel-label">Pending Provider Requests</div>
            <div class="admin-panel-main"><?php echo $pending_providers; ?></div>
            <div class="admin-panel-sub">Accounts waiting for admin approval</div>
            <a href="user_management.php?view=providers" class="btn btn-admin-panel">Review Providers</a>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="card admin-stat-card stat-pending h-100">
          <div class="card-body">
            <div class="admin-stat-top">
              <span class="admin-stat-tag">Queue</span>
              <div class="admin-stat-icon">P</div>
            </div>
            <div class="admin-stat-label">Pending Providers</div>
            <div class="admin-stat-value"><?php echo $pending_providers; ?></div>
            <div class="admin-stat-note">Waiting for approval or rejection</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card admin-stat-card stat-active h-100">
          <div class="card-body">
            <div class="admin-stat-top">
              <span class="admin-stat-tag">Active</span>
              <div class="admin-stat-icon">A</div>
            </div>
            <div class="admin-stat-label">Active Providers</div>
            <div class="admin-stat-value"><?php echo $active_providers; ?></div>
            <div class="admin-stat-note">Approved provider accounts in the system</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card admin-stat-card stat-open h-100">
          <div class="card-body">
            <div class="admin-stat-top">
              <span class="admin-stat-tag">Open</span>
              <div class="admin-stat-icon">S</div>
            </div>
            <div class="admin-stat-label">Open Scholarships</div>
            <div class="admin-stat-value"><?php echo $open_scholarships; ?></div>
            <div class="admin-stat-note">Scholarship records currently open</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card admin-stat-card stat-students h-100">
          <div class="card-body">
            <div class="admin-stat-top">
              <span class="admin-stat-tag">Users</span>
              <div class="admin-stat-icon">U</div>
            </div>
            <div class="admin-stat-label">Students</div>
            <div class="admin-stat-value"><?php echo $total_students; ?></div>
            <div class="admin-stat-note">Registered student accounts</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>