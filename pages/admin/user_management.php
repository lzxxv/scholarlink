<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

$message = "";
$message_type = "info";

$view = $_GET['view'] ?? 'all';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') {
        $message = "Provider account approved successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'rejected') {
        $message = "Provider account rejected successfully.";
        $message_type = "warning";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "User deleted successfully.";
        $message_type = "danger";
    }
}

/* DELETE USER */
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $user_id = (int) $_GET['id'];

    if ($user_id > 0 && $action === 'delete') {
        $checkRole = mysqli_prepare($conn, "SELECT role FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($checkRole, "i", $user_id);
        mysqli_stmt_execute($checkRole);
        $roleRes = mysqli_stmt_get_result($checkRole);
        $roleRow = $roleRes ? mysqli_fetch_assoc($roleRes) : null;

        if ($roleRow && $roleRow['role'] !== 'admin') {
            mysqli_begin_transaction($conn);

            try {
                if ($roleRow['role'] === 'student') {
                    $d1 = mysqli_prepare($conn, "DELETE FROM application_notes WHERE application_id IN (SELECT id FROM applications WHERE student_id=?)");
                    mysqli_stmt_bind_param($d1, "i", $user_id);
                    mysqli_stmt_execute($d1);

                    $d2 = mysqli_prepare($conn, "DELETE FROM applications WHERE student_id=?");
                    mysqli_stmt_bind_param($d2, "i", $user_id);
                    mysqli_stmt_execute($d2);

                    $d3 = mysqli_prepare($conn, "DELETE FROM student_profiles WHERE user_id=?");
                    mysqli_stmt_bind_param($d3, "i", $user_id);
                    mysqli_stmt_execute($d3);
                }

                if ($roleRow['role'] === 'provider') {
                    $d1 = mysqli_prepare($conn, "DELETE FROM application_notes WHERE provider_id=?");
                    mysqli_stmt_bind_param($d1, "i", $user_id);
                    mysqli_stmt_execute($d1);

                    $d2 = mysqli_prepare(
                        $conn,
                        "DELETE a FROM applications a
                         JOIN scholarships s ON a.scholarship_id = s.id
                         WHERE s.provider_id=?"
                    );
                    mysqli_stmt_bind_param($d2, "i", $user_id);
                    mysqli_stmt_execute($d2);

                    $d3 = mysqli_prepare($conn, "DELETE FROM scholarships WHERE provider_id=?");
                    mysqli_stmt_bind_param($d3, "i", $user_id);
                    mysqli_stmt_execute($d3);

                    $d4 = mysqli_prepare($conn, "DELETE FROM provider_profiles WHERE user_id=?");
                    mysqli_stmt_bind_param($d4, "i", $user_id);
                    mysqli_stmt_execute($d4);
                }

                $d5 = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role IN ('student','provider')");
                mysqli_stmt_bind_param($d5, "i", $user_id);
                mysqli_stmt_execute($d5);

                mysqli_commit($conn);

                header("Location: user_management.php?view=" . urlencode($view) . "&msg=deleted");
                exit();
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $message = "Failed to delete user.";
                $message_type = "danger";
            }
        }
    }
}

/* FILTERS */
$where = "WHERE 1=1";

if ($view === 'students') {
    $where .= " AND u.role='student'";
} elseif ($view === 'providers') {
    $where .= " AND u.role='provider'";
}

/* LOAD USERS */
$sql = "SELECT
            u.id,
            u.email,
            u.role,
            u.status,
            u.created_at,
            sp.first_name,
            sp.last_name,
            pp.organization_name,
            pp.verification_status,
            pp.verification_note,
            pp.supporting_document
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        LEFT JOIN provider_profiles pp ON u.id = pp.user_id
        $where
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/admin-user-management.css">

<div class="admin-user-page">
    <div class="container py-4">

        <div class="page-heading mb-4">
            <span class="page-badge">Admin Tools</span>
            <h2>User Management</h2>
            <p>Manage all student and provider accounts .</p>
        </div>

        <?php if ($message !== "") { ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> shadow-sm mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

       <div class="mb-4 d-flex flex-wrap gap-2">
    <a href="user_management.php?view=all" class="btn <?php echo $view === 'all' ? 'btn-green' : 'btn-green-outline'; ?>">All Users</a>
    <a href="user_management.php?view=students" class="btn <?php echo $view === 'students' ? 'btn-green' : 'btn-green-outline'; ?>">Students</a>
    <a href="user_management.php?view=providers" class="btn <?php echo $view === 'providers' ? 'btn-green' : 'btn-green-outline'; ?>">Providers</a>
</div>

        <div class="card user-table-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table user-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name / Organization</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Account Status</th>
                                <th>Verification</th>
                                <th>Document</th>
                                <th>Date Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$result || mysqli_num_rows($result) === 0) { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="empty-inline">No users found.</div>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <?php
                                    $display_name = 'Unnamed User';

                                    if ($row['role'] === 'student') {
                                        $full = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                        $display_name = $full !== '' ? $full : 'Student';
                                    } elseif ($row['role'] === 'provider') {
                                        $display_name = trim($row['organization_name'] ?? '') !== '' ? $row['organization_name'] : 'Provider';
                                    } elseif ($row['role'] === 'admin') {
                                        $display_name = 'Administrator';
                                    }

                                    $status_class = 'status-other';
                                    if ($row['status'] === 'active') $status_class = 'status-active';
                                    elseif ($row['status'] === 'pending') $status_class = 'status-pending';
                                    elseif ($row['status'] === 'inactive') $status_class = 'status-inactive';
                                    elseif ($row['status'] === 'rejected') $status_class = 'status-inactive';

                                    $verification = 'N/A';

                                    if ($row['role'] === 'provider') {
                                        if (!empty($row['verification_status'])) {
                                            $verification = $row['verification_status'];
                                        } else {
                                            $verification = !empty($row['supporting_document']) ? 'pending' : 'not_verified';
                                        }
                                    }

                                    $verification_label = 'N/A';
                                    if ($verification === 'not_verified') {
                                        $verification_label = 'Not Verified';
                                    } elseif ($verification === 'pending') {
                                        $verification_label = 'Pending';
                                    } elseif ($verification === 'verified') {
                                        $verification_label = 'Verified';
                                    } elseif ($verification === 'rejected') {
                                        $verification_label = 'Rejected';
                                    }

                                    $can_approve = (
                                        $row['role'] === 'provider' &&
                                        !empty($row['supporting_document']) &&
                                        ($verification === 'pending' || $verification === 'rejected')
                                    );

                                    $can_reject = (
                                        $row['role'] === 'provider' &&
                                        !empty($row['supporting_document']) &&
                                        $verification === 'pending'
                                    );
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($display_name); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <span class="role-pill"><?php echo htmlspecialchars(strtoupper($row['role'])); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-pill <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars(strtoupper($row['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['role'] === 'provider') { ?>
                                                <span class="verification-pill verification-<?php echo htmlspecialchars($verification); ?>">
                                                    <?php echo htmlspecialchars($verification_label); ?>
                                                </span>
                                            <?php } else { ?>
                                                <span class="text-muted">N/A</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($row['role'] === 'provider' && !empty($row['supporting_document'])) { ?>
                                                <a href="/scholarlink/uploads/provider_docs/<?php echo rawurlencode($row['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    View File
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted">No file</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php if ($row['role'] !== 'admin') { ?>
                                                <div class="action-wrap d-flex flex-wrap gap-2">

                                                    <?php if ($can_approve) { ?>
                                                        <a
                                                            class="btn btn-success btn-sm"
                                                            href="provider_action.php?id=<?php echo (int)$row['id']; ?>&action=approve"
                                                            onclick="return confirm('Approve this provider?')">
                                                            Approve
                                                        </a>
                                                    <?php } ?>

                                                    <?php if ($can_reject) { ?>
                                                        <a
                                                            class="btn btn-warning btn-sm"
                                                            href="provider_action.php?id=<?php echo (int)$row['id']; ?>&action=reject"
                                                            onclick="return confirm('Reject this provider?')">
                                                            Reject
                                                        </a>
                                                    <?php } ?>

                                                    <a
                                                        class="btn btn-danger btn-sm"
                                                        href="user_management.php?action=delete&id=<?php echo (int)$row['id']; ?>&view=<?php echo urlencode($view); ?>"
                                                        onclick="return confirm('Delete this user permanently?')">
                                                        Delete
                                                    </a>
                                                </div>
                                            <?php } else { ?>
                                                <span class="text-muted">Protected</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once("../../includes/footer.php"); ?>