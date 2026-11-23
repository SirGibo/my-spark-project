<?php
// 1. Process Database & Session logic BEFORE any HTML output
require_once '../config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Header redirects now work cleanly without error!
        if ($user['role'] === 'Admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['role'] === 'Teacher / Faculty') {
            header("Location: ../teacher/dashboard.php?logged=1");
        } elseif ($user['role'] === 'Student') {
            header("Location: ../student/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $error = "Invalid username/email or password.";
    }
}

// 2. Load UI Header AFTER authentication logic finishes
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="col-md-5 mx-auto">
        <div class="card card-stat p-4">
            <h3 class="text-center fw-bold mb-4">Login to SPARK</h3>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success py-2">Account created successfully! Please sign in.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username or Email</label>
                    <input type="text" name="login_input" class="form-control" placeholder="admin or student@deped.gov.ph" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></small>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>