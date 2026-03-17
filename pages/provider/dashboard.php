<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$user_id = $_SESSION['user_id'];
$verification_status = 'pending';
$organization_name = 'Provider';

$stmt = mysqli_prepare($conn, "SELECT organization_name, verification_status FROM provider_profiles WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $provider = mysqli_fetch_assoc($result);
    $organization_name = $provider['organization_name'] ?? 'Provider';
    $verification_status = $provider['verification_status'] ?? 'pending';
    $_SESSION['verification_status'] = $verification_status;
}

/* Counts */
$total_scholarships = 0;
$open_scholarships = 0;
$total_applicants = 0;

$q1 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=?");
mysqli_stmt_bind_param($q1, "i", $user_id);
mysqli_stmt_execute($q1);
$r1 = mysqli_stmt_get_result($q1);
if ($r1 && $row = mysqli_fetch_assoc($r1)) {
    $total_scholarships = (int)$row['total'];
}

$q2 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=? AND status='open'");
mysqli_stmt_bind_param($q2, "i", $user_id);
mysqli_stmt_execute($q2);
$r2 = mysqli_stmt_get_result($q2);
if ($r2 && $row = mysqli_fetch_assoc($r2)) {
    $open_scholarships = (int)$row['total'];
}

$q3 = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     JOIN scholarships s ON a.scholarship_id = s.id
     WHERE s.provider_id=?"
);
mysqli_stmt_bind_param($q3, "i", $user_id);
mysqli_stmt_execute($q3);
$r3 = mysqli_stmt_get_result($q3);
if ($r3 && $row = mysqli_fetch_assoc($r3)) {
    $total_applicants = (int)$row['total'];
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-dashboard.css">

<div class="provider-dashboard-page">
  <div class="container py-4">

    <div class="provider-hero mb-4">
      <div class="provider-hero-shape provider-hero-shape-1"></div>
      <div class="provider-hero-shape provider-hero-shape-2"></div>

      <div class="row align-items-center g-4 position-relative">
        <div class="col-lg-8">
          <span class="provider-badge">Provider Panel</span>
          <h2>Welcome, <?php echo htmlspecialchars($organization_name); ?>!</h2>
          <p>
            Create scholarships, manage applicants, and monitor provider activity from one organized dashboard.
          </p>
        </div>

        <div class="col-lg-4">
          <div class="provider-hero-panel">
            <div class="provider-panel-label">Verification Status</div>
            <div class="provider-panel-main">
              <?php echo htmlspecialchars(strtoupper($verification_status)); ?>
            </div>
            <div class="provider-panel-sub">
              <?php if ($verification_status === 'verified') { ?>
                Your provider account is approved and ready to post scholarships.
              <?php } else { ?>
                Your account can browse the dashboard, but posting stays disabled until admin approval.
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card provider-stat-card stat-total h-100">
          <div class="card-body">
            <div class="provider-stat-top">
              <span class="provider-stat-tag">Total</span>
              <div class="provider-stat-icon">S</div>
            </div>
            <div class="provider-stat-label">My Scholarships</div>
            <div class="provider-stat-value"><?php echo $total_scholarships; ?></div>
            <div class="provider-stat-note">All scholarship postings linked to your account</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card provider-stat-card stat-open h-100">
          <div class="card-body">
            <div class="provider-stat-top">
              <span class="provider-stat-tag">Open</span>
              <div class="provider-stat-icon">O</div>
            </div>
            <div class="provider-stat-label">Open Scholarships</div>
            <div class="provider-stat-value"><?php echo $open_scholarships; ?></div>
            <div class="provider-stat-note">Scholarships currently visible to students</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card provider-stat-card stat-applicants h-100">
          <div class="card-body">
            <div class="provider-stat-top">
              <span class="provider-stat-tag">Applicants</span>
              <div class="provider-stat-icon">A</div>
            </div>
            <div class="provider-stat-label">Total Applicants</div>
            <div class="provider-stat-value"><?php echo $total_applicants; ?></div>
            <div class="provider-stat-note">Applications received across your scholarships</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card provider-main-card">
      <div class="card-body">
        <div class="section-kicker">Management</div>
        <div class="section-title">Provider Workflow</div>
        <p class="section-text">
          Update your organization profile, post scholarship opportunities, review applicants, and maintain notes for each application.
        </p>

        <div class="provider-actions-grid">
          <a class="provider-action-box" href="profile.php">
            <strong>Profile Settings</strong>
            <span>Manage organization details and contact information.</span>
          </a>

          <a class="provider-action-box <?php echo $verification_status !== 'verified' ? 'disabled-box' : ''; ?>"
             href="<?php echo $verification_status === 'verified' ? 'add_scholarship.php' : '#'; ?>">
            <strong>Post Scholarship</strong>
            <span>
              <?php echo $verification_status === 'verified'
                ? 'Create a new scholarship listing.'
                : 'Posting is disabled until account approval.'; ?>
            </span>
          </a>

          <a class="provider-action-box" href="scholarships.php">
            <strong>My Scholarships</strong>
            <span>View and manage your scholarship records.</span>
          </a>

          <a class="provider-action-box" href="applicants.php">
            <strong>Applicants</strong>
            <span>Track student applications and update statuses.</span>
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>