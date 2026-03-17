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
    'contact_person' => '',
    'phone_number' => '',
    'address' => '',
    'verification_status' => 'pending',
    'verification_note' => ''
];

$stmt = mysqli_prepare(
    $conn,
    "SELECT organization_name, contact_person, phone_number, address, verification_status, verification_note
     FROM provider_profiles
     WHERE user_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $provider = mysqli_fetch_assoc($result);
} else {
    $msg = "Provider profile not found.";
    $msg_type = "danger";
}

if (isset($_POST['save_profile'])) {
    $organization_name = trim($_POST['organization_name'] ?? '');
    $contact_person    = trim($_POST['contact_person'] ?? '');
    $phone_number      = trim($_POST['phone_number'] ?? '');
    $address           = trim($_POST['address'] ?? '');

    if ($organization_name === '' || $contact_person === '' || $phone_number === '' || $address === '') {
        $msg = "Please complete all profile fields.";
        $msg_type = "warning";
    } else {
        $update = mysqli_prepare(
            $conn,
            "UPDATE provider_profiles
             SET organization_name = ?, contact_person = ?, phone_number = ?, address = ?
             WHERE user_id = ?"
        );
        mysqli_stmt_bind_param(
            $update,
            "ssssi",
            $organization_name,
            $contact_person,
            $phone_number,
            $address,
            $user_id
        );

        if (mysqli_stmt_execute($update)) {
            $msg = "Profile updated successfully.";
            $msg_type = "success";

            $provider['organization_name'] = $organization_name;
            $provider['contact_person'] = $contact_person;
            $provider['phone_number'] = $phone_number;
            $provider['address'] = $address;
        } else {
            $msg = "Failed to update profile.";
            $msg_type = "danger";
        }
    }
}

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-profile.css">

<div class="provider-profile-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Provider Profile</span>
      <h2>Profile Settings</h2>
      <p>Manage your organization details and contact information.</p>
    </div>

    <div class="mb-4 d-flex gap-2 flex-wrap">
      <a href="profile.php" class="btn btn-provider-primary">Profile Settings</a>
      <a href="verification.php" class="btn btn-provider-outline">Verification</a>
    </div>

    <?php if ($provider['verification_status'] !== 'verified') : ?>
      <div class="alert alert-warning shadow-sm">
        <strong>Verification Status:</strong>
        <?php echo htmlspecialchars(ucfirst($provider['verification_status'])); ?><br>
        Your account can access the dashboard, but posting scholarships is disabled until admin approval.
        <?php if (!empty($provider['verification_note'])) : ?>
          <hr>
          <strong>Admin Note:</strong> <?php echo htmlspecialchars($provider['verification_note']); ?>
        <?php endif; ?>
      </div>
    <?php else : ?>
      <div class="alert alert-success shadow-sm">
        <strong>Verification Status:</strong> Verified<br>
        Your provider account is approved and can post scholarships.
      </div>
    <?php endif; ?>

    <?php if ($msg !== '') : ?>
      <div class="alert alert-<?php echo htmlspecialchars($msg_type); ?> shadow-sm">
        <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <div class="card profile-card">
      <div class="card-body">
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Organization Name</label>
              <input type="text" name="organization_name" class="form-control"
                     value="<?php echo htmlspecialchars($provider['organization_name'] ?? ''); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contact_person" class="form-control"
                     value="<?php echo htmlspecialchars($provider['contact_person'] ?? ''); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone_number" class="form-control"
                     value="<?php echo htmlspecialchars($provider['phone_number'] ?? ''); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Verification Status</label>
              <input type="text" class="form-control"
                     value="<?php echo htmlspecialchars(ucfirst($provider['verification_status'] ?? 'pending')); ?>" disabled>
            </div>

            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control" rows="4" required><?php echo htmlspecialchars($provider['address'] ?? ''); ?></textarea>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" name="save_profile" class="btn btn-provider-primary">Save Changes</button>
            <a href="dashboard.php" class="btn btn-provider-outline">Back to Dashboard</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>