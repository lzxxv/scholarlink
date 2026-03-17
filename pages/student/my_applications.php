<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$student_id = $_SESSION['user_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT a.id AS app_id, a.status, a.created_at,
            s.title, s.deadline,
            pp.organization_name AS provider_name
     FROM applications a
     JOIN scholarships s ON a.scholarship_id = s.id
     JOIN provider_profiles pp ON s.provider_id = pp.user_id
     WHERE a.student_id=?
     ORDER BY a.id DESC"
);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/my-applications.css">

<div class="my-applications-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Application Records</span>
      <h2>My Applications</h2>
      <p>View and track all scholarship applications submitted through your account.</p>
    </div>

    <div class="card applications-table-card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table apps-table align-middle mb-0">
            <thead>
              <tr>
                <th>Scholarship</th>
                <th>Provider</th>
                <th>Date Applied</th>
                <th>Deadline</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if(!$result || mysqli_num_rows($result) == 0){ ?>
                <tr>
                  <td colspan="5" class="text-center py-4">
                    <div class="empty-inline">No applications yet.</div>
                  </td>
                </tr>
              <?php } ?>

              <?php while($row = mysqli_fetch_assoc($result)){ ?>
                <?php
                  $st = $row['status'];
                  $cls = 'status-other';
                  $label = strtoupper(str_replace('_', ' ', $st));

                  if ($st === 'submitted') $cls = 'status-submitted';
                  elseif ($st === 'under_review') $cls = 'status-review';
                  elseif ($st === 'approved') $cls = 'status-approved';
                  elseif ($st === 'rejected') $cls = 'status-rejected';
                ?>
                <tr>
                  <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                  <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                  <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                  <td><?php echo htmlspecialchars($row['deadline']); ?></td>
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
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>