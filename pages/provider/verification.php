<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "info";

$provider = [
    'organization_name' => '',
    'verification_status' => 'not_verified',
    'verification_note' => '',
    'supporting_document' => ''
];

$stmt = mysqli_prepare(
    $conn,
    "SELECT organization_name, verification_status, verification_note, supporting_document
     FROM provider_profiles
     WHERE user_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $provider = mysqli_fetch_assoc($result);

    if (empty($provider['verification_status'])) {
        $provider['verification_status'] = !empty($provider['supporting_document']) ? 'pending' : 'not_verified';
    }
} else {
    $msg = "Provider profile not found.";
    $msg_type = "danger";
}

if (isset($_POST['submit_verification'])) {
    if (isset($_FILES['supporting_document']) && $_FILES['supporting_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../../uploads/provider_docs/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $tmp_name = $_FILES['supporting_document']['tmp_name'];
        $original_name = basename($_FILES['supporting_document']['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed, true)) {
            $msg = "Only PDF, JPG, JPEG, and PNG files are allowed.";
            $msg_type = "warning";
        } else {
            $new_document_name = "provider_" . $user_id . "_" . time() . "." . $ext;

            if (move_uploaded_file($tmp_name, $upload_dir . $new_document_name)) {
                $update = mysqli_prepare(
                    $conn,
                    "UPDATE provider_profiles
                     SET supporting_document = ?,
                         verification_status = 'pending',
                         verification_note = NULL
                     WHERE user_id = ?"
                );
                mysqli_stmt_bind_param($update, "si", $new_document_name, $user_id);

                if (mysqli_stmt_execute($update)) {
                    $msg = "Verification submitted successfully. Your account is now pending admin review.";
                    $msg_type = "success";

                    $provider['supporting_document'] = $new_document_name;
                    $provider['verification_status'] = 'pending';
                    $provider['verification_note'] = '';
                    $_SESSION['verification_status'] = 'pending';
                } else {
                    $msg = "Failed to save verification record.";
                    $msg_type = "danger";
                }
            } else {
                $msg = "Failed to upload supporting document.";
                $msg_type = "danger";
            }
        }
    } else {
        $msg = "Please upload a supporting document.";
        $msg_type = "warning";
    }
}

$status_label = $provider['verification_status'] ?? 'not_verified';
if ($status_label === 'not_verified') {
    $status_label = 'Not Verified';
} elseif ($status_label === 'pending') {
    $status_label = 'Pending';
} elseif ($status_label === 'verified') {
    $status_label = 'Verified';
} elseif ($status_label === 'rejected') {
    $status_label = 'Rejected';
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-profile.css">

<div class="provider-profile-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Provider Verification</span>
      <h2>Verification</h2>
      <p>Upload your supporting document and submit your account for admin review.</p>
    </div>

    <div class="mb-4 d-flex gap-2 flex-wrap">
      <a href="profile.php" class="btn btn-provider-outline">Profile Settings</a>
      <a href="verification.php" class="btn btn-provider-primary">Verification</a>
    </div>

    <?php if (($provider['verification_status'] ?? 'not_verified') !== 'verified') : ?>
      <div class="alert alert-warning shadow-sm">
        <strong>Verification Status:</strong>
        <?php echo htmlspecialchars($status_label); ?><br>
        Upload or re-upload a document to submit your provider account for review.
        <?php if (!empty($provider['verification_note'])) : ?>
          <hr>
          <strong>Admin Note:</strong> <?php echo htmlspecialchars($provider['verification_note']); ?>
        <?php endif; ?>
      </div>
    <?php else : ?>
      <div class="alert alert-success shadow-sm">
        <strong>Verification Status:</strong> Verified<br>
        Your provider account is approved.
      </div>
    <?php endif; ?>

    <?php if ($msg !== '') : ?>
      <div class="alert alert-<?php echo htmlspecialchars($msg_type); ?> shadow-sm">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <div class="card profile-card">
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Organization</label>
            <input
              type="text"
              class="form-control"
              value="<?php echo htmlspecialchars($provider['organization_name'] ?? ''); ?>"
              disabled
            >
          </div>

          <div class="mb-3">
            <label class="form-label">Supporting Document</label>
            <input type="file" name="supporting_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            <div class="form-text">Accepted files: PDF, JPG, JPEG, PNG.</div>

            <?php if (!empty($provider['supporting_document'])) : ?>
              <div class="mt-2">
                Current file:
                <a href="/scholarlink/uploads/provider_docs/<?php echo rawurlencode($provider['supporting_document']); ?>" target="_blank">
                  View Uploaded Document
                </a>
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" name="submit_verification" class="btn btn-provider-primary">Submit Verification</button>
            <a href="dashboard.php" class="btn btn-provider-outline">Back to Dashboard</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>