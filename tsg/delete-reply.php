<?php
// tsg/delete-reply.php — Delete any reply (TSG can delete all replies)
require_once __DIR__ . '/../includes/auth.php';
requireTSG();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /tsg/dashboard.php');
    exit;
}

$replyId   = (int)($_POST['reply_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);

if ($replyId > 0) {
    $conn = getConnection();
    // TSG can delete ANY reply (no userID restriction)
    $stmt = $conn->prepare('DELETE FROM Reply WHERE replyID = ?');
    $stmt->bind_param('i', $replyId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

$redirect = $requestId > 0
    ? '/tsg/request.php?id=' . $requestId
    : '/tsg/dashboard.php';

header('Location: ' . $redirect);
exit;