<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

if (isset($_GET['toggle_id'])) {
    $id = (int) $_GET['toggle_id'];

    $stmt = mysqli_prepare($conn, "SELECT status FROM scholarships WHERE id=? AND provider_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $provider_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $new = ($row['status'] === 'open') ? 'closed' : 'open';
        $upd = mysqli_prepare($conn, "UPDATE scholarships SET status=? WHERE id=? AND provider_id=?");
        mysqli_stmt_bind_param($upd, "sii", $new, $id, $provider_id);
        mysqli_stmt_execute($upd);
    }

    header("Location: scholarships.php");
    exit();
}

$stmt = mysqli_prepare($conn,
    "SELECT id, title, deadline, status, created_at
     FROM scholarships
     WHERE provider_id=?
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_scholarships = 0;
$active_scholarships = 0;
$total_applicants = 0;

$stat1 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=?");
mysqli_stmt_bind_param($stat1, "i", $provider_id);
mysqli_stmt_execute($stat1);
$stat1_res = mysqli_stmt_get_result($stat1);
if ($row = mysqli_fetch_assoc($stat1_res)) {
    $total_scholarships = (int)$row['total'];
}

$stat2 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=? AND status='open'");
mysqli_stmt_bind_param($stat2, "i", $provider_id);
mysqli_stmt_execute($stat2);
$stat2_res = mysqli_stmt_get_result($stat2);
if ($row = mysqli_fetch_assoc($stat2_res)) {
    $active_scholarships = (int)$row['total'];
}

$stat3 = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     JOIN scholarships s ON a.scholarship_id = s.id
     WHERE s.provider_id=?"
);
mysqli_stmt_bind_param($stat3, "i", $provider_id);
mysqli_stmt_execute($stat3);
$stat3_res = mysqli_stmt_get_result($stat3);
if ($row = mysqli_fetch_assoc($stat3_res)) {
    $total_applicants = (int)$row['total'];
}

require_once("../../includes/header.php");
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
  <div>
    <span class="sl-section-tag">Provider Panel</span>
    <h1 class="sl-page-title">My Scholarships</h1>
    <p class="sl-page-subtitle mb-0">
      Manage your scholarship postings, deadlines, and applicants in one place.
    </p>
  </div>

  <div class="text-lg-end">
    <a class="btn btn-sl-primary" href="add_scholarship.php">+ Add Scholarship</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="sl-stat-card sl-stat-accent-dark">
      <div class="sl-stat-label">Total Scholarships</div>
      <div class="sl-stat-number"><?php echo $total_scholarships; ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="sl-stat-card sl-stat-accent-teal">
      <div class="sl-stat-label">Active Scholarships</div>
      <div class="sl-stat-number"><?php echo $active_scholarships; ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="sl-stat-card sl-stat-accent-yellow">
      <div class="sl-stat-label">Total Applicants</div>
      <div class="sl-stat-number"><?php echo $total_applicants; ?></div>
    </div>
  </div>
</div>

<div class="sl-main-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="sl-card-title">Scholarship Listings</h2>
      <p class="sl-card-subtitle">View and manage all your posted scholarships.</p>
    </div>
  </div>

  <?php if(mysqli_num_rows($result) == 0){ ?>
    <div class="sl-empty">
      <div class="sl-empty-icon">🎓</div>
      <div class="sl-empty-title">No scholarships posted yet</div>
      <p class="sl-empty-text">
        Start creating your first scholarship opportunity and manage applications here.
      </p>
      <a class="btn btn-sl-primary" href="add_scholarship.php">+ Add Scholarship</a>
    </div>
  <?php } else { ?>
    <div class="table-responsive">
      <table class="table sl-table align-middle">
        <thead>
          <tr>
            <th>Title</th>
            <th>Deadline</th>
            <th>Status</th>
            <th style="width: 170px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($result)){ ?>
            <tr>
              <td class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo htmlspecialchars(date("F d, Y", strtotime($row['deadline']))); ?></td>
              <td>
                <?php if($row['status'] === 'open'){ ?>
                  <span class="sl-badge sl-badge-open">OPEN</span>
                <?php } else { ?>
                  <span class="sl-badge sl-badge-closed">CLOSED</span>
                <?php } ?>
              </td>
              <td>
                <a class="btn btn-sm btn-sl-outline sl-btn-sm"
                   href="scholarships.php?toggle_id=<?php echo (int)$row['id']; ?>"
                   onclick="return confirm('Change scholarship status?')">
                   Toggle Status
                </a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</div>

<?php require_once("../../includes/footer.php"); ?>