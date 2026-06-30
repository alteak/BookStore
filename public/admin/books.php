<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // must define $conn (mysqli)

$error = '';
$success = '';

// Delete book (safe - only if book has no order_items referencing it)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];

    mysqli_begin_transaction($conn);
    try {
        // if referenced by order_items -> don't delete (FK)
        $chk = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM order_items WHERE book_id = ?");
        mysqli_stmt_bind_param($chk, "i", $deleteId);
        mysqli_stmt_execute($chk);
        $r = mysqli_stmt_get_result($chk);
        $cRow = mysqli_fetch_assoc($r);
        $count = (int)($cRow['c'] ?? 0);

        if ($count > 0) {
            throw new Exception("Cannot delete: book is used in orders. Deactivate it instead.");
        }

        // delete relations first
        mysqli_query($conn, "DELETE FROM book_genres WHERE book_id = $deleteId");
        mysqli_query($conn, "DELETE FROM inventory WHERE book_id = $deleteId");
        mysqli_query($conn, "DELETE FROM books WHERE id = $deleteId");

        mysqli_commit($conn);
        $success = "Book deleted successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Fetch books + inventory
$sql = "
    SELECT 
        b.id,
        b.title,
        b.author,
        b.is_active,
        COALESCE(i.price, 0) AS price,
        COALESCE(i.stock, 0) AS stock
    FROM books b
    LEFT JOIN inventory i ON i.book_id = b.id
    ORDER BY b.id DESC
";

$res = mysqli_query($conn, $sql);
$books = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $books[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Books & Inventory</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Books & Inventory</h3>
      <div class="text-muted">Manage book list and stock/price.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm" href="bookForm.php">+ Add Book</a>
      <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card p-3">
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th>Title</th>
            <th>Author</th>
            <th style="width:120px">Price</th>
            <th style="width:90px">Stock</th>
            <th style="width:90px">Active</th>
            <th style="width:170px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($books)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">No books found.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($books as $b): ?>
            <tr>
              <td><?= (int)$b['id'] ?></td>
              <td><?= htmlspecialchars($b['title']) ?></td>
              <td><?= htmlspecialchars($b['author']) ?></td>
              <td><?= number_format((float)$b['price'], 2) ?> €</td>
              <td><?= (int)$b['stock'] ?></td>
              <td><?= ((int)$b['is_active'] === 1) ? 'Yes' : 'No' ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a class="btn btn-sm" href="bookForm.php?id=<?= (int)$b['id'] ?>">Edit</a>

                  <form method="post" onsubmit="return confirm('Delete this book?');" style="margin:0;">
                    <input type="hidden" name="delete_id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-3 d-flex gap-2">
      <a class="btn btn-sm" href="genres.php">Manage Genres</a>
    </div>
  </div>

</body>
</html>
