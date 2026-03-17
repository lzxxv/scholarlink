<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("student");

$sql = "SELECT s.id, s.title, s.description, s.eligibility, s.deadline, s.status,
               pp.organization_name AS provider_name
        FROM scholarships s
        JOIN provider_profiles pp ON s.provider_id = pp.user_id
        WHERE s.status='open'
        ORDER BY s.created_at DESC";

$result = mysqli_query($conn, $sql);
$total_open = $result ? mysqli_num_rows($result) : 0;

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/student-scholarships.css">

<div class="student-scholarships-page">
  <div class="container py-4">

    <div class="browse-hero mb-4">
      <div class="browse-hero-shape browse-hero-shape-1"></div>
      <div class="browse-hero-shape browse-hero-shape-2"></div>

      <div class="row align-items-center g-4 position-relative">
        <div class="col-lg-8">
          <span class="browse-badge">Scholarship Records</span>
          <h2>Browse Available Scholarships</h2>
          <p>
            Review current scholarship opportunities, compare provider offerings, and apply to programs that match your qualifications.
          </p>
        </div>

        <div class="col-lg-4">
          <div class="browse-summary-card">
            <div class="browse-summary-label">Open Records</div>
            <div class="browse-summary-value"><?php echo (int)$total_open; ?></div>
            <div class="browse-summary-text">Scholarships currently available for application</div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!$result || mysqli_num_rows($result) == 0) { ?>
      <div class="empty-box">
        No open scholarships available yet.
      </div>
    <?php } else { ?>
      <div class="row g-4">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
          <div class="col-xl-6">
            <div class="card scholarship-card h-100">
              <div class="card-body">
                <div class="scholarship-top">
                  <div>
                    <h5 class="scholarship-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <div class="scholarship-provider">
                      Provider: <?php echo htmlspecialchars($row['provider_name']); ?>
                    </div>
                  </div>
                  <span class="badge scholarship-badge">OPEN</span>
                </div>

                <div class="info-block">
                  <div class="info-label">Description</div>
                  <div class="info-text"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                </div>

                <div class="info-block">
                  <div class="info-label">Eligibility</div>
                  <div class="info-text"><?php echo nl2br(htmlspecialchars($row['eligibility'])); ?></div>
                </div>

                <div class="scholarship-footer">
                  <div class="deadline-text">
                    <span>Deadline</span>
                    <strong><?php echo htmlspecialchars($row['deadline']); ?></strong>
                  </div>

                  <a class="btn btn-apply-scholarship btn-sm" href="apply.php?id=<?php echo (int)$row['id']; ?>">
                    Apply Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>