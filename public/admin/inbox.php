<?php
require_once __DIR__ . '/../../services/session/sessionHandler.php';
requireLogin();

if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
  header('Location: /BookStore/public/user/index.php');
  exit;
}

require_once __DIR__ . '/../../database/databaseConnection.php'; // $conn (mysqli)

$err = "";
$selectedUser = $_GET['user'] ?? "";

// Users list (threads)
$resUsers = mysqli_query($conn, "
  SELECT user_email,
         MAX(created_at) AS last_time,
         SUM(CASE WHEN is_read=0 AND sender='USER' THEN 1 ELSE 0 END) AS unread_count
  FROM messages
  GROUP BY user_email
  ORDER BY last_time DESC
");

$threads = [];
$totalUnreadMessages = 0;
while ($row = mysqli_fetch_assoc($resUsers)) {
  $threads[] = $row;
  $totalUnreadMessages += (int)$row['unread_count'];
}

// Send reply (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
  $userEmail = trim($_POST['user_email'] ?? '');
  $msg = trim($_POST['message'] ?? '');

  if ($userEmail === '' || $msg === '') {
    $err = "User and message are required.";
  } else {
    $stmt = mysqli_prepare($conn, "INSERT INTO messages (user_email, sender, message, is_read) VALUES (?, 'ADMIN', ?, 0)");
    mysqli_stmt_bind_param($stmt, "ss", $userEmail, $msg);
    mysqli_stmt_execute($stmt);

    header("Location: inbox.php?user=" . urlencode($userEmail));
    exit;
  }
}

// Load messages for selected user
$messages = [];
$selectedUserUnreadCount = 0;
if ($selectedUser !== "") {
  // Get unread count before marking as read
  $stmtUnread = mysqli_prepare($conn, "SELECT COUNT(*) as unread FROM messages WHERE user_email=? AND sender='USER' AND is_read=0");
  mysqli_stmt_bind_param($stmtUnread, "s", $selectedUser);
  mysqli_stmt_execute($stmtUnread);
  $unreadResult = mysqli_stmt_get_result($stmtUnread);
  $unreadRow = mysqli_fetch_assoc($unreadResult);
  $selectedUserUnreadCount = (int)$unreadRow['unread'];
  
  $stmt = mysqli_prepare($conn, "SELECT id, sender, message, created_at FROM messages WHERE user_email=? ORDER BY id ASC");
  mysqli_stmt_bind_param($stmt, "s", $selectedUser);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) $messages[] = $row;

  // mark USER messages as read when admin opens thread
  $safe = mysqli_real_escape_string($conn, $selectedUser);
  mysqli_query($conn, "UPDATE messages SET is_read=1 WHERE user_email='$safe' AND sender='USER'");
}

// Feedback data
// Get average rating
$avgRatingQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback FROM feedback";
$avgResult = mysqli_query($conn, $avgRatingQuery);
$ratingData = mysqli_fetch_assoc($avgResult);
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalFeedback = $ratingData['total_feedback'] ?? 0;

// Get recent feedback messages
$feedbackQuery = "SELECT user_email, rating, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 10";
$feedbackResult = mysqli_query($conn, $feedbackQuery);
$feedbackMessages = [];
while ($row = mysqli_fetch_assoc($feedbackResult)) {
    $feedbackMessages[] = $row;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Inbox</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/BookStore/assets/css/admin/admin.css">

</head>

<body class="p-4 admin-page">

  <div class="admin-topbar d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-0">Admin Inbox</h3>
      <div class="text-muted">Messages from clients (support) and customer feedback.</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <?php if ($totalUnreadMessages > 0): ?>
        <div class="badge bg-danger"><i class="bi bi-envelope-fill"></i> <?= $totalUnreadMessages ?> Unread</div>
      <?php endif; ?>
      <div class="badge bg-primary">★ <?= $avgRating ?>/5 Average</div>
      <div class="badge bg-info"><?= $totalFeedback ?> Feedback</div>
      <a class="btn btn-sm" href="adminDashboard.php">← Back</a>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- LEFT: THREAD LIST -->
    <div class="col-md-4">
      <div class="card p-3 inbox-panel">
        <div class="mb-2 text-muted">Clients</div>

        <?php if (empty($threads)): ?>
          <div class="text-muted">No messages yet.</div>
        <?php else: ?>
          <?php foreach ($threads as $t): ?>
            <?php
              $u = $t['user_email'];
              $isActive = ($selectedUser === $u);
            ?>
            <a class="text-decoration-none" href="inbox.php?user=<?= urlencode($u) ?>">
              <div class="inbox-thread mb-2 <?= $isActive ? 'active' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                  <div><b><?= htmlspecialchars($u) ?></b></div>
                  <?php if ((int)$t['unread_count'] > 0): ?>
                    <span class="badge bg-danger"><?= (int)$t['unread_count'] ?></span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small">Last: <?= htmlspecialchars($t['last_time']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: MESSAGES -->
    <div class="col-md-8">
      <div class="card p-3 inbox-panel">
        <?php if ($selectedUser === ""): ?>
          <div class="alert alert-info mb-0">Select a client from the left to view messages.</div>
        <?php else: ?>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>
              <b>Conversation with:</b> <?= htmlspecialchars($selectedUser) ?>
              <?php if ($selectedUserUnreadCount > 0): ?>
                <span class="badge bg-danger ms-2"><?= $selectedUserUnreadCount ?> unread</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="inbox-scroll mb-3">
            <?php if (empty($messages)): ?>
              <div class="text-muted">No messages in this conversation yet.</div>
            <?php else: ?>
              <?php foreach ($messages as $m): ?>
                <div class="mb-2">
                  <div class="chat-bubble <?= $m['sender']==='ADMIN' ? 'chat-me' : 'chat-them' ?>">
                    <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                    <div class="chat-meta mt-1">
                      <?= htmlspecialchars($m['sender']) ?> • <?= htmlspecialchars($m['created_at']) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="user_email" value="<?= htmlspecialchars($selectedUser) ?>">
            <textarea class="form-control" name="message" rows="2" placeholder="Reply to client..." required></textarea>
            <button class="btn btn-sm" type="submit" name="reply">Send</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- FEEDBACK SECTION -->
  <div class="row g-3 mt-4">
    <div class="col-12">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="bi bi-star-fill text-warning"></i> Customer Feedback</h4>
          <div class="d-flex gap-2">
            <span class="badge bg-warning text-dark">Average Rating: <?= $avgRating ?>/5</span>
            <span class="badge bg-secondary">Total Reviews: <?= $totalFeedback ?></span>
          </div>
        </div>
        
        <!-- Rating Breakdown -->
        <?php
        // Get rating distribution
        $ratingDistQuery = "SELECT rating, COUNT(*) as count FROM feedback GROUP BY rating ORDER BY rating DESC";
        $ratingDistResult = mysqli_query($conn, $ratingDistQuery);
        $ratingDist = [];
        while ($row = mysqli_fetch_assoc($ratingDistResult)) {
            $ratingDist[$row['rating']] = $row['count'];
        }
        ?>
        
        <div class="row mb-4">
          <div class="col-md-6">
            <h6>Rating Distribution:</h6>
            <div class="rating-bars">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <?php
                $count = $ratingDist[$i] ?? 0;
                $percentage = $totalFeedback > 0 ? ($count / $totalFeedback) * 100 : 0;
                ?>
                <div class="d-flex align-items-center mb-2">
                  <span class="rating-label"><?= $i ?> ★</span>
                  <div class="progress flex-grow-1 mx-2" style="height: 20px;">
                    <div class="progress-bar bg-warning" style="width: <?= $percentage ?>%"></div>
                  </div>
                  <span class="rating-count"><?= $count ?></span>
                </div>
              <?php endfor; ?>
            </div>
          </div>
          
          <div class="col-md-6">
            <h6>Overall Statistics:</h6>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-value"><?= $avgRating ?></div>
                <div class="stat-label">Average Rating</div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?= $totalFeedback ?></div>
                <div class="stat-label">Total Reviews</div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?= round(($ratingDist[4] ?? 0) + ($ratingDist[5] ?? 0)) ?></div>
                <div class="stat-label">Positive (4-5★)</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Feedback Messages -->
        <h6>Recent Feedback:</h6>
        <?php if (empty($feedbackMessages)): ?>
          <div class="alert alert-info">No feedback messages yet.</div>
        <?php else: ?>
          <div class="feedback-list">
            <?php foreach ($feedbackMessages as $feedback): ?>
              <div class="feedback-item border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <strong><?= htmlspecialchars($feedback['user_email']) ?></strong>
                    <div class="rating-stars">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>">
                          <i class="bi bi-star-fill"></i>
                        </span>
                      <?php endfor; ?>
                      <span class="ms-1 text-muted">(<?= $feedback['rating'] ?>/5)</span>
                    </div>
                  </div>
                  <small class="text-muted"><?= htmlspecialchars($feedback['created_at']) ?></small>
                </div>
                <div class="feedback-message">
                  <?= nl2br(htmlspecialchars($feedback['message'])) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <style>
    .rating-label {
      width: 40px;
      font-size: 14px;
    }
    
    .rating-count {
      width: 30px;
      text-align: right;
      font-size: 14px;
    }
    
    .stats-grid {
      display: flex;
      gap: 20px;
    }
    
    .stat-item {
      text-align: center;
    }
    
    .stat-value {
      font-size: 24px;
      font-weight: bold;
      color: #f4623a;
    }
    
    .stat-label {
      font-size: 12px;
      color: #6c757d;
      text-transform: uppercase;
    }
    
    .feedback-item {
      background: #f8f9fa;
    }
    
    .feedback-item:hover {
      background: #e9ecef;
    }
    
    .rating-stars {
      font-size: 14px;
    }
    
    .feedback-message {
      font-style: italic;
      color: #495057;
    }
  </style>

</body>
</html>
