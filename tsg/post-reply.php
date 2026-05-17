<?php
// tsg/post-reply.php — Handle reply submission for TSG
require_once __DIR__ . '/../includes/auth.php';
requireTSG();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$uid           = currentUserId();
$parentId      = (int)($_POST['parent_id'] ?? 0);
$isFromRequest = (int)($_POST['is_from_request'] ?? 0);
$message       = trim($_POST['message'] ?? '');

if ($parentId <= 0 || $message === '') {
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

$conn = getConnection();

// If isFromRequest=1, verify the request exists (TSG can reply to any request)
if ($isFromRequest) {
    $stmt = $conn->prepare(
        'SELECT requestID FROM Request WHERE requestID = ? LIMIT 1'
    );
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exists) {
        $conn->close();
        http_response_code(403);
        echo json_encode(['error' => 'Request not found.']);
        exit;
    }
}

// Get TSG user info for response
$stmt = $conn->prepare('SELECT fname, lname FROM User WHERE userID = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$tsg = $stmt->get_result()->fetch_assoc();
$stmt->close();

// FIXED: Correct parameter types - 'i' for integer, 's' for string
$stmt = $conn->prepare(
    'INSERT INTO Reply (parentID, userID, message, isFromRequest) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('iisi', $parentId, $uid, $message, $isFromRequest);

if ($stmt->execute()) {
    $replyId = $conn->insert_id;
    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'reply'   => [
            'replyID'       => $replyId,
            'parentID'      => $parentId,
            'userID'        => $uid,
            'datetime'      => date('Y-m-d H:i:s'),
            'message'       => $message,
            'fname'         => $tsg['fname'],
            'lname'         => $tsg['lname'],
            'user_type'     => 'T',
            'isFromRequest' => $isFromRequest,
        ],
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    echo json_encode(['error' => 'Could not save reply: ' . $error]);
}
?>