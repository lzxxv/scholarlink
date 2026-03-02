<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];
$app_id = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;

if ($app_id <= 0) {
    header("Location: applicants.php");
    exit();
}

// Check ownership: provider must own the scholarship for this application
$own = mysqli_prepare($conn,
  "SELECT a.id, a.status, s.title AS scholarship_title, u.full_name AS student_name
   FROM applications a
   JOIN scholarships s ON a.scholarship_id = s.id
   JOIN users u ON a.student_id = u.id
   WHERE a.id=? AND s.provider_id=?"
);
mysqli_stmt_bind_param($own, "ii", $app_id, $provider_id);
mysqli_stmt_execute($own);
$ownRes = mysqli_stmt_get_result($own);

if (!($info = mysqli_fetch_assoc($ownRes))) {
    die("Access denied.");
}

// Add note
$msg = "";
if (isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    if ($note !== "") {
        $stmt = mysqli_prepare($conn,
          "INSERT INTO application_notes (application_id, provider_id, note)
           VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iis", $app_id, $provider_id, $note);
        mysqli_stmt_execute($stmt);
        $msg = "Note added!";
    } else {
        $msg = "Note cannot be empty.";
    }
}

// Fetch notes
$stmt2 = mysqli_prepare($conn,
  "SELECT note, created_at
   FROM application_notes
   WHERE application_id=? AND provider_id=?
   ORDER BY id DESC"
);
mysqli_stmt_bind_param($stmt2, "ii", $app_id, $provider_id);
mysqli_stmt_execute($stmt2);
$notes = mysqli_stmt_get_result($stmt2);

require_once("../../includes/header.php");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Application Notes</h4>
    <div class="text-muted">
      Scholarship: <strong><?php echo htmlspecialchars($info['scholarship_title']); ?></strong> —
      Student: <strong><?php echo htmlspecialchars($info['student_name']); ?></strong>
    </div>
  </div>
  <a class="btn btn-outline-secondary" href="applicants.php">Back</a>
</div>

<?php if($msg){ ?>
  <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
<?php } ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Add Note</h6>
        <form method="POST">
          <textarea class="form-control mb-3" name="note" rows="5" placeholder="Type your remarks..." required></textarea>
          <button class="btn btn-dark" name="add_note">Save Note</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Notes History</h6>

        <?php if(mysqli_num_rows($notes) == 0){ ?>
          <div class="text-muted">No notes yet.</div>
        <?php } ?>

        <?php while($n = mysqli_fetch_assoc($notes)){ ?>
          <div class="border rounded p-3 mb-2 bg-light">
            <div class="small text-muted mb-1"><?php echo htmlspecialchars($n['created_at']); ?></div>
            <div><?php echo nl2br(htmlspecialchars($n['note'])); ?></div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>