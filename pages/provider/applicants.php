<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$sql = "SELECT a.id AS app_id, a.status, a.created_at,
               s.title AS scholarship_title,
               u.full_name AS student_name, u.email AS student_email
        FROM applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        JOIN users u ON a.student_id = u.id
        WHERE s.provider_id = ?
        ORDER BY a.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once("../../includes/header.php");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Applicants</h4>
    <div class="text-muted">Manage student applications</div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
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
            <tr><td colspan="5" class="text-center text-muted py-4">No applicants yet.</td></tr>
          <?php } ?>

          <?php while($row = mysqli_fetch_assoc($result)){ ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($row['scholarship_title']); ?></td>
              <td><?php echo htmlspecialchars($row['student_name']); ?></td>
              <td><?php echo htmlspecialchars($row['student_email']); ?></td>
              <td>
                <?php
                  $st = $row['status'];
                  if ($st === 'submitted') echo '<span class="badge bg-primary">SUBMITTED</span>';
                  elseif ($st === 'under_review') echo '<span class="badge bg-warning text-dark">UNDER REVIEW</span>';
                  elseif ($st === 'approved') echo '<span class="badge bg-success">APPROVED</span>';
                  elseif ($st === 'rejected') echo '<span class="badge bg-danger">REJECTED</span>';
                  else echo htmlspecialchars($st);
                ?>
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
                  <button class="btn btn-sm btn-dark">Save</button>
                </form>
              </td>
            <td>
             <a class="btn btn-sm btn-outline-primary"
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

<?php require_once("../../includes/footer.php"); ?>