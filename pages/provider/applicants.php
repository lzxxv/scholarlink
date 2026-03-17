<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];
$scholarship_id = isset($_GET['scholarship_id']) ? (int) $_GET['scholarship_id'] : 0;

$message = "";
$message_type = "info";

function get_status_class($status) {
    switch ($status) {
        case 'submitted':
            return 'status-submitted';
        case 'under_review':
            return 'status-review';
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        default:
            return 'status-other';
    }
}

function get_status_label($status) {
    return strtoupper(str_replace('_', ' ', (string)$status));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'], $_POST['scholarship_id'])) {
    $app_id = (int) $_POST['app_id'];
    $posted_scholarship_id = (int) $_POST['scholarship_id'];
    $new_status = trim($_POST['status']);

    $allowed_statuses = ['submitted', 'under_review', 'approved', 'rejected'];

    if (in_array($new_status, $allowed_statuses, true) && $posted_scholarship_id > 0) {
        $check_sql = "SELECT a.id
                      FROM applications a
                      JOIN scholarships s ON a.scholarship_id = s.id
                      WHERE a.id = ? AND a.scholarship_id = ? AND s.provider_id = ?
                      LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "iii", $app_id, $posted_scholarship_id, $provider_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $update_sql = "UPDATE applications SET status = ? WHERE id = ? AND scholarship_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sii", $new_status, $app_id, $posted_scholarship_id);
            mysqli_stmt_execute($update_stmt);

            header("Location: applicants.php?scholarship_id=" . $posted_scholarship_id . "&msg=updated");
            exit();
        } else {
            header("Location: applicants.php?scholarship_id=" . $posted_scholarship_id . "&msg=invalid");
            exit();
        }
    } else {
        header("Location: applicants.php?scholarship_id=" . $posted_scholarship_id . "&msg=invalid");
        exit();
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $message = "Application status updated successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'invalid') {
        $message = "Invalid application action.";
        $message_type = "danger";
    }
}

$scholarship = null;

if ($scholarship_id > 0) {
    $scholarship_sql = "SELECT id, title, deadline, status
                        FROM scholarships
                        WHERE id = ? AND provider_id = ?
                        LIMIT 1";
    $scholarship_stmt = mysqli_prepare($conn, $scholarship_sql);
    mysqli_stmt_bind_param($scholarship_stmt, "ii", $scholarship_id, $provider_id);
    mysqli_stmt_execute($scholarship_stmt);
    $scholarship_result = mysqli_stmt_get_result($scholarship_stmt);

    if ($scholarship_result && mysqli_num_rows($scholarship_result) > 0) {
        $scholarship = mysqli_fetch_assoc($scholarship_result);
    }
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-applicants.css?v=4">

<div class="provider-applicants-page">
  <div class="container py-4">

    <?php if (!$scholarship) { ?>
      <div class="page-heading mb-4">
        <span class="page-badge">Application Records</span>
        <h2>Applicants</h2>
        <p>Please select a scholarship first before viewing applications.</p>
      </div>

      <div class="card provider-table-card">
        <div class="card-body">
          <div class="empty-inline">
            No scholarship selected or the scholarship does not exist.
            <div class="mt-3">
              <a href="scholarships.php" class="btn btn-provider-outline btn-sm">← Back to My Scholarships</a>
            </div>
          </div>
        </div>
      </div>

    <?php } else { ?>

      <div class="page-heading mb-4">
        <span class="page-badge">Application Records</span>
        <div class="mb-3">
          <a href="scholarships.php" class="btn btn-provider-outline btn-sm">← Back to My Scholarships</a>
        </div>
        <h2>Applicants</h2>
        <p>
          Showing applications for
          <strong><?php echo htmlspecialchars($scholarship['title']); ?></strong>.
        </p>
      </div>

      <?php if ($message !== "") { ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> shadow-sm mb-4">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php } ?>

      <?php
      $sql = "SELECT 
                a.id AS app_id,
                a.status,
                a.created_at,
                CONCAT(COALESCE(sp.first_name, ''), ' ', COALESCE(sp.last_name, '')) AS student_name,
                u.email AS student_email
              FROM applications a
              JOIN scholarships s ON a.scholarship_id = s.id
              JOIN users u ON a.student_id = u.id
              LEFT JOIN student_profiles sp ON u.id = sp.user_id
              WHERE s.provider_id = ? AND a.scholarship_id = ?
              ORDER BY a.id DESC";

      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "ii", $provider_id, $scholarship_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      ?>

      <div class="card provider-table-card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table provider-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th style="width: 220px;">Update</th>
                  <th style="width: 90px;">Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$result || mysqli_num_rows($result) == 0) { ?>
                  <tr>
                    <td colspan="5" class="text-center py-4">
                      <div class="empty-inline">
                        No applicants yet for <strong><?php echo htmlspecialchars($scholarship['title']); ?></strong>.
                      </div>
                    </td>
                  </tr>
                <?php } ?>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                  <?php
                    $student_name = trim($row['student_name']);
                    if ($student_name === '') {
                        $student_name = 'Student';
                    }
                  ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($student_name); ?></td>
                    <td><?php echo htmlspecialchars($row['student_email']); ?></td>
                    <td>
                      <span class="status-pill <?php echo get_status_class($row['status']); ?>">
                        <?php echo htmlspecialchars(get_status_label($row['status'])); ?>
                      </span>
                    </td>
                    <td>
                      <form class="d-flex gap-2 align-items-center flex-wrap m-0" method="POST" action="">
                        <input type="hidden" name="app_id" value="<?php echo (int)$row['app_id']; ?>">
                        <input type="hidden" name="scholarship_id" value="<?php echo (int)$scholarship_id; ?>">

                        <select class="form-select form-select-sm" name="status" required>
                          <option value="submitted" <?php if ($row['status'] === 'submitted') echo 'selected'; ?>>Submitted</option>
                          <option value="under_review" <?php if ($row['status'] === 'under_review') echo 'selected'; ?>>Under Review</option>
                          <option value="approved" <?php if ($row['status'] === 'approved') echo 'selected'; ?>>Approved</option>
                          <option value="rejected" <?php if ($row['status'] === 'rejected') echo 'selected'; ?>>Rejected</option>
                        </select>

                        <button type="submit" class="btn btn-provider-dark btn-sm">Save</button>
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

    <?php } ?>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>