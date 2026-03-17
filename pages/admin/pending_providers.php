<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

$message = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') $message = "Provider account approved successfully.";
    if ($_GET['msg'] === 'rejected') $message = "Provider account rejected successfully.";
}

$sql = "SELECT u.id, u.email, u.status, u.created_at,
               pp.organization_name AS provider_name,
               pp.verification_status,
               pp.supporting_document
        FROM users u
        LEFT JOIN provider_profiles pp ON u.id = pp.user_id
        WHERE u.role='provider'
          AND pp.verification_status='pending'
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);

if ($result === false) {
    die("Query failed: " . mysqli_error($conn));
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/admin-pending-providers.css">

<div class="pending-providers-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Admin Review</span>
      <h2>Pending Providers</h2>
      <p>Review provider registrations and approve or reject access requests.</p>
    </div>

    <?php if ($message !== "") { ?>
      <div class="alert success-alert mb-4">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php } ?>

    <div class="card pending-table-card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table pending-table align-middle mb-0">
            <thead>
              <tr>
                <th>Organization</th>
                <th>Email</th>
                <th>Verification</th>
                <th>Document</th>
                <th>Account</th>
                <th>Date Registered</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($result) === 0) { ?>
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <div class="empty-inline">No pending provider accounts.</div>
                  </td>
                </tr>
              <?php } else { ?>
                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                  <tr>
                    <td class="fw-semibold">
                      <?php echo htmlspecialchars($row['provider_name'] ?: 'Unnamed Provider'); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                      <span class="status-pill status-verification">
                        <?php echo htmlspecialchars($row['verification_status'] ?: 'pending'); ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($row['supporting_document'])) { ?>
                        <a href="/scholarlink/uploads/provider_docs/<?php echo rawurlencode($row['supporting_document']); ?>" target="_blank" class="btn btn-sm btn-provider-outline">
                          View File
                        </a>
                      <?php } else { ?>
                        <span class="text-muted">No file</span>
                      <?php } ?>
                    </td>
                    <td>
                      <span class="status-pill status-pending">
                        <?php echo htmlspecialchars($row['status']); ?>
                      </span>
                    </td>
                    <td><?php echo date("M d, Y", strtotime($row['created_at'])); ?></td>
                    <td>
                      <div class="action-wrap">
                        <a
                          class="btn btn-approve btn-sm"
                          href="provider_action.php?id=<?php echo (int)$row['id']; ?>&action=approve"
                          onclick="return confirm('Approve this provider?')"
                        >
                          Approve
                        </a>

                        <a
                          class="btn btn-reject btn-sm"
                          href="provider_action.php?id=<?php echo (int)$row['id']; ?>&action=reject"
                          onclick="return confirm('Reject this provider?')"
                        >
                          Reject
                        </a>
                      </div>
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