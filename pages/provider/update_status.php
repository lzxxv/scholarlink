<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$app_id = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : "";

$allowed = ["submitted","under_review","approved","rejected"];
if ($app_id <= 0 || !in_array($status, $allowed, true)) {
    header("Location: applicants.php");
    exit();
}

// Ensure provider owns the scholarship for this application
$sql = "UPDATE applications a
        JOIN scholarships s ON a.scholarship_id = s.id
        SET a.status = ?
        WHERE a.id = ? AND s.provider_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sii", $status, $app_id, $provider_id);
mysqli_stmt_execute($stmt);

header("Location: applicants.php");
exit();