<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : "";

if ($id <= 0 || !in_array($action, ["approve", "reject"], true)) {
    header("Location: pending_providers.php");
    exit();
}

$new_status = ($action === "approve") ? "active" : "rejected";

// update provider status
$stmt = mysqli_prepare($conn, "UPDATE users SET status=? WHERE id=? AND role='provider'");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "si", $new_status, $id);

if (!mysqli_stmt_execute($stmt)) {
    die("Execute failed: " . mysqli_stmt_error($stmt));
}

header("Location: pending_providers.php");
exit();