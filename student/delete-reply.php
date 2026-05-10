<?php
// student/delete-reply.php — Delete a reply owned by the current student
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/dashboard.php');
    exit;
}

$uid       = currentUserId();
$replyId   = (int)($_POST['reply_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);

if ($replyId > 0) {
    $conn = getConnection();
    // Only delete if this reply belongs to the current user
    $stmt = $conn->prepare(
        'DELETE FROM Reply WHERE replyID = ? AND userID = ?'
    );
    $stmt->bind_param('ii', $replyId, $uid);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

$redirect = $requestId > 0
    ? '/student/request.php?id=' . $requestId
    : '/student/dashboard.php';

header('Location: ' . $redirect);
exit;
