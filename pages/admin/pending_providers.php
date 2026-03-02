<?php
require_once("../../config/db.php");
require_once("../../config/auth.php");

require_login();
require_role("admin");

// Fetch providers who are pending
$sql = "SELECT id, full_name, email, status, created_at 
        FROM users 
        WHERE role='provider' AND status='pending'
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Pending Providers</title>
</head>
<body>
  <h2>Pending Provider Accounts</h2>
  <a href="dashboard.php">Back to Dashboard</a>
  <br><br>

  <table border="1" cellpadding="8">
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Status</th>
      <th>Date Registered</th>
      <th>Action</th>
    </tr>

    <?php while($row = mysqli_fetch_assoc($result)) { ?>
      <tr>
        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        <td>
          <a href="provider_action.php?id=<?php echo $row['id']; ?>&action=approve"
             onclick="return confirm('Approve this provider?')">Approve</a>
          |
          <a href="provider_action.php?id=<?php echo $row['id']; ?>&action=reject"
             onclick="return confirm('Reject this provider?')">Reject</a>
        </td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>