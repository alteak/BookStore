<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
  header('Location: /BookStore/public/user/index.php');
  exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
  header('Location: orders.php');
  exit;
}

$err = "";

// Load order header
$stmt = mysqli_prepare($conn, "SELECT id, user_email, status, total_amount, created_at FROM orders WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
  $err = "Order not found.";
}

// Detect price column (unit_price / price / fallback inventory.price)
$hasUnitPrice = false;
$hasPrice = false;

$rCols = mysqli_query($conn, "SHOW COLUMNS FROM order_items");
while ($c = mysqli_fetch_assoc($rCols)) {
  if ($c['Field'] === 'unit_price') $hasUnitPrice = true;
  if ($c['Field'] === 'price') $hasPrice = true;
}

$priceExpr = "inv.price"; // fallback
if ($hasUnitPrice) $priceExpr = "oi.unit_price";
else if ($hasPrice) $priceExpr = "oi.price";

// Load order items
$items = [];
$itemsTotal = 0.0;

if (!$err) {
  $sql = "
    SELECT
      oi.book_id,
      b.title,
      oi.quantity,
      $priceExpr AS unit_price,
      (oi.quantity * $priceExpr) AS line_total
    FROM order_items oi
    JOIN books b ON b.id = oi.book_id
    LEFT JOIN inventory inv ON inv.book_id = b.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
  ";

  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "i", $orderId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
    $itemsTotal += (float)$row['line_total'];
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Order Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">
</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Order #<?= (int)$orderId ?></h3>
      <div class="text-muted">View order items and quantities</div>
    </div>
    <a class="btn btn-sm" href="orders.php">← Back</a>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php else: ?>

    <div class="card p-3 mb-3">
      <div class="row g-2">
        <div class="col-md-4"><b>User:</b> <?= htmlspecialchars($order['user_email']) ?></div>
        <div class="col-md-2"><b>Status:</b> <span class="badge bg-secondary"><?= htmlspecialchars($order['status']) ?></span></div>
        <div class="col-md-3"><b>Created:</b> <?= htmlspecialchars($order['created_at']) ?></div>
        <div class="col-md-3"><b>Order Total:</b> <?= number_format((float)$order['total_amount'], 2) ?> €</div>
      </div>
    </div>

    <div class="card p-3">
      <h5 class="mb-3">Items</h5>

      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead>
            <tr>
              <th>Book</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Price (€)</th>
              <th class="text-end">Line Total (€)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No items found for this order.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= htmlspecialchars($it['title']) ?></td>
                  <td class="text-end"><?= (int)$it['quantity'] ?></td>
                  <td class="text-end"><?= number_format((float)$it['unit_price'], 2) ?></td>
                  <td class="text-end"><?= number_format((float)$it['line_total'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="alert alert-success mt-3 mb-0">
        <b>Items Total:</b> <?= number_format($itemsTotal, 2) ?> €
      </div>
    </div>

  <?php endif; ?>

</body>
</html>
