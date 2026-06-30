<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

// Fetch logs
$sql = "
    SELECT 
        id,
        email,
        status,
        ip_address,
        created_at
    FROM login_logs
    ORDER BY id DESC
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
  <title>Login Logs</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Login Logs</h3>
      <div class="text-muted">Last 200 login attempts.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>Email Entered</th>
            <th style="width:120px;">Status</th>
            <th style="width:170px;">IP Address</th>
            <th style="width:210px;">Created</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No logs found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <?php
              $status = strtoupper(trim((string)($r['status'] ?? '')));
              $isOk = in_array($status, ['SUCCESS', 'OK', '1', 'TRUE'], true);
            ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>
              <td>
                <?php if ($isOk): ?>
                  <span class="badge bg-success">SUCCESS</span>
                <?php else: ?>
                  <span class="badge bg-danger"><?= htmlspecialchars($status ?: 'FAIL') ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($r['ip_address'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
