<?php
// tsg/dashboard.php — TSG Dashboard (View All Student Requests)
require_once __DIR__ . '/../includes/auth.php';
requireTSG(); // You'll need to create this function

$conn = getConnection();

// Fetch ALL requests (different from student version)
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     ORDER BY r.datetime DESC'
);
$stmt->execute();
$allRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$ongoing      = array_filter($allRequests, fn($r) => !$r['isAccomplished']);
$accomplished = array_filter($allRequests, fn($r) =>  $r['isAccomplished']);

function formatDate(string $dt): string {
    return date('M j, Y · g:i A', strtotime($dt));
}

function truncateMsg(?string $msg, int $len = 70): string {
    if (!$msg) return '<em>No message</em>';
    return strlen($msg) > $len ? htmlspecialchars(substr($msg, 0, $len)) . '…' : htmlspecialchars($msg);
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="app-layout">
    <!-- Top bar -->
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

    <!-- Main content -->
    <main class="page-content">

        <!-- Ongoing Requests -->
        <div class="section-header mb-2">
            <h2 class="section-title">Ongoing Requests</h2>
            <span class="badge badge-ongoing"><?= count($ongoing) ?> active</span>
        </div>

        <div class="request-list">
            <?php if (empty($ongoing)): ?>
                <div class="empty-state">No ongoing requests.</div>
            <?php else: ?>
                <?php foreach ($ongoing as $r): ?>
                    <a class="request-card" href="/tsg/request.php?id=<?= (int)$r['requestID'] ?>">
                        <div class="request-card-icon ongoing">⏳</div>
                        <div class="request-card-body">
                            <div class="request-card-date"><?= formatDate($r['datetime']) ?></div>
                            <div class="request-card-msg"><?= truncateMsg($r['message']) ?></div>
                        </div>
                        <span class="request-card-arrow">›</span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Accomplished Requests -->
        <div class="section-header mb-2">
            <h2 class="section-title">Accomplished Requests</h2>
            <span class="badge badge-accomplished"><?= count($accomplished) ?> done</span>
        </div>

        <div class="request-list">
            <?php if (empty($accomplished)): ?>
                <div class="empty-state">No accomplished requests yet.</div>
            <?php else: ?>
                <?php foreach ($accomplished as $r): ?>
                    <a class="request-card" href="/tsg/request.php?id=<?= (int)$r['requestID'] ?>">
                        <div class="request-card-icon accomplished">✓</div>
                        <div class="request-card-body">
                            <div class="request-card-date"><?= formatDate($r['datetime']) ?></div>
                            <div class="request-card-msg"><?= truncateMsg($r['message']) ?></div>
                        </div>
                        <span class="request-card-arrow">›</span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>