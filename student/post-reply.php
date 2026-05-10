<?php
// student/post-reply.php — Handle reply submission (AJAX-capable)
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$uid           = currentUserId();
$student       = currentStudent();
$parentId      = (int)($_POST['parent_id'] ?? 0);
$isFromRequest = (int)($_POST['is_from_request'] ?? 0);
$message       = trim($_POST['message'] ?? '');

if ($parentId <= 0 || $message === '') {
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

// If isFromRequest=1, verify the request belongs to this student
if ($isFromRequest) {
    $conn = getConnection();
    $stmt = $conn->prepare(
        'SELECT r.requestID FROM Request r
         JOIN Student s ON s.studID = r.studID
         WHERE r.requestID = ? AND s.userID = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $parentId, $uid);
    $stmt->execute();
    $owns = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$owns) {
        $conn->close();
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden.']);
        exit;
    }
} else {
    $conn = getConnection();
}

$stmt = $conn->prepare(
    'INSERT INTO Reply (parentID, userID, message, isFromRequest) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('isis', $parentId, $uid, $message, $isFromRequest);

if ($stmt->execute()) {
    $replyId = $conn->insert_id;
    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'reply'   => [
            'replyID'   => $replyId,
            'parentID'  => $parentId,
            'userID'    => $uid,
            'datetime'  => date('Y-m-d H:i:s'),
            'message'   => $message,
            'fname'     => $student['fname'],
            'lname'     => $student['lname'],
            'user_type' => 'S',
        ],
    ]);
} else {
    $stmt->close();
    $conn->close();
    echo json_encode(['error' => 'Could not save reply.']);
}
