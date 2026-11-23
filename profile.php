<?php
require_once __DIR__ . '/includes/header.php';

// Auth Guard: Redirect unauthenticated users to login page
if (!isset($_SESSION['user'])) {
    header("Location: authentication/login.php");
    exit;
}

$userId = $_SESSION['user']['id'];
$message = "";
$messageType = "";

// Handle Profile Info Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username  = trim($_POST['username'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');

    if (empty($username)) {
        $message = "Username cannot be empty.";
        $messageType = "danger";
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET username = ?, id_number = ? WHERE id = ?");
        if ($updateStmt->execute([$username, $id_number, $userId])) {
            $_SESSION['user']['username']  = $username;
            $_SESSION['user']['id_number'] = $id_number;
            $message = "Profile details updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update profile details.";
            $messageType = "danger";
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Fetch user password hash
    $passQuery = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $passQuery->execute([$userId]);
    $userRecord = $passQuery->fetch();

    if (!password_verify($currentPassword, $userRecord['password'])) {
        $message = "Incorrect current password.";
        $messageType = "danger";
    } elseif (strlen($newPassword) < 6) {
        $message = "New password must be at least 6 characters long.";
        $messageType = "danger";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
        $messageType = "danger";
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updatePassStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($updatePassStmt->execute([$newHash, $userId])) {
            $message = "Password updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update password.";
            $messageType = "danger";
        }
    }
}

// Fetch Latest User Profile Information
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch();

// Fetch Submitted Research Papers by User
$papersStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM papers p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.user_id = ? 
    ORDER BY p.id DESC
");
$papersStmt->execute([$userId]);
$userPapers = $papersStmt->fetchAll();
?>

<div class="container py-4">
    <!-- Feedback Alerts -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- User Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 80px; height: 80px; font-size: 2.2rem;">
                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                </div>
                <div class="text-center text-md-start flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                        <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($currentUser['username']) ?></h3>
                        <span class="badge bg-<?= $currentUser['role'] === 'Admin' ? 'danger' : ($currentUser['role'] === 'Teacher / Faculty' ? 'primary' : 'info') ?>">
                            <?= htmlspecialchars($currentUser['role']) ?>
                        </span>
                    </div>
                    <p class="text-muted mb-2"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($currentUser['email']) ?></p>
                    <div class="d-flex flex-wrap gap-3 text-muted small justify-content-center justify-content-md-start">
                        <span><i class="bi bi-card-heading me-1"></i> ID: <strong><?= htmlspecialchars($currentUser['id_number'] ?? 'N/A') ?></strong></span>
                        <span><i class="bi bi-calendar3 me-1"></i> Member Since: <strong><?= date('M d, Y', strtotime($currentUser['created_at'])) ?></strong></span>
                    </div>
                </div>
                <div class="text-md-end">
                    <a href="papers/upload.php" class="btn btn-warning fw-semibold px-3">
                        <i class="bi bi-upload me-1"></i> Submit Paper
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Account Settings Column -->
        <div class="col-lg-4">
            <!-- Edit Profile Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-gear me-2 text-primary"></i> Edit Profile</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">ID / LRN Number</label>
                            <input type="text" name="id_number" class="form-control" value="<?= htmlspecialchars($currentUser['id_number'] ?? '') ?>" placeholder="e.g., 123456789">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Email Address</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($currentUser['email']) ?>" readonly>
                            <small class="text-muted">Email cannot be changed.</small>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Save Profile Details
                        </button>
                    </form>
                </div>
            </div>

            <!-- Security / Password Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-lock me-2 text-danger"></i> Security</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Research Submissions Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-journal-text me-2 text-primary"></i> My Submissions
                    </h5>
                    <span class="badge bg-secondary"><?= count($userPapers) ?> Paper(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($userPapers)): ?>
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-file-earmark-plus display-3 text-muted opacity-50 mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark">No Submissions Found</h5>
                            <p class="text-muted small mb-3">You have not submitted any research papers to the portal yet.</p>
                            <a href="papers/upload.php" class="btn btn-primary btn-sm px-3 fw-semibold">
                                <i class="bi bi-upload"></i> Upload Research Now
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Title</th>
                                        <th>Category</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userPapers as $paper): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($paper['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($paper['research_type']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= htmlspecialchars($paper['category_name'] ?? 'General') ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= htmlspecialchars($paper['academic_year']) ?>
                                            </td>
                                            <td>
                                                <?php if ($paper['status'] === 'Approved'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                        <i class="bi bi-check-circle me-1"></i> Approved
                                                    </span>
                                                <?php elseif ($paper['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                        <i class="bi bi-x-circle me-1"></i> Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                        <i class="bi bi-clock me-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="uploads/<?= htmlspecialchars($paper['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold">
                                                    <i class="bi bi-file-earmark-pdf"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>