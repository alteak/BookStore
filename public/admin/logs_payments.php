<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

// Fetch payments
$sql = "
    SELECT id, order_id, amount, status, created_at
    FROM payments
    ORDER BY id DESC
    LIMIT 200
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Logs</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Payment Logs</h3>
      <div class="text-muted">Last 200 payment records.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:90px;">Order</th>
            <th style="width:140px;">Amount</th>
            <th style="width:130px;">Status</th>
            <th style="width:220px;">Created</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $hasRows = false;
          while ($r = $result->fetch_assoc()):
              $hasRows = true;
              $status = strtoupper(trim((string)($r['status'] ?? '')));
              $badge = 'bg-secondary';

              if (in_array($status, ['PAID','SUCCESS','COMPLETED'], true)) $badge = 'bg-success';
              elseif (in_array($status, ['PENDING','WAITING'], true)) $badge = 'bg-warning';
              elseif (in_array($status, ['FAILED','ERROR'], true)) $badge = 'bg-danger';
              elseif (in_array($status, ['CANCELLED','CANCELED'], true)) $badge = 'bg-danger';
          ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= (int)$r['order_id'] ?></td>
              <td><?= number_format((float)$r['amount'], 2) ?></td>
              <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($status ?: '-') ?></span></td>
              <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
            </tr>
          <?php endwhile; ?>

          <?php if (!$hasRows): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No payment logs found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
