<?php
// index.php — Login (Student or TSG)
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'S') {
        header('Location: /student/dashboard.php');
    } else {
        header('Location: /tsg/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($loginId === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!ctype_digit($loginId)) {
        $error = 'ID must be numeric.';
    } else {
        $conn = getConnection();
        $row = null;
        
        // First check if it's a Student (using studID)
        $stmt = $conn->prepare(
            'SELECT u.userID, u.password, u.userType, s.studID as loginId
             FROM User u
             JOIN Student s ON s.userID = u.userID
             WHERE s.studID = ? LIMIT 1'
        );
        $stmt->bind_param('i', $loginId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // If not found as student, check if it's TSG (using empID)
        if (!$row) {
            $stmt = $conn->prepare(
                'SELECT u.userID, u.password, u.userType, t.empID as loginId
                 FROM User u
                 JOIN TSG t ON t.userID = u.userID
                 WHERE t.empID = ? LIMIT 1'
            );
            $stmt->bind_param('i', $loginId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        $conn->close();

        if ($row && verifyPassword($password, $row['password'])) {
            $_SESSION['user_id'] = $row['userID'];
            $_SESSION['user_type'] = $row['userType'];
            $_SESSION['login_id'] = $row['loginId']; // Store the actual studID or empID

            // Redirect based on user type
            if ($row['userType'] === 'S') {
                header('Location: /student/dashboard.php');
            } elseif ($row['userType'] === 'T') {
                header('Location: /tsg/dashboard.php');
            } else {
                $error = 'Invalid user type.';
                session_destroy();
            }
            exit;
        } else {
            $error = 'Invalid ID or password.';
        }
    }
}

$pageTitle = 'Log In';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="/public/assets/cit-logo.png" alt="CIT-U Logo" onerror="this.style.display='none'">
            <h1>Voucher Request System</h1>
            <p>Cebu Institute of Technology – University</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($_GET['registered'])): ?>
            <div class="alert alert-success">Account created! You can now log in.</div>
        <?php endif; ?>

        <form method="POST" action="/index.php" novalidate>
            <div class="form-group mb-2">
                <label for="login_id">Student ID / Employee ID</label>
                <input class="form-control" type="text" id="login_id" name="login_id"
                       placeholder="Enter your Student ID or Employee ID" required pattern="\d+"
                       value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>">
            </div>

            <div class="form-group mb-2">
                <label for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary">Log In</button>
        </form>

        <hr class="divider">
        <a href="/student/register.php" class="btn btn-ghost">Create Student Account</a>
    </div>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>