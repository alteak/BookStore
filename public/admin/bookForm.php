<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
    header('Location: /BookStore/public/user/index.php');
    exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

$error = '';

// Load genres
$genres = [];
$res = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) {
    $genres[] = $row;
}

// Load book (edit mode)
$book = null;
$selectedGenres = [];

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $sql = "
        SELECT b.*, i.price, i.stock
        FROM books b
        LEFT JOIN inventory i ON i.book_id = b.id
        WHERE b.id = $id
        LIMIT 1
    ";
    $res = mysqli_query($conn, $sql);
    $book = mysqli_fetch_assoc($res);

    $resG = mysqli_query($conn, "SELECT genre_id FROM book_genres WHERE book_id = $id");
    while ($g = mysqli_fetch_assoc($resG)) {
        $selectedGenres[] = (int)$g['genre_id'];
    }
}

$isEdit = ($book !== null);

// Save (insert / update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    $id     = (int)($_POST['id'] ?? 0);

    // editable fields
    $price  = (float)($_POST['price'] ?? 0);
    $stock  = (int)($_POST['stock'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    $genreIds = array_map('intval', $_POST['genres'] ?? []);

    // fields locked after creation (only used when creating)
    $title  = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $desc   = trim($_POST['description'] ?? '');

    // basic validation
    if ($price <= 0) {
        $error = "Price must be > 0.";
    } elseif ($stock < 0) {
        $error = "Stock cannot be negative.";
    } else {
        if ($id !== 0) {
            // fetch fixed fields from DB to prevent changes
            $resFixed = mysqli_query($conn, "
                SELECT title, author, description, cover_image, is_active
                FROM books
                WHERE id = $id
                LIMIT 1
            ");
            $fixed = mysqli_fetch_assoc($resFixed);

            if (!$fixed) {
                $error = "Book not found.";
            } else {
                $title  = $fixed['title'];
                $author = $fixed['author'];
                $desc   = $fixed['description'];

                $book = array_merge(($book ?? []), $fixed);
                $isEdit = true;
            }
        } else {
            if ($title === '' || $author === '') {
                $error = "Title and author are required.";
            }
        }
    }

    if ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            /* ---------- COVER UPLOAD / REMOVE ---------- */
            $coverPath = $book['cover_image'] ?? null;

            if (isset($_POST['remove_cover'])) {
                $coverPath = null;
            }

            if (!empty($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {

                $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
                if (!in_array($_FILES['cover']['type'], $allowedMime, true)) {
                    throw new Exception("Invalid image type (allowed: jpg/png/webp/gif).");
                }

                if ($_FILES['cover']['size'] > 2 * 1024 * 1024) {
                    throw new Exception("Image too large. Max 2MB.");
                }

                $uploadDir = __DIR__ . '/../../assets/uploads/covers/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
                $fileName = 'cover_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $dest = $uploadDir . $fileName;

                if (!move_uploaded_file($_FILES['cover']['tmp_name'], $dest)) {
                    throw new Exception("Failed to save uploaded file.");
                }

                $coverPath = '/BookStore/assets/uploads/covers/' . $fileName;
            }

            /* ---------- INSERT / UPDATE BOOK ---------- */
            if ($id === 0) {
                $titleEsc  = mysqli_real_escape_string($conn, $title);
                $authorEsc = mysqli_real_escape_string($conn, $author);
                $descEsc   = mysqli_real_escape_string($conn, $desc);
                $coverEsc  = $coverPath ? mysqli_real_escape_string($conn, $coverPath) : null;

                $q = "
                    INSERT INTO books (title, author, description, cover_image, is_active)
                    VALUES (
                        '$titleEsc',
                        '$authorEsc',
                        '$descEsc',
                        " . ($coverEsc ? "'$coverEsc'" : "NULL") . ",
                        $active
                    )
                ";
                if (!mysqli_query($conn, $q)) {
                    throw new Exception(mysqli_error($conn));
                }

                $id = mysqli_insert_id($conn);

                $qInv = "INSERT INTO inventory (book_id, price, stock) VALUES ($id, $price, $stock)";
                if (!mysqli_query($conn, $qInv)) {
                    throw new Exception(mysqli_error($conn));
                }

            } else {
                $coverEsc = $coverPath ? mysqli_real_escape_string($conn, $coverPath) : null;

                $qUpd = "
                    UPDATE books SET
                        cover_image=" . ($coverEsc ? "'$coverEsc'" : "NULL") . ",
                        is_active=$active
                    WHERE id=$id
                ";
                if (!mysqli_query($conn, $qUpd)) {
                    throw new Exception(mysqli_error($conn));
                }

                $qInv = "
                    INSERT INTO inventory (book_id, price, stock)
                    VALUES ($id, $price, $stock)
                    ON DUPLICATE KEY UPDATE
                        price=VALUES(price),
                        stock=VALUES(stock)
                ";
                if (!mysqli_query($conn, $qInv)) {
                    throw new Exception(mysqli_error($conn));
                }
            }

            /* ---------- GENRES (RESET + INSERT) ---------- */
            mysqli_query($conn, "DELETE FROM book_genres WHERE book_id = $id");
            foreach ($genreIds as $gid) {
                $gid = (int)$gid;
                mysqli_query($conn, "INSERT INTO book_genres (book_id, genre_id) VALUES ($id, $gid)");
            }

            mysqli_commit($conn);
            header("Location: books.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Save failed: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= $isEdit ? 'Edit Book' : 'Add Book' ?></title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0"><?= $isEdit ? 'Edit Book' : 'Add Book' ?></h3>
      <div class="text-muted">Only stock, price and cover can be updated after creation.</div>
    </div>
    <a class="btn btn-sm" href="books.php">← Back</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card p-3" style="max-width: 980px; margin: 0 auto;">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= (int)($book['id'] ?? 0) ?>">

      <div class="row g-3">

        <div class="col-md-6">
          <label class="form-label">Title</label>
          <input class="form-control" name="title"
                 value="<?= htmlspecialchars($book['title'] ?? '') ?>"
                 <?= $isEdit ? 'readonly' : '' ?>>
          <?php if ($isEdit): ?>
            <div class="form-text text-muted">Locked after creation</div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label">Author</label>
          <input class="form-control" name="author"
                 value="<?= htmlspecialchars($book['author'] ?? '') ?>"
                 <?= $isEdit ? 'readonly' : '' ?>>
          <?php if ($isEdit): ?>
            <div class="form-text text-muted">Locked after creation</div>
          <?php endif; ?>
        </div>

        <div class="col-md-3">
          <label class="form-label">Price (€)</label>
          <input class="form-control" type="number" step="0.01" name="price"
                 value="<?= htmlspecialchars($book['price'] ?? '') ?>">
          <div class="form-text text-muted">Editable</div>
        </div>

        <div class="col-md-3">
          <label class="form-label">Stock</label>
          <input class="form-control" type="number" min="0" name="stock"
                 value="<?= (int)($book['stock'] ?? 0) ?>">
          <div class="form-text text-muted">Editable</div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active"
              <?= ((int)($book['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            <label class="form-check-label">Active</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" <?= $isEdit ? 'readonly' : '' ?>><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
          <?php if ($isEdit): ?>
            <div class="form-text text-muted">Locked after creation</div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label">Cover Image</label>
          <input class="form-control" type="file" name="cover" accept="image/*">
          <div class="form-text text-muted">Editable</div>

          <?php if (!empty($book['cover_image'])): ?>
            <div class="mt-2">
              <img src="<?= htmlspecialchars($book['cover_image']) ?>" style="max-height:120px">
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="remove_cover">
              <label class="form-check-label">Remove cover</label>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label">Genres</label>
          <select class="form-select" name="genres[]" multiple size="6">
            <?php foreach ($genres as $g): ?>
              <option value="<?= (int)$g['id'] ?>" <?= in_array((int)$g['id'], $selectedGenres, true) ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">CTRL për zgjedhje multiple</div>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-success" type="submit" name="save">Save</button>
        <a class="btn btn-secondary" href="books.php">Cancel</a>
      </div>

    </form>
  </div>

</body>
</html>
