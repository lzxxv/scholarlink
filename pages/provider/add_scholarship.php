<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$msg = "";

if (isset($_POST['submit'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $eligibility = trim($_POST['eligibility']);
    $deadline = $_POST['deadline'];

    if ($title && $description && $eligibility && $deadline) {
        $provider_id = $_SESSION['user_id'];

        $stmt = mysqli_prepare($conn,
            "INSERT INTO scholarships (provider_id, title, description, eligibility, deadline, status)
             VALUES (?, ?, ?, ?, ?, 'open')"
        );
        mysqli_stmt_bind_param($stmt, "issss", $provider_id, $title, $description, $eligibility, $deadline);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Scholarship added successfully!";
        } else {
            $msg = "Error adding scholarship.";
        }
    } else {
        $msg = "Please complete all fields.";
    }
}

require_once("../../includes/header.php");
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Add Scholarship</h4>

        <?php if($msg){ ?>
          <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
        <?php } ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Eligibility</label>
            <textarea name="eligibility" class="form-control" rows="3" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Deadline</label>
            <input type="date" name="deadline" class="form-control" required>
          </div>

          <button class="btn btn-primary" name="submit">Save Scholarship</button>
          <a class="btn btn-outline-secondary" href="scholarships.php">View My Scholarships</a>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>