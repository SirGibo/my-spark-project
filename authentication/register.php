<?php
// 1. Process registration logic BEFORE any HTML is output
require_once '../config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $id_number = trim($_POST['id_number']);
    $role      = $_POST['role'] ?? '';
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    // Basic Validations
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ['Student', 'Teacher / Faculty'])) {
        $error = "Please select a valid account role.";
    } else {
        // Check if username or email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);

        if ($check->rowCount() > 0) {
            $error = "Username or email is already registered.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, role, id_number, password) VALUES (?, ?, ?, ?, ?)");
            $executed = $stmt->execute([$username, $email, $role, $id_number, $hashed_password]);

            if ($executed) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// 2. Load UI Header AFTER processing logic
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="col-md-6 mx-auto">
        <div class="card card-stat p-4">
            <h3 class="text-center fw-bold mb-4">Create a SPARK Account</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Full Name / Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. Juan Dela Cruz" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="user@deped.gov.ph" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Account Role</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>Select Role</option>
                            <option value="Student" <?= (($_POST['role'] ?? '') === 'Student') ? 'selected' : '' ?>>Student</option>
                            <option value="Teacher / Faculty" <?= (($_POST['role'] ?? '') === 'Teacher / Faculty') ? 'selected' : '' ?>>Teacher / Faculty</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">LRN ID / Employee No.</label>
                        <input type="text" name="id_number" class="form-control" placeholder="e.g. 2026-0001" required value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password" title="Show/Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password" title="Show/Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mt-2">Create Account</button>
            </form>

            <div class="text-center mt-3">
                <small class="text-muted">Already have an account? <a href="login.php" class="text-decoration-none">Sign in here</a></small>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>