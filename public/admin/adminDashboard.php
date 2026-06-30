<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php';

// Get total unread messages count
$unreadQuery = "SELECT COUNT(*) as unread_count FROM messages WHERE is_read=0 AND sender='USER'";
$unreadResult = mysqli_query($conn, $unreadQuery);
$unreadRow = mysqli_fetch_assoc($unreadResult);
$totalUnread = (int)$unreadRow['unread_count'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Panel</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h2 class="mb-0">Admin Panel</h2>
      <div class="text-muted">Manage books, orders, users, logs and discounts</div>
    </div>
    <a class="btn btn-sm" href="../user/logout.php">Log out</a>
  </div>

  <div class="card p-3" style="max-width: 820px; margin: 0 auto;">
    <div class="row g-2">

      <!-- Primary actions -->
      <div class="col-12 col-md-6">
        <a class="btn w-100 btn-success" href="books.php">Books & Inventory</a>
      </div>
      <div class="col-12 col-md-6">
        <a class="btn w-100 btn-success" href="orders.php">Orders</a>
      </div>

      <!-- Secondary actions -->
      <div class="col-12 col-md-6">
        <a class="btn w-100" href="users.php">Users</a>
      </div>
      <div class="col-12 col-md-6">
        <a class="btn w-100" href="genres.php">Genres / Tags</a>
      </div>

      <div class="col-12 col-md-6">
        <a class="btn w-100" href="discounts.php">Discounts</a>
      </div>
      <div class="col-12 col-md-6">
        <a class="btn w-100" href="salesReport.php">Sales Report</a>
      </div>

      <div class="col-12 col-md-6">
        <a class="btn w-100" href="inbox.php">
          Inbox 
          <?php if ($totalUnread > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= $totalUnread ?></span>
          <?php endif; ?>
        </a>
      </div>
      <div class="col-12 col-md-6">
        <a class="btn w-100" href="logs_logins.php">Login Logs</a>
      </div>

      <div class="col-12 col-md-6">
        <a class="btn w-100" href="logs_payments.php">Payment Logs</a>
      </div>
      <div class="col-12 col-md-6">
        <a class="btn w-100" href="logs_users.php">User Logs</a>
      </div>

    </div>
  </div>

</body>
</html>
