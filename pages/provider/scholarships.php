<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$message = "";
$message_type = "info";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $message = "Scholarship deleted successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'has_applications') {
        $message = "This scholarship cannot be deleted because it already has applications.";
        $message_type = "warning";
    } elseif ($_GET['msg'] === 'error') {
        $message = "Failed to delete scholarship.";
        $message_type = "danger";
    }
}

/* Toggle scholarship status */
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

/* Scholarship list */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, title, deadline, status, created_at
     FROM scholarships
     WHERE provider_id=?
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/* Stats */
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

$stat3 = mysqli_prepare(
    $conn,
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

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-scholarships.css">

<div class="provider-scholarships-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Provider Panel</span>
      <h2>My Scholarships</h2>
      <p>Manage your scholarship postings, deadlines, and applicants in one place.</p>
    </div>

    <div class="page-actions mb-4">
      <a class="btn btn-provider-primary" href="add_scholarship.php">+ Add Scholarship</a>
    </div>

    <?php if ($message !== "") { ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> shadow-sm mb-4">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php } ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Total Scholarships</div>
          <div class="provider-stat-number"><?php echo $total_scholarships; ?></div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Active Scholarships</div>
          <div class="provider-stat-number"><?php echo $active_scholarships; ?></div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Total Applicants</div>
          <div class="provider-stat-number"><?php echo $total_applicants; ?></div>
        </div>
      </div>
    </div>

    <div class="card provider-table-card">
      <div class="card-body">
        <?php if (!$result || mysqli_num_rows($result) == 0) { ?>
          <div class="empty-inline">No scholarships posted yet.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table provider-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Deadline</th>
                  <th>Status</th>
                  <th style="width: 280px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars(date("F d, Y", strtotime($row['deadline']))); ?></td>
                    <td>
                      <?php if($row['status'] === 'open'){ ?>
                        <span class="status-pill status-open">OPEN</span>
                      <?php } else { ?>
                        <span class="status-pill status-closed">CLOSED</span>
                      <?php } ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-provider-outline btn-sm"
                           href="edit_scholarship.php?id=<?php echo (int)$row['id']; ?>">
                           Edit
                        </a>

                        <a class="btn btn-provider-outline btn-sm"
                           href="scholarships.php?toggle_id=<?php echo (int)$row['id']; ?>"
                           onclick="return confirm('Change scholarship status?')">
                           Toggle Status
                        </a>

                        <a class="btn btn-provider-danger btn-sm"
                           href="delete_scholarship.php?id=<?php echo (int)$row['id']; ?>"
                           onclick="return confirm('Are you sure you want to delete this scholarship?')">
                           Delete
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>