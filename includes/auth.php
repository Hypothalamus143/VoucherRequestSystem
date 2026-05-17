<?php
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure the visitor is a logged-in student.
 * Redirects to login if not authenticated.
 */
function requireTSG() {
    if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'T') {
        header('Location: /index.php');
        exit;
    }
}

function requireStudent() {
    if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'S') {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Return the currently logged-in user's ID, or null.
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Return the student row for the current session, or null.
 */
function currentStudent(): ?array {
    $uid = currentUserId();
    if ($uid === null) return null;

    $conn = getConnection();
    $stmt = $conn->prepare(
        'SELECT s.studID, s.yearLevel, u.fname, u.mname, u.lname
         FROM Student s
         JOIN User u ON u.userID = s.userID
         WHERE s.userID = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result ?: null;
}

/**
 * Hash a password for storage.
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify a password against its hash.
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}