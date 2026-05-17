<?php
// tsg_register.php — TSG Registration (place this as tsg_register.php or rename as needed)
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

$errors = [];
$fields = ['emp_id'=>'', 'fname'=>'', 'mname'=>'', 'lname'=>'', 'password'=>'', 'confirm_password'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    foreach (array_keys($fields) as $key) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($fields['emp_id'] === '')           
        $errors[] = 'Employee ID is required.';
    elseif (!ctype_digit($fields['emp_id'])) 
        $errors[] = 'Employee ID must be numeric.';
    
    if ($fields['fname'] === '')    
        $errors[] = 'First name is required.';
    
    if ($fields['lname'] === '')    
        $errors[] = 'Last name is required.';
    
    if (strlen($fields['password']) < 8) 
        $errors[] = 'Password must be at least 8 characters.';
    
    if ($fields['password'] !== $fields['confirm_password']) 
        $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $conn = getConnection();
        $empID = (int) $fields['emp_id'];

        // Check employee ID uniqueness in TSG table
        $stmt = $conn->prepare('SELECT empID FROM TSG WHERE empID = ? LIMIT 1');
        $stmt->bind_param('i', $empID);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $errors[] = 'That Employee ID is already registered.';
        } else {
            $conn->begin_transaction();
            try {
                // Insert User with type 'T' for TSG
                $hash  = password_hash($fields['password'], PASSWORD_DEFAULT);
                $mname = $fields['mname'] !== '' ? $fields['mname'] : null;

                $stmt = $conn->prepare(
                    'INSERT INTO User (fname, mname, lname, password, userType)
                     VALUES (?, ?, ?, ?, "T")'
                );
                $stmt->bind_param('ssss', $fields['fname'], $mname, $fields['lname'], $hash);
                $stmt->execute();
                $userID = $conn->insert_id;
                $stmt->close();

                // Insert TSG using the provided employee ID
                $stmt = $conn->prepare(
                    'INSERT INTO TSG (empID, userID) VALUES (?, ?)'
                );
                $stmt->bind_param('ii', $empID, $userID);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $conn->close();

                // Redirect to login with success message
                header('Location: /index.php?tsg_registered=1');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $conn->close();
                $errors[] = 'Registration failed. Please try again. Error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register TSG Account';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card wide">
        <div class="auth-logo">
            <img src="/public/assets/cit-logo.png" alt="CIT-U Logo" onerror="this.style.display='none'">
            <h1>Voucher Request System</h1>
            <p>Register TSG Account</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success">TSG Account created! You can now log in.</div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-grid">
                <!-- Row 1 -->
                <div class="form-group">
                    <label for="emp_id">Employee ID</label>
                    <input class="form-control" type="text" id="emp_id" name="emp_id"
                           placeholder="e.g. 1001" required pattern="\d+"
                           value="<?= htmlspecialchars($fields['emp_id']) ?>">
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

            <button type="submit" class="btn btn-primary mt-2">Register TSG Account</button>
        </form>

        <hr class="divider">
        <a href="/index.php" class="btn btn-ghost">Back to Login</a>
        <a href="/student/register.php" class="btn btn-ghost">Register as Student</a>
    </div>
</div>

<script src="/public/js/main.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>