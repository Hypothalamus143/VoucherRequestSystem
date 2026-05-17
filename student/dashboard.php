<?php
// student/dashboard.php — Student Dashboard
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = currentStudent();
$uid     = currentUserId();

$conn = getConnection();

// Pagination settings
$requestsPerPage = 10;
$ongoingPage = isset($_GET['ongoing_page']) ? (int)$_GET['ongoing_page'] : 1;
$ongoingPage = max(1, $ongoingPage);
$accomplishedPage = isset($_GET['accomplished_page']) ? (int)$_GET['accomplished_page'] : 1;
$accomplishedPage = max(1, $accomplishedPage);
$ongoingOffset = ($ongoingPage - 1) * $requestsPerPage;
$accomplishedOffset = ($accomplishedPage - 1) * $requestsPerPage;

// Fetch total counts for this student
$countStmt = $conn->prepare(
    'SELECT COUNT(*) as total 
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     WHERE s.userID = ? AND r.isAccomplished = 0'
);
$countStmt->bind_param('i', $uid);
$countStmt->execute();
$totalOngoing = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$countStmt = $conn->prepare(
    'SELECT COUNT(*) as total 
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     WHERE s.userID = ? AND r.isAccomplished = 1'
);
$countStmt->bind_param('i', $uid);
$countStmt->execute();
$totalAccomplished = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Fetch ongoing requests for this student with pagination
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     WHERE s.userID = ? AND r.isAccomplished = 0
     ORDER BY r.datetime DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('iii', $uid, $requestsPerPage, $ongoingOffset);
$stmt->execute();
$ongoing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch accomplished requests for this student with pagination
$stmt = $conn->prepare(
    'SELECT r.requestID, r.datetime, r.message, r.isAccomplished
     FROM Request r
     JOIN Student s ON s.studID = r.studID
     WHERE s.userID = ? AND r.isAccomplished = 1
     ORDER BY r.datetime DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('iii', $uid, $requestsPerPage, $accomplishedOffset);
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

    <!-- Main content -->
    <main class="page-content">
        
        <!-- Side by side columns container -->
        <div class="dashboard-columns">
            
            <!-- Ongoing Requests Column -->
            <div class="dashboard-column">
                <div class="section-header mb-2">
                    <h2 class="section-title">Ongoing Requests</h2>
                    <span class="badge badge-ongoing"><?= $totalOngoing ?> active</span>
                </div>

                <div class="request-list">
                    <?php if (empty($ongoing)): ?>
                        <div class="empty-state">No ongoing requests.</div>
                    <?php else: ?>
                        <?php foreach ($ongoing as $r): ?>
                            <a class="request-card" href="/student/request.php?id=<?= (int)$r['requestID'] ?>">
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
                <div class="pagination">
                    <?php if ($ongoingPage > 1): ?>
                        <a href="?ongoing_page=<?= $ongoingPage - 1 ?>&accomplished_page=<?= $accomplishedPage ?>" class="pagination-link">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalOngoingPages; $i++): ?>
                        <a href="?ongoing_page=<?= $i ?>&accomplished_page=<?= $accomplishedPage ?>" class="pagination-link <?= $i === $ongoingPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($ongoingPage < $totalOngoingPages): ?>
                        <a href="?ongoing_page=<?= $ongoingPage + 1 ?>&accomplished_page=<?= $accomplishedPage ?>" class="pagination-link">Next ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Accomplished Requests Column -->
            <div class="dashboard-column">
                <div class="section-header mb-2">
                    <h2 class="section-title">Accomplished Requests</h2>
                    <span class="badge badge-accomplished"><?= $totalAccomplished ?> done</span>
                </div>

                <div class="request-list">
                    <?php if (empty($accomplished)): ?>
                        <div class="empty-state">No accomplished requests yet.</div>
                    <?php else: ?>
                        <?php foreach ($accomplished as $r): ?>
                            <a class="request-card" href="/student/request.php?id=<?= (int)$r['requestID'] ?>">
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
                <div class="pagination">
                    <?php if ($accomplishedPage > 1): ?>
                        <a href="?ongoing_page=<?= $ongoingPage ?>&accomplished_page=<?= $accomplishedPage - 1 ?>" class="pagination-link">‹ Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalAccomplishedPages; $i++): ?>
                        <a href="?ongoing_page=<?= $ongoingPage ?>&accomplished_page=<?= $i ?>" class="pagination-link <?= $i === $accomplishedPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($accomplishedPage < $totalAccomplishedPages): ?>
                        <a href="?ongoing_page=<?= $ongoingPage ?>&accomplished_page=<?= $accomplishedPage + 1 ?>" class="pagination-link">Next ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
        </div>

    </main>

    <!-- FAB -->
    <div class="fab-area">
        <a href="/student/submit-request.php" class="fab">+ Submit Request</a>
    </div>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>