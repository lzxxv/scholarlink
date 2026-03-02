<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$sql = "SELECT s.id, s.title, s.description, s.eligibility, s.deadline, s.status,
               u.full_name AS provider_name
        FROM scholarships s
        JOIN users u ON s.provider_id = u.id
        WHERE s.status='open'
        ORDER BY s.created_at DESC";

$result = mysqli_query($conn, $sql);

require_once("../../includes/header.php");
?>

<h4 class="mb-3">Browse Scholarships</h4>

<?php if(mysqli_num_rows($result) == 0){ ?>
  <div class="alert alert-warning">No OPEN scholarships available yet.</div>
<?php } ?>

<div class="row g-3">
  <?php while($row = mysqli_fetch_assoc($result)){ ?>
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-1"><?php echo htmlspecialchars($row['title']); ?></h5>
          <div class="text-muted small mb-2">Provider: <?php echo htmlspecialchars($row['provider_name']); ?></div>

          <div class="mb-2">
            <div class="fw-semibold">Description</div>
            <div><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
          </div>

          <div class="mb-2">
            <div class="fw-semibold">Eligibility</div>
            <div><?php echo nl2br(htmlspecialchars($row['eligibility'])); ?></div>
          </div>

          <div class="mb-3">
            <span class="badge bg-success">OPEN</span>
            <span class="ms-2"><strong>Deadline:</strong> <?php echo htmlspecialchars($row['deadline']); ?></span>
          </div>

          <a class="btn btn-primary btn-sm" href="apply.php?id=<?php echo (int)$row['id']; ?>">
            Apply
          </a>
        </div>
      </div>
    </div>
  <?php } ?>
</div>

<?php require_once("../../includes/footer.php"); ?>