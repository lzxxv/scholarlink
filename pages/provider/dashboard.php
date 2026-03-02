<?php
require_once("../../config/auth.php");
require_login();
require_role("provider");

require_once("../../includes/header.php");
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-1">Provider Dashboard</h4>
    <p class="text-muted mb-0">Create scholarships and manage applicants.</p>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>