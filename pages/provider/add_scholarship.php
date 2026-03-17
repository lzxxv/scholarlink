<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "info";

$verification_status = 'pending';

$checkProvider = mysqli_prepare($conn, "SELECT verification_status FROM provider_profiles WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($checkProvider, "i", $user_id);
mysqli_stmt_execute($checkProvider);
$providerResult = mysqli_stmt_get_result($checkProvider);

if ($providerResult && mysqli_num_rows($providerResult) === 1) {
    $provider = mysqli_fetch_assoc($providerResult);
    $verification_status = $provider['verification_status'] ?? 'pending';
}

$_SESSION['verification_status'] = $verification_status;

if ($verification_status !== 'verified') {
    require_once("../../includes/header.php");
    ?>
    <link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-add-scholarship.css">
    <div class="provider-add-page">
      <div class="container py-4">
        <div class="card provider-form-card">
          <div class="card-body">
            <div class="alert alert-warning mb-0">
              <strong>Posting Disabled</strong><br>
              Your provider account is still pending approval. You cannot post scholarships yet.
            </div>
            <div class="mt-3">
              <a href="dashboard.php" class="btn btn-provider-outline">Back to Dashboard</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    require_once("../../includes/footer.php");
    exit;
}

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    if ($title && $description && $eligibility && $deadline) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO scholarships (provider_id, title, description, eligibility, deadline, status)
             VALUES (?, ?, ?, ?, ?, 'open')"
        );
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $description, $eligibility, $deadline);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Scholarship added successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error adding scholarship.";
            $msg_type = "danger";
        }
    } else {
        $msg = "Please complete all fields.";
        $msg_type = "warning";
    }
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-add-scholarship.css">

<div class="provider-add-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Scholarship Form</span>
      <h2>Add Scholarship</h2>
      <p>Create a new scholarship listing for students to browse and apply to.</p>
    </div>

    <div class="card provider-form-card">
      <div class="card-body">
        <?php if ($msg) { ?>
          <div class="alert alert-<?php echo htmlspecialchars($msg_type); ?>">
            <?php echo htmlspecialchars($msg); ?>
          </div>
        <?php } ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Eligibility</label>
            <textarea name="eligibility" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['eligibility'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" name="deadline" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" required>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-provider-primary" name="submit">Save Scholarship</button>
            <a class="btn btn-provider-outline" href="scholarships.php">View My Scholarships</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>