<?php
// student/submit-request.php — Submit a new voucher request
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$student = currentStudent();
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    // Message is nullable per schema, but UX-wise guide user to add one
    $conn = getConnection();
    $stmt = $conn->prepare(
        'INSERT INTO Request (studID, message, isAccomplished) VALUES (?, ?, 0)'
    );
    $msg = $message !== '' ? $message : null;
    $stmt->bind_param('is', $student['studID'], $msg);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        header('Location: /student/request.php?id=' . $newId . '&submitted=1');
        exit;
    } else {
        $error = 'Could not submit request. Please try again.';
        $stmt->close();
        $conn->close();
    }
}

$pageTitle = 'Submit Request';
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

        <div class="section-header mb-2">
            <h2 class="section-title">Submit Request</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="submit-card">
            <form method="POST" action="/student/submit-request.php">
                <div class="form-group mb-2">
                    <label for="message">Message</label>
                    <textarea class="form-control autoresize" id="message" name="message"
                              placeholder="Describe your voucher request…" rows="6"
                    ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <div class="text-muted mt-1">You may leave this blank if not applicable.</div>
                </div>

                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="/student/dashboard.php" class="btn btn-ghost">Cancel</a>
            </form>
        </div>
    </main>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
