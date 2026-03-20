<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("provider");

$provider_id = $_SESSION['user_id'];

$message = "";
$message_type = "info";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = "Scholarship created successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $message = "Scholarship updated successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $message = "Scholarship deleted successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] === 'has_applications') {
        $message = "This scholarship cannot be deleted because it already has applications.";
        $message_type = "warning";
    } elseif ($_GET['msg'] === 'slots_full') {
        $message = "This scholarship already has full slots.";
        $message_type = "warning";
    } elseif ($_GET['msg'] === 'error') {
        $message = "Something went wrong.";
        $message_type = "danger";
    }
}

/* Toggle scholarship status */
if (isset($_GET['toggle_id'])) {
    $id = (int) $_GET['toggle_id'];

    $stmt = mysqli_prepare($conn, "SELECT status FROM scholarships WHERE id=? AND provider_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $provider_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $new = ($row['status'] === 'open') ? 'closed' : 'open';
        $upd = mysqli_prepare($conn, "UPDATE scholarships SET status=? WHERE id=? AND provider_id=?");
        mysqli_stmt_bind_param($upd, "sii", $new, $id, $provider_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    mysqli_stmt_close($stmt);

    header("Location: scholarships.php");
    exit();
}

/* Scholarship list with applicant count + approved count + slots */
$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        s.id,
        s.title,
        s.benefit,
        s.location,
        s.total_slots,
        s.deadline,
        s.status,
        s.created_at,
        COUNT(a.id) AS applicant_count,
        COALESCE(SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count
     FROM scholarships s
     LEFT JOIN applications a ON a.scholarship_id = s.id
     WHERE s.provider_id=?
     GROUP BY s.id, s.title, s.benefit, s.location, s.total_slots, s.deadline, s.status, s.created_at
     ORDER BY s.created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/* Stats */
$total_scholarships = 0;
$active_scholarships = 0;
$total_applicants = 0;

$stat1 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=?");
mysqli_stmt_bind_param($stat1, "i", $provider_id);
mysqli_stmt_execute($stat1);
$stat1_res = mysqli_stmt_get_result($stat1);
if ($row = mysqli_fetch_assoc($stat1_res)) {
    $total_scholarships = (int)$row['total'];
}
mysqli_stmt_close($stat1);

$stat2 = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM scholarships WHERE provider_id=? AND status='open'");
mysqli_stmt_bind_param($stat2, "i", $provider_id);
mysqli_stmt_execute($stat2);
$stat2_res = mysqli_stmt_get_result($stat2);
if ($row = mysqli_fetch_assoc($stat2_res)) {
    $active_scholarships = (int)$row['total'];
}
mysqli_stmt_close($stat2);

$stat3 = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications a
     JOIN scholarships s ON a.scholarship_id = s.id
     WHERE s.provider_id=?"
);
mysqli_stmt_bind_param($stat3, "i", $provider_id);
mysqli_stmt_execute($stat3);
$stat3_res = mysqli_stmt_get_result($stat3);
if ($row = mysqli_fetch_assoc($stat3_res)) {
    $total_applicants = (int)$row['total'];
}
mysqli_stmt_close($stat3);

require_once("../../includes/header.php");
?>

<link rel="stylesheet" href="/scholarlink/pages/assets/css/provider-scholarships.css?v=<?php echo time(); ?>">

<div class="provider-scholarships-page">
  <div class="container py-4">

    <div class="page-heading mb-4">
      <span class="page-badge">Provider Panel</span>
      <h2>My Scholarships</h2>
      <p>Manage your scholarship postings, benefits, locations, deadlines, and applicants in one place.</p>
    </div>

    <div class="page-actions mb-4">
      <a class="btn btn-provider-primary" href="add_scholarship.php">+ Add Scholarship</a>
    </div>

    <?php if ($message !== "") { ?>
      <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> shadow-sm mb-4">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php } ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Total Scholarships</div>
          <div class="provider-stat-number"><?php echo $total_scholarships; ?></div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Active Scholarships</div>
          <div class="provider-stat-number"><?php echo $active_scholarships; ?></div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="provider-stat-card">
          <div class="provider-stat-label">Total Applicants</div>
          <div class="provider-stat-number"><?php echo $total_applicants; ?></div>
        </div>
      </div>
    </div>

    <div class="card provider-table-card">
      <div class="card-body">
        <?php if (!$result || mysqli_num_rows($result) == 0) { ?>
          <div class="empty-inline">No scholarships posted yet.</div>
        <?php } else { ?>
          <div class="table-responsive">
            <table class="table provider-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Benefit</th>
                  <th>Location</th>
                  <th>Deadline</th>
                  <th>Slots</th>
                  <th>Status</th>
                  <th>Applicants</th>
                  <th style="width: 220px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                  <?php
                    $total_slots = (int)($row['total_slots'] ?? 0);
                    $approved_count = (int)($row['approved_count'] ?? 0);
                    $available_slots = max(0, $total_slots - $approved_count);
                  ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>

                    <td>
                      <?php echo htmlspecialchars($row['benefit'] !== "" ? $row['benefit'] : "—"); ?>
                    </td>

                    <td>
                      <?php echo htmlspecialchars($row['location'] !== "" ? $row['location'] : "—"); ?>
                    </td>

                    <td>
                      <?php
                        if (!empty($row['deadline']) && $row['deadline'] !== '0000-00-00') {
                            echo htmlspecialchars(date("F d, Y", strtotime($row['deadline'])));
                        } else {
                            echo "—";
                        }
                      ?>
                    </td>

                    <td>
                      <span class="applicant-count-badge">
                        <?php echo $available_slots . "/" . $total_slots; ?>
                      </span>
                    </td>

                    <td>
                      <?php if ($row['status'] === 'open') { ?>
                        <span class="status-pill status-open">OPEN</span>
                      <?php } else { ?>
                        <span class="status-pill status-closed">CLOSED</span>
                      <?php } ?>
                    </td>

                    <td>
                      <span class="applicant-count-badge">
                        <?php echo (int)$row['applicant_count']; ?>
                      </span>
                    </td>

                    <td>
                      <div class="action-icons-wrap">
                        <a class="action-icon action-icon-applicants"
                           href="applicants.php?scholarship_id=<?php echo (int)$row['id']; ?>"
                           title="View Applicants"
                           aria-label="View Applicants">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3Zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z"></path>
                          </svg>
                        </a>

                        <a class="action-icon action-icon-edit"
                           href="edit_scholarship.php?id=<?php echo (int)$row['id']; ?>"
                           title="Edit Scholarship"
                           aria-label="Edit Scholarship">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm17.71-10.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0L14.96 5.12l3.75 3.75 1.99-1.66Z"></path>
                          </svg>
                        </a>

                        <a class="action-icon action-icon-toggle"
                           href="scholarships.php?toggle_id=<?php echo (int)$row['id']; ?>"
                           onclick="return confirm('Change scholarship status?')"
                           title="Toggle Status"
                           aria-label="Toggle Status">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 6V3L8 7l4 4V8c2.76 0 5 2.24 5 5a5 5 0 0 1-8.66 3.54l-1.42 1.42A7 7 0 1 0 12 6Zm-5 5a5 5 0 0 1 8.66-3.54l1.42-1.42A7 7 0 1 0 12 20v3l4-4-4-4v3c-2.76 0-5-2.24-5-5Z"></path>
                          </svg>
                        </a>

                        <a class="action-icon action-icon-delete"
                           href="delete_scholarship.php?id=<?php echo (int)$row['id']; ?>"
                           onclick="return confirm('Are you sure you want to delete this scholarship?')"
                           title="Delete Scholarship"
                           aria-label="Delete Scholarship">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 7h12l-1 14H7L6 7Zm3-3h6l1 2h4v2H4V6h4l1-2Z"></path>
                          </svg>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>

  </div>
</div>

<?php require_once("../../includes/footer.php"); ?>