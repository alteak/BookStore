<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

// Fetch user logs
$sql = "
    SELECT 
        ul.id,
        ul.admin_email,
        ul.action,
        ul.entity,
        ul.entity_id,
        ul.details,
        ul.created_at
    FROM user_logs ul
    ORDER BY ul.id DESC
    LIMIT 200
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
?>
<!doctype html>
<html lang="sq">
<head>
  <meta charset="utf-8">
  <title>User Logs</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">User Logs (Admin Actions)</h3>
      <div class="text-muted">Last 200 admin actions recorded.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="min-width:180px;">Admin Email</th>
            <th style="min-width:150px;">Action</th>
            <th style="min-width:120px;">Entity</th>
            <th style="width:90px;">Entity ID</th>
            <th style="min-width:260px;">Details</th>
            <th style="width:220px;">Created</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">No user logs found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['admin_email'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['action'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['entity'] ?? '-') ?></td>
              <td><?= htmlspecialchars((string)($r['entity_id'] ?? '-')) ?></td>
              <td><?= htmlspecialchars($r['details'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
