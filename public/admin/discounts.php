<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

/*
  (Optional) debugging during development:
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
*/

$adminEmail = $_SESSION['user']['email'] ?? ($_SESSION['user_email'] ?? 'admin');

$err = '';

// Log helper (smart columns - supports user_logs with admin_email or email)
function addLog(mysqli $conn, string $adminEmail, string $action, string $details = ''): void
{
    // detect column name
    $col = 'admin_email';
    $r = mysqli_query($conn, "SHOW COLUMNS FROM user_logs LIKE 'admin_email'");
    if (!$r || mysqli_num_rows($r) === 0) {
        $col = 'email';
    }

    $sql = "INSERT INTO user_logs ($col, action, entity, details, created_at)
            VALUES (?, ?, 'discount', ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $adminEmail, $action, $details);
    $stmt->execute();
}

// Create discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {

    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $type      = $_POST['type'] ?? 'PERCENT';
    $value     = (float)($_POST['value'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $starts_at = trim($_POST['starts_at'] ?? '');
    $ends_at   = trim($_POST['ends_at'] ?? '');

    $starts_at = ($starts_at === '') ? null : $starts_at;
    $ends_at   = ($ends_at === '') ? null : $ends_at;

    if ($code === '' || $value <= 0) {
        $err = "Code dhe value duhen (value > 0).";
    } elseif (!in_array($type, ['PERCENT','FIXED'], true)) {
        $err = "Invalid discount type.";
    } else {

        // NOTE: bind_param types must match params count
        // code(s), type(s), value(d), is_active(i), starts_at(s), ends_at(s)
        $sql = "INSERT INTO discounts (code, type, value, is_active, starts_at, ends_at)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssdis" . "s",
            $code,
            $type,
            $value,
            $is_active,
            $starts_at,
            $ends_at
        );

        if ($stmt->execute()) {
            addLog($conn, $adminEmail, "CREATE_DISCOUNT", "code=$code type=$type value=$value");
            header("Location: /BookStore/public/admin/discounts.php");
            exit;
        } else {
            $err = "Database error: " . $stmt->error;
        }
    }
}

// Toggle discount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {

    $id = (int)$_POST['toggle_id'];

    $stmt = $conn->prepare("UPDATE discounts SET is_active = 1 - is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    addLog($conn, $adminEmail, "TOGGLE_DISCOUNT", "discount_id=$id");

    header("Location: /BookStore/public/admin/discounts.php");
    exit;
}

// Fetch discounts
$result = $conn->query("SELECT * FROM discounts ORDER BY id DESC");
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Discounts</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Discounts</h3>
      <div class="text-muted">Create and activate discount codes.</div>
    </div>
    <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
  </div>

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="card p-3 mb-3">
    <form method="post" class="row g-2 align-items-end">

      <div class="col-md-3">
        <label class="form-label">Code</label>
        <input class="form-control" name="code" placeholder="SAVE10" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">Type</label>
        <select class="form-select" name="type">
          <option value="PERCENT">PERCENT</option>
          <option value="FIXED">FIXED</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Value</label>
        <input class="form-control" name="value" type="number" step="0.01" required>
      </div>

      <div class="col-md-2">
        <label class="form-label">Starts (optional)</label>
        <input class="form-control" name="starts_at" placeholder="2026-01-10 10:00:00">
      </div>

      <div class="col-md-2">
        <label class="form-label">Ends (optional)</label>
        <input class="form-control" name="ends_at" placeholder="2026-01-30 23:59:59">
      </div>

      <div class="col-md-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="active" checked>
          <label class="form-check-label" for="active">On</label>
        </div>
      </div>

      <div class="col-12 mt-2">
        <button class="btn btn-sm" name="create" value="1">Add Discount</button>
      </div>

    </form>
  </div>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>Code</th>
            <th style="width:110px;">Type</th>
            <th style="width:110px;">Value</th>
            <th style="width:90px;">Active</th>
            <th>Starts</th>
            <th>Ends</th>
            <th style="width:120px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted">No discounts found.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['code']) ?></td>
              <td><?= htmlspecialchars($r['type']) ?></td>
              <td><?= number_format((float)$r['value'], 2) ?></td>
              <td><?= ((int)$r['is_active'] === 1) ? 'YES' : 'NO' ?></td>
              <td><?= htmlspecialchars($r['starts_at'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['ends_at'] ?? '-') ?></td>
              <td>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="toggle_id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm" type="submit">Toggle</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
