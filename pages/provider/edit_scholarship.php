<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];
$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scholarship_id <= 0) {
    header("Location: scholarships.php");
    exit();
}

$msg = "";
$msg_type = "info";

/* Check ownership and fetch scholarship */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, title, description, eligibility, deadline, status
     FROM scholarships
     WHERE id = ? AND provider_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ii", $scholarship_id, $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Scholarship not found or access denied.");
}

$scholarship = mysqli_fetch_assoc($result);

if (isset($_POST['update_scholarship'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $status = $_POST['status'] ?? 'open';

    $allowed_status = ['open', 'closed'];

    if ($title === '' || $description === '' || $eligibility === '' || $deadline === '') {
        $msg = "Please complete all fields.";
        $msg_type = "warning";
    } elseif (!in_array($status, $allowed_status, true)) {
        $msg = "Invalid scholarship status.";
        $msg_type = "danger";
    } else {
        $update = mysqli_prepare(
            $conn,
            "UPDATE scholarships
             SET title = ?, description = ?, eligibility = ?, deadline = ?, status = ?
             WHERE id = ? AND provider_id = ?"
        );
        mysqli_stmt_bind_param(
            $update,
            "sssssii",
            $title,
            $description,
            $eligibility,
            $deadline,
            $status,
            $scholarship_id,
            $provider_id
        );

        if (mysqli_stmt_execute($update)) {
            $msg = "Scholarship updated successfully.";
            $msg_type = "success";

            $scholarship['title'] = $title;
            $scholarship['description'] = $description;
            $scholarship['eligibility'] = $eligibility;
            $scholarship['deadline'] = $deadline;
            $scholarship['status'] = $status;
        } else {
            $msg = "Failed to update scholarship.";
            $msg_type = "danger";
        }
    }
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-add-scholarship.css">

<div class="provider-add-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Scholarship Form</span>
      <h2>Edit Scholarship</h2>
      <p>Update your scholarship details and application availability.</p>
    </div>

    <div class="card provider-form-card">
      <div class="card-body">
        <?php if ($msg !== '') { ?>
          <div class="alert alert-<?php echo htmlspecialchars($msg_type); ?>">
            <?php echo htmlspecialchars($msg); ?>
          </div>
        <?php } ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input
              type="text"
              name="title"
              class="form-control"
              value="<?php echo htmlspecialchars($scholarship['title']); ?>"
              required
            >
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($scholarship['description']); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Eligibility</label>
            <textarea name="eligibility" class="form-control" rows="3" required><?php echo htmlspecialchars($scholarship['eligibility']); ?></textarea>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Deadline</label>
              <input
                type="date"
                name="deadline"
                class="form-control"
                value="<?php echo htmlspecialchars($scholarship['deadline']); ?>"
                required
              >
            </div>

            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-control" required>
                <option value="open" <?php if ($scholarship['status'] === 'open') echo 'selected'; ?>>Open</option>
                <option value="closed" <?php if ($scholarship['status'] === 'closed') echo 'selected'; ?>>Closed</option>
              </select>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" name="update_scholarship" class="btn btn-provider-primary">Save Changes</button>
            <a href="scholarships.php" class="btn btn-provider-outline">Back to My Scholarships</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>