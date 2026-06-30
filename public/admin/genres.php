<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

$err = '';
$success = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF check
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $err = "Invalid request (CSRF). Please try again.";
        header("Location: genres.php");
        exit;
    }

    // Add genre
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $err = "Genre name is required.";
        } else {
            $sql = "INSERT IGNORE INTO genres (name) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $name);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_errno($stmt)) {
                $err = "Database error: " . mysqli_stmt_error($stmt);
            } else {
                // INSERT IGNORE: nëse ekziston, affected_rows = 0
                $success = (mysqli_stmt_affected_rows($stmt) > 0)
                    ? "Genre added successfully."
                    : "Genre already exists.";
            }
            mysqli_stmt_close($stmt);
        }

        header("Location: genres.php");
        exit;
    }

    // Delete genre
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $err = "Invalid genre ID.";
            header("Location: genres.php");
            exit;
        }

        // Fshi genre-n
        $sql = "DELETE FROM genres WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt)) {
            // Shpesh këtu vjen error FK: Cannot delete or update a parent row...
            $err = "Cannot delete this genre. It may be used by one or more books.";
        } else {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $success = "Genre deleted successfully.";
            } else {
                $err = "Genre not found (maybe already deleted).";
            }
        }
        mysqli_stmt_close($stmt);

        header("Location: genres.php");
        exit;
    }

    // Action fallback
    header("Location: genres.php");
    exit;
}

// Load genres
$genres = [];
$res = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $genres[] = $row;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Genres</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">
</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Genres / Tags</h3>
      <div class="text-muted">Add genres used for filtering books in the store.</div>
    </div>
    <a class="btn btn-sm" href="books.php">← Back</a>
  </div>

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card p-3" style="max-width: 760px; margin: 0 auto;">
    <!-- ADD FORM -->
    <form method="post" class="d-flex gap-2 mb-3">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <input class="form-control"
             name="name"
             placeholder="e.g. Fantasy, Romance, Horror..."
             required>
      <button class="btn btn-sm" type="submit">Add</button>
    </form>

    <ul class="list-group">
      <?php if (empty($genres)): ?>
        <li class="list-group-item text-muted">No genres yet.</li>
      <?php endif; ?>

      <?php foreach ($genres as $g): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div class="d-flex flex-column">
            <span><?= htmlspecialchars($g['name']) ?></span>
            <small class="text-muted">ID: <?= (int)$g['id'] ?></small>
          </div>

          <!-- DELETE BUTTON -->
          <form method="post" class="m-0"
                onsubmit="return confirm('Delete this genre: <?= htmlspecialchars($g['name'], ENT_QUOTES) ?> ?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

</body>
</html>
