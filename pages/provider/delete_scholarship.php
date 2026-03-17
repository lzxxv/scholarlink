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

/* Check ownership */
$check = mysqli_prepare(
    $conn,
    "SELECT id FROM scholarships WHERE id = ? AND provider_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($check, "ii", $scholarship_id, $provider_id);
mysqli_stmt_execute($check);
$checkResult = mysqli_stmt_get_result($check);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    header("Location: scholarships.php");
    exit();
}

/* Check if scholarship already has applications */
$appCheck = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total FROM applications WHERE scholarship_id = ?"
);
mysqli_stmt_bind_param($appCheck, "i", $scholarship_id);
mysqli_stmt_execute($appCheck);
$appResult = mysqli_stmt_get_result($appCheck);
$appRow = mysqli_fetch_assoc($appResult);
$totalApplications = (int)($appRow['total'] ?? 0);

if ($totalApplications > 0) {
    header("Location: scholarships.php?msg=has_applications");
    exit();
}

/* Delete scholarship */
$delete = mysqli_prepare(
    $conn,
    "DELETE FROM scholarships WHERE id = ? AND provider_id = ?"
);
mysqli_stmt_bind_param($delete, "ii", $scholarship_id, $provider_id);

if (mysqli_stmt_execute($delete)) {
    header("Location: scholarships.php?msg=deleted");
    exit();
} else {
    header("Location: scholarships.php?msg=error");
    exit();
}