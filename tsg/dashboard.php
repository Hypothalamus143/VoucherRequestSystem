<?php
// tsg/dashboard.php — TSG Dashboard (View All Student Requests)
require_once __DIR__ . '/../includes/auth.php';
requireTSG(); // You'll need to create this function

$conn = getConnection();

// Pagination settings
$requestsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $requestsPerPage;

// Fetch total counts for pagination
$countStmt = $conn->prepare('SELECT COUNT(*) as total FROM Request WHERE isAccomplished = 0');
$countStmt->execute();
$totalOngoing = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$countStmt = $conn->prepare('SELECT COUNT(*) as total FROM Request WHERE isAccomplished = 1');
$countStmt->execute();
$totalAccomplished = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Fetch ongoing requests with pagination
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     WHERE r.isAccomplished = 0
     ORDER BY r.datetime DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('ii', $requestsPerPage, $offset);
$stmt->execute();
$ongoing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch accomplished requests with pagination
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     WHERE r.isAccomplished = 1
     ORDER BY r.datetime DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('ii', $requestsPerPage, $offset);
$stmt->execute();
$accomplished = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Calculate total pages for each section
$totalOngoingPages = ceil($totalOngoing / $requestsPerPage);
$totalAccomplishedPages = ceil($totalAccomplished / $requestsPerPage);

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
        
        <!-- Side by side columns container -->
        <div style="display: flex; gap: 2rem; align-items: flex-start;">
            
            <!-- Ongoing Requests Column -->
            <div style="flex: 1; min-width: 0;">
                <div class="section-header mb-2">
                    <h2 class="section-title">Ongoing Requests</h2>
                    <span class="badge badge-ongoing"><?= $totalOngoing ?> active</span>
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
                
                <!-- Pagination for Ongoing -->
                <?php if ($totalOngoingPages > 1): ?>
                <div class="pagination" style="margin-top: 1rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&tab=ongoing" class="pagination-link">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalOngoingPages; $i++): ?>
                        <a href="?page=<?= $i ?>&tab=ongoing" class="pagination-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalOngoingPages): ?>
                        <a href="?page=<?= $page + 1 ?>&tab=ongoing" class="pagination-link">Next ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Accomplished Requests Column -->
            <div style="flex: 1; min-width: 0;">
                <div class="section-header mb-2">
                    <h2 class="section-title">Accomplished Requests</h2>
                    <span class="badge badge-accomplished"><?= $totalAccomplished ?> done</span>
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
                
                <!-- Pagination for Accomplished -->
                <?php if ($totalAccomplishedPages > 1): ?>
                <div class="pagination" style="margin-top: 1rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <?php 
                    $accomplishedPage = isset($_GET['accomplished_page']) ? (int)$_GET['accomplished_page'] : 1;
                    $accomplishedPage = max(1, $accomplishedPage);
                    ?>
                    <?php if ($accomplishedPage > 1): ?>
                        <a href="?accomplished_page=<?= $accomplishedPage - 1 ?>" class="pagination-link">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalAccomplishedPages; $i++): ?>
                        <a href="?accomplished_page=<?= $i ?>" class="pagination-link <?= $i === $accomplishedPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($accomplishedPage < $totalAccomplishedPages): ?>
                        <a href="?accomplished_page=<?= $accomplishedPage + 1 ?>" class="pagination-link">Next ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
        </div>

    </main>
</div>

<script src="/public/js/main.js"></script>
<style>
/* Optional: Add these styles if your existing CSS doesn't have pagination styles */
.pagination-link {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    text-decoration: none;
    color: #4a5568;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.pagination-link:hover {
    background: #edf2f7;
    border-color: #cbd5e0;
}

.pagination-link.active {
    background: #4299e1;
    color: white;
    border-color: #4299e1;
}
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>