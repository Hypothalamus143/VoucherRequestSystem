<?php
// tsg/request.php — View a single request and its reply thread (TSG Version)
require_once __DIR__ . '/../includes/auth.php';
requireTSG();

$requestId = (int)($_GET['id'] ?? 0);
$error     = '';

if ($requestId <= 0) {
    header('Location: /tsg/dashboard.php');
    exit;
}

$conn = getConnection();

// Fetch request (no student restriction - TSG can view any request)
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished,
            s.studID, u.fname, u.lname, u.mname
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     JOIN User u ON u.userID = s.userID
     WHERE r.requestID = ?
     LIMIT 1'
);
$stmt->bind_param('i', $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    $conn->close();
    header('Location: /tsg/dashboard.php');
    exit;
}

// Helper: recursively fetch replies (BFS by parentID chain)
function fetchReplies(mysqli $conn, int $requestId): array {
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
    $belongsToRequest = [];
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

// Handle mark as accomplished
if (isset($_POST['mark_accomplished'])) {
    $stmt = $conn->prepare('UPDATE Request SET isAccomplished = 1 WHERE requestID = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $stmt->close();
    $request['isAccomplished'] = 1; // Update local variable
}

// Handle delete request (TSG can also delete requests)
if (isset($_POST['delete_request'])) {
    $stmt = $conn->prepare('DELETE FROM Request WHERE requestID = ?');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: /tsg/dashboard.php?deleted=1');
    exit;
}

$conn->close();

function formatDate(string $dt): string {
    $d = new DateTime($dt, new DateTimeZone('Asia/Manila'));
    return $d->format('M j, Y · g:i A');
}

$pageTitle = 'Request #' . $request['requestID'];
include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
    <header class="topbar">
        <a class="topbar-logo" href="/tsg/dashboard.php">
            <img src="/public/assets/cit-logo.png" alt="CIT-U" onerror="this.style.display='none'">
            <span>
                Voucher Request System
                <small>CIT-University</small>
            </span>
        </a>
        <div class="topbar-actions">
            <div class="topbar-user">
                <strong>TSG Staff</strong>
                Administrator
            </div>
            <a href="/tsg/logout.php" class="topbar-logout">Log Out</a>
        </div>
    </header>

    <main class="page-content">
        <a href="/tsg/dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if (!empty($_GET['submitted'])): ?>
            <div class="alert alert-success">Reply submitted successfully!</div>
        <?php endif; ?>

        <?php if (isset($_POST['mark_accomplished'])): ?>
            <div class="alert alert-success">Request marked as accomplished!</div>
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
                <span>👤 Student: <?= htmlspecialchars($request['fname'] . ' ' . $request['lname']) ?> (ID: <?= $request['studID'] ?>)</span>
            </div>
            <div class="detail-card-msg">
                <?= $request['message'] ? nl2br(htmlspecialchars($request['message'])) : '<em style="color:var(--grey-400)">No message provided.</em>' ?>
            </div>
        </div>

        <!-- Mark as Accomplished Button (only if not already accomplished) -->
        <?php if (!$request['isAccomplished']): ?>
            <form method="POST" style="margin-bottom:1.5rem;">
                <button type="submit" name="mark_accomplished"
                        class="btn btn-sm btn-success"
                        data-confirm="Mark this request as accomplished?">
                    ✓ Mark as Accomplished
                </button>
            </form>
        <?php endif; ?>

        <!-- Delete request button -->
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
                <!-- This will be populated by JavaScript -->
            </div>

            <!-- Compose reply - ADD THIS BACK -->
            <div class="reply-compose">
                <div id="replying-to" style="display:none; font-size:.8rem; color:var(--maroon); margin-bottom:.5rem; align-items:center; gap:.5rem;">
                    <span id="replying-to-label"></span>
                    <button id="cancel-reply-to" style="background:none;border:none;cursor:pointer;color:var(--grey-400);font-size:.8rem;">✕ cancel</button>
                </div>
                <form id="reply-form" method="POST" action="/tsg/post-reply.php">
                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                    <input type="hidden" name="parent_id"  value="<?= $requestId ?>" id="parent-id-input">
                    <input type="hidden" name="is_from_request" value="1" id="is-from-request-input">
                    <textarea name="message" class="autoresize" placeholder="Write a reply…" rows="3" required></textarea>
                    <div class="reply-compose-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// On page load, fetch and render replies
document.addEventListener('DOMContentLoaded', function() {
    const replies = <?= json_encode($replies) ?>;
    const threadContainer = document.getElementById('reply-thread');
    if (threadContainer && replies) {
        threadContainer.innerHTML = buildThreadedReplies(replies);
    }
});
</script>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>