<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

$status = $_GET['status'] ?? 'ALL';
$allowed = ['ALL', 'PENDING', 'PAID', 'CANCELLED'];
if (!in_array($status, $allowed, true)) {
    $status = 'ALL';
}

$err = '';

// Helper: log admin action
function log_admin_action(mysqli $conn, string $adminEmail, string $action, string $entity, int $entityId, string $details): void
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO user_logs (admin_email, action, entity, entity_id, details)
         VALUES (?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssis", $adminEmail, $action, $entity, $entityId, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Mark as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid_order_id'])) {
    $orderId = (int)$_POST['mark_paid_order_id'];
    $adminEmail = $_SESSION['user']['email'] ?? 'admin';

    mysqli_begin_transaction($conn);

    try {
        // Lock order
        $stmt = mysqli_prepare($conn, "SELECT status, total_amount FROM orders WHERE id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            throw new Exception("Order not found.");
        }
        if (strtoupper(trim((string)$row['status'])) !== 'PENDING') {
            throw new Exception("Only PENDING orders can be marked as PAID.");
        }

        // Update status -> PAID
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status='PAID' WHERE id=?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Insert into payments (because logs_payments.php reads from `payments`)
        $amount = (float)($row['total_amount'] ?? 0);
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (order_id, amount, status, created_at) VALUES (?, ?, 'PAID', NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "id", $orderId, $amount);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Log admin action
        log_admin_action($conn, $adminEmail, 'MARK_PAID', 'order', $orderId, 'Marked order as PAID from admin panel');

        mysqli_commit($conn);

        header("Location: /BookStore/public/admin/orders.php?status=" . urlencode($status));
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $err = $e->getMessage();
    }
}

// Cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = (int)$_POST['cancel_order_id'];
    $adminEmail = $_SESSION['user']['email'] ?? 'admin';

    mysqli_begin_transaction($conn);

    try {
        // Lock order
        $stmt = mysqli_prepare($conn, "SELECT status FROM orders WHERE id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) {
            throw new Exception("Order not found.");
        }

        if (strtoupper(trim((string)$row['status'])) !== 'PENDING') {
            throw new Exception("Only PENDING orders can be cancelled.");
        }

        // Update status -> CANCELLED
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status='CANCELLED' WHERE id=?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Log admin action
        log_admin_action($conn, $adminEmail, 'CANCEL_ORDER', 'order', $orderId, 'Cancelled order from admin panel');

        mysqli_commit($conn);

        header("Location: /BookStore/public/admin/orders.php?status=" . urlencode($status));
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $err = $e->getMessage();
    }
}

// Fetch orders
// NOTE: Requires these columns to exist in `orders`:
// - shipping_address (VARCHAR/TEXT, nullable)
// - city (VARCHAR, nullable)
// - phone (VARCHAR, nullable)
// If you don't have them, either add them in DB or remove from SELECT.
$where = "";
$params = [];
$types = "";

if ($status !== 'ALL') {
    $where = "WHERE status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql = "
    SELECT
        id,
        user_email,
        customer_name,
        status,
        total_amount,
        shipping_address,
        city,
        phone,
        created_at
    FROM orders
    $where
    ORDER BY id DESC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Orders</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">
</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Orders</h3>
      <div class="text-muted">View orders, view address/items, mark paid, or cancel pending orders.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="card p-3 mb-3">
    <div class="d-flex flex-wrap gap-2">
      <?php foreach (['ALL','PENDING','PAID','CANCELLED'] as $s): ?>
        <a class="btn btn-sm <?= ($status === $s) ? 'btn-primary' : '' ?>"
           href="orders.php?status=<?= $s ?>">
          <?= $s ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>User Email</th>
            <th>Customer Name</th>
            <th style="width:120px;">Status</th>
            <th style="width:120px;">Total (€)</th>
            <th style="width:210px;">Created</th>
            <th style="width:340px;">Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php
        $hasRows = false;

        while ($o = mysqli_fetch_assoc($orders)):
          $hasRows = true;

          $st = strtoupper(trim((string)$o['status']));
          $badge = 'bg-secondary';
          if ($st === 'PAID') $badge = 'bg-success';
          elseif ($st === 'PENDING') $badge = 'bg-warning';
          elseif ($st === 'CANCELLED') $badge = 'bg-danger';

          $orderId = (int)$o['id'];
          $modalId = "addrModal_" . $orderId;

          $addr  = $o['shipping_address'] ?? '';
          $city  = $o['city'] ?? '';
          $phone = $o['phone'] ?? '';
        ?>
          <tr>
            <td><?= $orderId ?></td>
            <td><?= htmlspecialchars($o['user_email']) ?></td>
            <td><?= htmlspecialchars($o['customer_name'] ?? 'N/A') ?></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($st) ?></span></td>
            <td><?= number_format((float)$o['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars($o['created_at']) ?></td>

            <td>
              <div class="d-flex flex-wrap gap-2">

                <!-- ✅ VIEW order items/details -->
                <a class="btn btn-sm" href="orderDetails.php?id=<?= $orderId ?>">
                  View
                </a>

                <!-- ✅ ADDRESS modal -->
                <button type="button"
                        class="btn btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#<?= $modalId ?>">
                  Address
                </button>

                <?php if ($st === 'PENDING'): ?>
                  <!-- Mark Paid -->
                  <form method="post" onsubmit="return confirm('Mark this order as PAID?');" style="margin:0;">
                    <input type="hidden" name="mark_paid_order_id" value="<?= $orderId ?>">
                    <button class="btn btn-sm btn-success" type="submit">Mark Paid</button>
                  </form>

                  <!-- Cancel -->
                  <form method="post" onsubmit="return confirm('Cancel this order?');" style="margin:0;">
                    <input type="hidden" name="cancel_order_id" value="<?= $orderId ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Cancel</button>
                  </form>
                <?php endif; ?>

              </div>

              <!-- ✅ Address Modal -->
              <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Shipping Address (Order #<?= $orderId ?>)</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-2"><b>User:</b> <?= htmlspecialchars($o['user_email']) ?></div>

                      <div class="mb-2">
                        <b>Address:</b><br>
                        <?= $addr !== '' ? nl2br(htmlspecialchars($addr)) : '<span class="text-muted">-</span>' ?>
                      </div>

                      <div class="mb-2">
                        <b>City:</b>
                        <?= $city !== '' ? htmlspecialchars($city) : '<span class="text-muted">-</span>' ?>
                      </div>

                      <div class="mb-0">
                        <b>Phone:</b>
                        <?= $phone !== '' ? htmlspecialchars($phone) : '<span class="text-muted">-</span>' ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </td>
          </tr>
        <?php endwhile; ?>

        <?php if (!$hasRows): ?>
          <tr>
            <td colspan="6" class="text-center text-muted">No orders found for this filter.</td>
          </tr>
        <?php endif; ?>
        </tbody>

      </table>
    </div>
  </div>

  <!-- Bootstrap JS (required for modals) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
