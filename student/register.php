<?php
// student/register.php — Student Registration
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /student/dashboard.php');
    exit;
}

$errors = [];
$fields = ['student_id'=>'','fname'=>'','mname'=>'','lname'=>'','password'=>'','confirm_password'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    foreach (array_keys($fields) as $key) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($fields['student_id'] === '')           $errors[] = 'Student ID is required.';
    elseif (!ctype_digit($fields['student_id'])) $errors[] = 'Student ID must be numeric.';
    if ($fields['fname'] === '')    $errors[] = 'First name is required.';
    if ($fields['lname'] === '')    $errors[] = 'Last name is required.';
    if (strlen($fields['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($fields['password'] !== $fields['confirm_password']) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $conn = getConnection();
        $studID = (int) $fields['student_id'];

        // Check student ID uniqueness
        $stmt = $conn->prepare('SELECT studID FROM Student WHERE studID = ? LIMIT 1');
        $stmt->bind_param('i', $studID);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $errors[] = 'That Student ID is already registered.';
        } else {
            $conn->begin_transaction();
            try {
                // Insert User
                $hash  = hashPassword($fields['password']);
                $mname = $fields['mname'] !== '' ? $fields['mname'] : null;

                $stmt = $conn->prepare(
                    'INSERT INTO User (fname, mname, lname, password, userType)
                     VALUES (?, ?, ?, ?, "S")'
                );
                $stmt->bind_param('ssss', $fields['fname'], $mname, $fields['lname'], $hash);
                $stmt->execute();
                $userID = $conn->insert_id;
                $stmt->close();

                // Insert Student using the provided student ID
                $yearLevel = 1;
                $stmt = $conn->prepare(
                    'INSERT INTO Student (studID, userID, yearLevel) VALUES (?, ?, ?)'
                );
                $stmt->bind_param('iii', $studID, $userID, $yearLevel);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $conn->close();

                header('Location: /index.php?registered=1');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $conn->close();
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card wide">
        <div class="auth-logo">
            <img src="/public/assets/cit-logo.png" alt="CIT-U Logo" onerror="this.style.display='none'">
            <h1>Voucher Request System</h1>
            <p>Create Student Account</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/student/register.php" novalidate>
            <div class="form-grid">
                <!-- Row 1 -->
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input class="form-control" type="text" id="student_id" name="student_id"
                           placeholder="e.g. 2100123" required pattern="\d+"
                           value="<?= htmlspecialchars($fields['student_id']) ?>">
                </div>
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input class="form-control" type="text" id="fname" name="fname"
                           placeholder="First name" required
                           value="<?= htmlspecialchars($fields['fname']) ?>">
                </div>

                <!-- Row 2 -->
                <div class="form-group">
                    <label for="mname">Middle Name <span class="text-muted">(optional)</span></label>
                    <input class="form-control" type="text" id="mname" name="mname"
                           placeholder="Middle name"
                           value="<?= htmlspecialchars($fields['mname']) ?>">
                </div>
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input class="form-control" type="text" id="lname" name="lname"
                           placeholder="Last name" required
                           value="<?= htmlspecialchars($fields['lname']) ?>">
                </div>

                <!-- Row 3 -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password"
                           placeholder="Min. 8 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-2">Register</button>
        </form>

        <hr class="divider">
        <a href="/index.php" class="btn btn-ghost">Sign In Instead</a>
    </div>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>