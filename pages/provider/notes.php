<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$sql = "SELECT a.id AS app_id, a.status, a.created_at,
               s.title AS scholarship_title,
               CONCAT(sp.first_name, ' ', sp.last_name) AS student_name,
               u.email AS student_email
        FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        JOIN users u ON a.student_id = u.id
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        WHERE s.provider_id = ?
        ORDER BY a.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-applicants.css">

<div class="provider-applicants-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Application Records</span>
      <h2>Applicants</h2>
      <p>Manage student applications, update statuses, and access provider notes.</p>
    </div>

    <div class="card provider-table-card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table provider-table align-middle mb-0">
            <thead>
              <tr>
                <th>Scholarship</th>
                <th>Student</th>
                <th>Email</th>
                <th>Status</th>
                <th style="width: 220px;">Update</th>
                <th style="width: 90px;">Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if(mysqli_num_rows($result) == 0){ ?>
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <div class="empty-inline">No applicants yet.</div>
                  </td>
                </tr>
              <?php } ?>

              <?php while($row = mysqli_fetch_assoc($result)){ ?>
                <tr>
                  <td class="fw-semibold"><?php echo htmlspecialchars($row['scholarship_title']); ?></td>
                  <td><?php echo htmlspecialchars(trim($row['student_name']) ?: 'Student'); ?></td>
                  <td><?php echo htmlspecialchars($row['student_email']); ?></td>
                  <td>
                    <?php
                      $st = $row['status'];
                      $cls = 'status-other';
                      $label = strtoupper(str_replace('_', ' ', $st));

                      if ($st === 'submitted') $cls = 'status-submitted';
                      elseif ($st === 'under_review') $cls = 'status-review';
                      elseif ($st === 'approved') $cls = 'status-approved';
                      elseif ($st === 'rejected') $cls = 'status-rejected';
                    ?>
                    <span class="status-pill <?php echo $cls; ?>">
                      <?php echo htmlspecialchars($label); ?>
                    </span>
                  </td>
                  <td>
                    <form class="d-flex gap-2" method="POST" action="update_status.php">
                      <input type="hidden" name="app_id" value="<?php echo (int)$row['app_id']; ?>">
                      <select class="form-select form-select-sm" name="status" required>
                        <option value="submitted" <?php if($row['status']=='submitted') echo 'selected'; ?>>Submitted</option>
                        <option value="under_review" <?php if($row['status']=='under_review') echo 'selected'; ?>>Under Review</option>
                        <option value="approved" <?php if($row['status']=='approved') echo 'selected'; ?>>Approved</option>
                        <option value="rejected" <?php if($row['status']=='rejected') echo 'selected'; ?>>Rejected</option>
                      </select>
                      <button class="btn btn-provider-dark btn-sm">Save</button>
                    </form>
                  </td>
                  <td>
                    <a class="btn btn-sm btn-provider-outline"
                       href="notes.php?app_id=<?php echo (int)$row['app_id']; ?>">
                      Notes
                    </a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>