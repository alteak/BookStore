<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

// Fetch users
$sql = "
    SELECT 
        email,
        role,
        created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 200
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Users</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Users</h3>
      <div class="text-muted">Last 200 registered users.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Email</th>
            <th style="width:130px;">Role</th>
            <th style="width:220px;">Created</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="3" class="text-center text-muted">No users found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($users as $u): ?>
            <?php
              $role = strtoupper(trim((string)($u['role'] ?? 'USER')));
              $badge = ($role === 'ADMIN') ? 'bg-primary' : 'bg-secondary';
            ?>
            <tr>
              <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
              <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($role) ?></span></td>
              <td><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
