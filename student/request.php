<?php
// student/request.php — View a single request and its reply thread
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student   = currentStudent();
$uid       = currentUserId();
$requestId = (int)($_GET['id'] ?? 0);
$error     = '';

if ($requestId <= 0) {
    header('Location: /student/dashboard.php');
    exit;
}

$conn = getConnection();

// Fetch request — must belong to this student
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     WHERE r.requestID = ? AND s.userID = ?
     LIMIT 1'
);
$stmt->bind_param('ii', $requestId, $uid);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    $conn->close();
    header('Location: /student/dashboard.php');
    exit;
}

// Helper: recursively fetch replies (BFS by parentID chain)
// For simplicity we fetch all replies for this request flat and display in order.
function fetchReplies(mysqli $conn, int $requestId): array {
    // Fetch all replies that originate from this request (isFromRequest=1, parentID=requestID)
    // plus all replies chained from those (isFromRequest=0, parentID=replyID of a reply in this thread)
    // Simple approach: fetch all replies and filter — for small threads this is fine.
    $stmt = $conn->prepare(
        'SELECT rp.replyID, rp.parentID, rp.userID, rp.datetime, rp.message, rp.isFromRequest,
                u.fname, u.lname, u.userType
         FROM Reply rp
         JOIN User u ON u.userID = rp.userID
         ORDER BY rp.datetime ASC'
    );
    $stmt->execute();
    $all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Build a set of reply IDs that belong to this request's thread
    $belongsToRequest = []; // replyID => true
    foreach ($all as $r) {
        if ($r['isFromRequest'] && (int)$r['parentID'] === $requestId) {
            $belongsToRequest[$r['replyID']] = true;
        }
    }
    // Expand: any reply whose parent is in the set also belongs
    $changed = true;
    while ($changed) {
        $changed = false;
        foreach ($all as $r) {
            if (!isset($belongsToRequest[$r['replyID']]) && isset($belongsToRequest[$r['parentID']])) {
                $belongsToRequest[$r['replyID']] = true;
                $changed = true;
            }
        }
    }

    return array_values(array_filter($all, fn($r) => isset($belongsToRequest[$r['replyID']])));
}

$replies = fetchReplies($conn, $requestId);

// Handle delete request
if (isset($_POST['delete_request'])) {
    $stmt = $conn->prepare(
        'DELETE r FROM Request r
         JOIN Student s ON s.studID = r.studID
         WHERE r.requestID = ? AND s.userID = ?'
    );
    $stmt->bind_param('ii', $requestId, $uid);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: /student/dashboard.php?deleted=1');
    exit;
}

$conn->close();

function formatDate(string $dt): string {
    return date('M j, Y · g:i A', strtotime($dt));
}

$pageTitle = 'Request #' . $request['requestID'];
include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
    <header class="topbar">
        <a class="topbar-logo" href="/student/dashboard.php">
            <img src="/public/assets/cit-logo.png" alt="CIT-U" onerror="this.style.display='none'">
            <span>
                Voucher Request System
                <small>CIT-University</small>
            </span>
        </a>
        <div class="topbar-actions">
            <div class="topbar-user">
                <strong><?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?></strong>
                Student
            </div>
            <a href="/student/logout.php" class="topbar-logout">Log Out</a>
        </div>
    </header>

    <main class="page-content">
        <a href="/student/dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if (!empty($_GET['submitted'])): ?>
            <div class="alert alert-success">Request submitted successfully!</div>
        <?php endif; ?>

        <!-- Request detail -->
        <div class="detail-id">
            Request #<?= $request['requestID'] ?>
            <?php if ($request['isAccomplished']): ?>
                <span class="badge badge-accomplished">✓ Accomplished</span>
            <?php else: ?>
                <span class="badge badge-ongoing">⏳ Ongoing</span>
            <?php endif; ?>
        </div>

        <div class="detail-card">
            <div class="detail-card-meta">
                <span>📅 <?= formatDate($request['datetime']) ?></span>
            </div>
            <div class="detail-card-msg">
                <?= $request['message'] ? nl2br(htmlspecialchars($request['message'])) : '<em style="color:var(--grey-400)">No message provided.</em>' ?>
            </div>
        </div>

        <!-- Delete request -->
        <form method="POST" style="margin-bottom:1.5rem;">
            <button type="submit" name="delete_request"
                    class="btn btn-sm btn-danger"
                    data-confirm="Delete this request? This cannot be undone.">
                🗑 Delete Request
            </button>
        </form>

        <!-- Reply thread -->
        <div class="replies-section">
            <h3 class="section-title mb-2">Replies</h3>

            <div class="reply-thread" id="reply-thread">
                <?php if (empty($replies)): ?>
                    <div class="empty-state">No replies yet.</div>
                <?php else: ?>
                    <?php foreach ($replies as $reply): ?>
                        <?php
                        $isMe     = (int)$reply['userID'] === $uid;
                        $roleClass = $reply['userType'] === 'S' ? 'student' : 'tsg';
                        $initial  = strtoupper($reply['fname'][0] ?? '?');
                        ?>
                        <div class="reply-item">
                            <div class="reply-avatar <?= $roleClass ?>"><?= $initial ?></div>
                            <div class="reply-bubble <?= $isMe ? 'from-me' : '' ?>">
                                <div class="reply-bubble-meta">
                                    <span>
                                        <?= htmlspecialchars($reply['fname'] . ' ' . $reply['lname']) ?>
                                        <?= $reply['userType'] === 'T' ? '<em style="color:var(--gold)">· TSG</em>' : '' ?>
                                    </span>
                                    <span><?= formatDate($reply['datetime']) ?></span>
                                </div>
                                <div class="reply-bubble-msg">
                                    <?= nl2br(htmlspecialchars($reply['message'] ?? '')) ?>
                                </div>
                                <?php if ($isMe): ?>
                                    <form method="POST" action="/student/delete-reply.php" style="margin-top:.5rem;">
                                        <input type="hidden" name="reply_id" value="<?= (int)$reply['replyID'] ?>">
                                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                        <button type="submit" class="reply-delete-btn"
                                                data-confirm="Delete this reply?">✕ Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Compose reply -->
            <div class="reply-compose">
                <form id="reply-form" method="POST" action="/student/post-reply.php">
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                    <input type="hidden" name="parent_id"  value="<?= $requestId ?>">
                    <input type="hidden" name="is_from_request" value="1">
                    <textarea name="message" class="autoresize" placeholder="Write a reply…" rows="3" required></textarea>
                    <div class="reply-compose-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
