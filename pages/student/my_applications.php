<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$student_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
  "SELECT a.id AS app_id, a.status, a.created_at,
          s.title, s.deadline,
          u.full_name AS provider_name
   FROM applications a
   JOIN scholarships s ON a.scholarship_id = s.id
   JOIN users u ON s.provider_id = u.id
   WHERE a.student_id=?
   ORDER BY a.id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once("../../includes/header.php");
?>

<h4 class="mb-3">My Applications</h4>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Scholarship</th>
            <th>Provider</th>
            <th>Deadline</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if(mysqli_num_rows($result) == 0){ ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No applications yet.</td></tr>
          <?php } ?>

          <?php while($row = mysqli_fetch_assoc($result)){ ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
              <td><?php echo htmlspecialchars($row['deadline']); ?></td>
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
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>