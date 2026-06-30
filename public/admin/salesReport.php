<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

// Filter dates
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

$fromDT = $from . " 00:00:00";
$toDT   = $to   . " 23:59:59";

// Detect price column in order_items
$hasUnitPrice = false;
$hasPrice = false;

$rCols = mysqli_query($conn, "SHOW COLUMNS FROM order_items");
while ($c = mysqli_fetch_assoc($rCols)) {
    if ($c['Field'] === 'unit_price') $hasUnitPrice = true;
    if ($c['Field'] === 'price') $hasPrice = true;
}

$priceExpr = "inv.price"; // fallback (NOT ideal, but ok if no unit_price stored)
if ($hasUnitPrice) $priceExpr = "oi.unit_price";
else if ($hasPrice) $priceExpr = "oi.price";

// Sales report query (only PAID orders)
$sql = "
    SELECT
        o.id AS order_id,
        o.status,
        o.created_at,
        b.title,
        oi.quantity,
        $priceExpr AS unit_price,
        (oi.quantity * $priceExpr) AS line_total
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN books b ON b.id = oi.book_id
    LEFT JOIN inventory inv ON inv.book_id = b.id
    WHERE o.created_at BETWEEN ? AND ?
      AND o.status = 'PAID'
    ORDER BY o.created_at DESC, o.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ss", $fromDT, $toDT);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
$totalRevenue = 0.0;

while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
    $totalRevenue += (float)$r['line_total'];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales Report</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Sales Report</h3>
      <div class="text-muted">Paid orders revenue by date interval.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <div class="card p-3 mb-3">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">From</label>
        <input class="form-control" type="date" name="from" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input class="form-control" type="date" name="to" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-sm" type="submit">Filter</button>
        <a class="btn btn-sm" href="salesReport.php">Today</a>
      </div>
    </form>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:210px;">Date</th>
            <th style="width:90px;">Order</th>
            <th style="width:110px;">Status</th>
            <th>Book</th>
            <th class="text-end" style="width:80px;">Qty</th>
            <th class="text-end" style="width:140px;">Unit Price (€)</th>
            <th class="text-end" style="width:150px;">Line Total (€)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">No sales in this period.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
                <td>#<?= (int)$r['order_id'] ?></td>
                <td><span class="badge bg-success">PAID</span></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td class="text-end"><?= (int)$r['quantity'] ?></td>
                <td class="text-end"><?= number_format((float)$r['unit_price'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$r['line_total'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="alert alert-success mt-3 mb-0">
      <b>Total revenue (<?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?>):</b>
      <?= number_format($totalRevenue, 2) ?> €
    </div>
  </div>

</body>
</html>
