<?php
require_once("../../config/auth.php");
require_login();
require_role("admin");

require_once("../../includes/header.php");
?>

<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-1">Admin Dashboard</h4>
        <p class="text-muted mb-0">Manage providers, monitor scholarships, and view system reports.</p>
      </div>
    </div>
  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>