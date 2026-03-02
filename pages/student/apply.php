<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$student_id = $_SESSION['user_id'];
$scholarship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($scholarship_id <= 0) {
    header("Location: scholarships.php");
    exit();
}

// prevent duplicate apply
$check = mysqli_prepare($conn, "SELECT id FROM applications WHERE scholarship_id=? AND student_id=?");
mysqli_stmt_bind_param($check, "ii", $scholarship_id, $student_id);
mysqli_stmt_execute($check);
$res = mysqli_stmt_get_result($check);

$msg = "";
if (mysqli_fetch_assoc($res)) {
    $msg = "You already applied for this scholarship.";
} else {
    $stmt = mysqli_prepare($conn, "INSERT INTO applications (scholarship_id, student_id, status) VALUES (?, ?, 'submitted')");
    mysqli_stmt_bind_param($stmt, "ii", $scholarship_id, $student_id);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Application submitted successfully!";
    } else {
        $msg = "Error submitting application.";
    }
}

require_once("../../includes/header.php");
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-2">Apply</h4>
    <div class="alert alert-info mb-3"><?php echo htmlspecialchars($msg); ?></div>
    <a href="scholarships.php" class="btn btn-secondary">Back to Browse</a>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>