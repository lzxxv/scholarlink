<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$student_id = $_SESSION['user_id'];

/* =========================
   STUDENT PROFILE
========================= */
$student_first_name = 'Student';

$stmtProfile = mysqli_prepare(
    $conn,
    "SELECT first_name FROM student_profiles WHERE user_id=? LIMIT 1"
);
mysqli_stmt_bind_param($stmtProfile, "i", $student_id);
mysqli_stmt_execute($stmtProfile);
$resProfile = mysqli_stmt_get_result($stmtProfile);

if ($resProfile && mysqli_num_rows($resProfile) > 0) {
    $profile = mysqli_fetch_assoc($resProfile);
    $student_first_name = trim($profile['first_name'] ?? 'Student');
}

/* =========================
   COUNTS
========================= */
$open_scholarships = 0;
$total_applications = 0;
$pending_applications = 0;
$approved_applications = 0;
$rejected_applications = 0;

$q1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE status='open'");
if ($q1) {
    $open_scholarships = (int)(mysqli_fetch_assoc($q1)['total'] ?? 0);
}

$stmtTotal = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total FROM applications WHERE student_id=?"
);
mysqli_stmt_bind_param($stmtTotal, "i", $student_id);
mysqli_stmt_execute($stmtTotal);
$resTotal = mysqli_stmt_get_result($stmtTotal);
if ($resTotal) {
    $total_applications = (int)(mysqli_fetch_assoc($resTotal)['total'] ?? 0);
}

$stmtPending = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id=? AND status IN ('submitted','under_review')"
);
mysqli_stmt_bind_param($stmtPending, "i", $student_id);
mysqli_stmt_execute($stmtPending);
$resPending = mysqli_stmt_get_result($stmtPending);
if ($resPending) {
    $pending_applications = (int)(mysqli_fetch_assoc($resPending)['total'] ?? 0);
}

$stmtApproved = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id=? AND status='approved'"
);
mysqli_stmt_bind_param($stmtApproved, "i", $student_id);
mysqli_stmt_execute($stmtApproved);
$resApproved = mysqli_stmt_get_result($stmtApproved);
if ($resApproved) {
    $approved_applications = (int)(mysqli_fetch_assoc($resApproved)['total'] ?? 0);
}

$stmtRejected = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id=? AND status='rejected'"
);
mysqli_stmt_bind_param($stmtRejected, "i", $student_id);
mysqli_stmt_execute($stmtRejected);
$resRejected = mysqli_stmt_get_result($stmtRejected);
if ($resRejected) {
    $rejected_applications = (int)(mysqli_fetch_assoc($resRejected)['total'] ?? 0);
}

/* =========================
   APPLICATION TRACKING
========================= */
$stmtRecent = mysqli_prepare(
    $conn,
    "SELECT a.status, a.created_at, s.title, s.deadline,
            pp.organization_name AS provider_name
     FROM applications a
     JOIN scholarships s ON a.scholarship_id = s.id
     JOIN provider_profiles pp ON s.provider_id = pp.user_id
     WHERE a.student_id=?
     ORDER BY a.id DESC
     LIMIT 6"
);
mysqli_stmt_bind_param($stmtRecent, "i", $student_id);
mysqli_stmt_execute($stmtRecent);
$recentApplications = mysqli_stmt_get_result($stmtRecent);

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/student-dashboard.css">

<div class="student-dashboard">
  <div class="container py-4">

    <!-- OVERVIEW -->
    <div class="crm-hero mb-4">
      <div class="crm-hero-shape crm-hero-shape-1"></div>
      <div class="crm-hero-shape crm-hero-shape-2"></div>

      <div class="row position-relative">
        <div class="col-12">
          <span class="crm-badge">Student CRM Dashboard</span>
          <h2>Welcome back, <?php echo htmlspecialchars($student_first_name); ?>!</h2>
          <p>
            Monitor your scholarship applications, review current opportunities, and track status updates.
          </p>
        </div>
      </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="card stat-card stat-green h-100">
          <div class="card-body">
            <div class="stat-top">
              <span class="stat-tag">Live</span>
              <div class="stat-icon"></div>
            </div>
            <div class="stat-label">Open Scholarships</div>
            <div class="stat-value"><?php echo $open_scholarships; ?></div>
            <div class="stat-note">Currently available scholarship records</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card stat-card stat-lime h-100">
          <div class="card-body">
            <div class="stat-top">
              <span class="stat-tag">Total</span>
              <div class="stat-icon"></div>
            </div>
            <div class="stat-label">Applications Submitted</div>
            <div class="stat-value"><?php echo $total_applications; ?></div>
            <div class="stat-note">Total applications stored in your account</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card stat-card stat-soft h-100">
          <div class="card-body">
            <div class="stat-top">
              <span class="stat-tag">Review</span>
              <div class="stat-icon"></div>
            </div>
            <div class="stat-label">Pending / Review</div>
            <div class="stat-value"><?php echo $pending_applications; ?></div>
            <div class="stat-note">Applications awaiting provider evaluation</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card stat-card stat-dark h-100">
          <div class="card-body">
            <div class="stat-top">
              <span class="stat-tag">Approved</span>
              <div class="stat-icon"></div>
            </div>
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo $approved_applications; ?></div>
            <div class="stat-note">Successfully approved scholarship applications</div>
          </div>
        </div>
      </div>
    </div>

    <!-- TRACKING + SUMMARY -->
    <div class="row g-4 align-items-stretch">
      <div class="col-lg-9">
        <div class="card data-card h-100">
          <div class="card-body">
            <div class="section-head">
              <div>
                <div class="section-kicker">Records</div>
                <div class="section-title">Application Tracking</div>
              </div>
            </div>

            <?php if ($recentApplications && mysqli_num_rows($recentApplications) > 0) { ?>
              <div class="table-responsive">
                <table class="table crm-table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Scholarship</th>
                      <th>Provider</th>
                      <th>Date Applied</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($app = mysqli_fetch_assoc($recentApplications)) { ?>
                      <?php
                        $status = $app['status'];
                        $cls = "status-other";
                        $label = strtoupper(str_replace("_", " ", $status));

                        if ($status === "submitted") $cls = "status-submitted";
                        elseif ($status === "under_review") $cls = "status-review";
                        elseif ($status === "approved") $cls = "status-approved";
                        elseif ($status === "rejected") $cls = "status-rejected";
                      ?>
                      <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($app['title']); ?></td>
                        <td><?php echo htmlspecialchars($app['provider_name']); ?></td>
                        <td><?php echo date("M d, Y", strtotime($app['created_at'])); ?></td>
                        <td>
                          <span class="status-pill <?php echo $cls; ?>">
                            <?php echo htmlspecialchars($label); ?>
                          </span>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } else { ?>
              <div class="empty-box">
                No application records found yet.
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="col-lg-3">
        <div class="card side-card h-100">
          <div class="card-body">
            <div class="section-kicker">Status</div>
            <div class="section-title">Application Status Summary</div>

            <div class="summary-list">
              <div class="summary-item">
                <span>Submitted / Under Review</span>
                <strong><?php echo $pending_applications; ?></strong>
              </div>
              <div class="summary-item">
                <span>Approved</span>
                <strong><?php echo $approved_applications; ?></strong>
              </div>
              <div class="summary-item">
                <span>Rejected</span>
                <strong><?php echo $rejected_applications; ?></strong>
              </div>
              <div class="summary-item">
                <span>Total Applications</span>
                <strong><?php echo $total_applications; ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>