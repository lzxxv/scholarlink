<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header("Location: user_management.php?view=providers");
    exit();
}

$user_status = ($action === 'approve') ? 'active' : 'inactive';
$verification_status = ($action === 'approve') ? 'verified' : 'rejected';

mysqli_begin_transaction($conn);

try {
    $stmt1 = mysqli_prepare($conn, "UPDATE users SET status=? WHERE id=? AND role='provider'");
    mysqli_stmt_bind_param($stmt1, "si", $user_status, $id);
    mysqli_stmt_execute($stmt1);

    $stmt2 = mysqli_prepare(
        $conn,
        "UPDATE provider_profiles
         SET verification_status=?, verification_note=NULL
         WHERE user_id=?"
    );
    mysqli_stmt_bind_param($stmt2, "si", $verification_status, $id);
    mysqli_stmt_execute($stmt2);

    mysqli_commit($conn);

    $msg = ($action === 'approve') ? 'approved' : 'rejected';
    header("Location: user_management.php?view=providers&msg=" . $msg);
    exit();
} catch (Throwable $e) {
    mysqli_rollback($conn);
    die("Provider action failed: " . $e->getMessage());
}
?>