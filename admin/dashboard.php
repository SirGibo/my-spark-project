<?php
require_once '../includes/header.php';

// Auth Guard: Ensure user is logged in and is an Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: ../authentication/login.php");
    exit;
}

$message = "";
$messageType = "";

// Handle Paper Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paper_action'])) {
    $paper_id = intval($_POST['paper_id']);
    $action = $_POST['paper_action']; // 'Approved' or 'Rejected'

    if (in_array($action, ['Approved', 'Rejected'])) {
        $stmt = $pdo->prepare("UPDATE papers SET status = ? WHERE id = ?");
        $stmt->execute([$action, $paper_id]);
        $message = "Paper status updated to <strong>$action</strong>.";
        $messageType = ($action === 'Approved') ? "success" : "danger";
    }
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $target_user_id = intval($_POST['delete_user_id']);
    
    // Prevent admin from deleting themselves
    if ($target_user_id !== $_SESSION['user']['id']) {
        $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del_stmt->execute([$target_user_id]);
        $message = "User deleted successfully.";
        $messageType = "warning";
    } else {
        $message = "You cannot delete your own admin account!";
        $messageType = "danger";
    }
}

// Handle User Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $target_user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $id_number = trim($_POST['id_number']);
    $new_password = $_POST['password'];

    $allowed_roles = ['Student', 'Teacher / Faculty', 'Admin'];

    if (!empty($username) && !empty($email) && in_array($role, $allowed_roles)) {
        if (!empty($new_password)) {
            // Update details WITH new hashed password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, id_number = ?, password = ? WHERE id = ?");
            $update_stmt->execute([$username, $email, $role, $id_number, $hashed_password, $target_user_id]);
        } else {
            // Update details WITHOUT touching the password
            $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, id_number = ? WHERE id = ?");
            $update_stmt->execute([$username, $email, $role, $id_number, $target_user_id]);
        }

        // Synchronize active session if admin edited their own profile
        if ($target_user_id === $_SESSION['user']['id']) {
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['role'] = $role;
        }

        $message = "User <strong>" . htmlspecialchars($username) . "</strong> updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update user. Please enter valid details.";
        $messageType = "danger";
    }
}

// System Overview Metrics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Student'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Teacher / Faculty'")->fetchColumn();

$totalPapers = $pdo->query("SELECT COUNT(*) FROM papers")->fetchColumn();
$pendingPapers = $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'Pending'")->fetchColumn();
$approvedPapers = $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'Approved'")->fetchColumn();
$rejectedPapers = $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'Rejected'")->fetchColumn();

// Fetch Pending Papers for Review
$pending_stmt = $pdo->prepare("
    SELECT p.*, u.username AS uploader_name, u.email AS uploader_email, c.name AS category_name 
    FROM papers p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'Pending' 
    ORDER BY p.id ASC
");
$pending_stmt->execute();
$pendingList = $pending_stmt->fetchAll();

// Fetch All Registered Users
$users_stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 10");
$usersList = $users_stmt->fetchAll();
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill text-primary"></i> Administrator Control Panel</h2>
            <p class="text-muted small mb-0">System overview, content moderation, and user management for Project SPARK.</p>
        </div>
        <span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-person-badge"></i> Admin Access</span>
    </div>

    <!-- Alert Banner -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- System Stats Cards -->
    <div class="row g-3 text-center mb-4">
        <div class="col-md-3">
            <div class="card card-stat p-3 border-start border-primary border-4">
                <i class="bi bi-people text-primary fs-3"></i>
                <h3 class="fw-bold my-1"><?= $totalUsers ?></h3>
                <span class="text-muted small">Total Users (<?= $totalStudents ?> Students / <?= $totalTeachers ?> Faculty)</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3 border-start border-warning border-4">
                <i class="bi bi-hourglass-split text-warning fs-3"></i>
                <h3 class="fw-bold my-1"><?= $pendingPapers ?></h3>
                <span class="text-muted small">Pending Moderation</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3 border-start border-success border-4">
                <i class="bi bi-file-earmark-check text-success fs-3"></i>
                <h3 class="fw-bold my-1"><?= $approvedPapers ?></h3>
                <span class="text-muted small">Approved Papers</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3 border-start border-danger border-4">
                <i class="bi bi-file-earmark-x text-danger fs-3"></i>
                <h3 class="fw-bold my-1"><?= $rejectedPapers ?></h3>
                <span class="text-muted small">Rejected Submissions</span>
            </div>
        </div>
    </div>

    <!-- Pending Moderation Queue -->
    <div class="card card-stat p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-circle text-warning"></i> Pending Research Papers Requiring Review</h5>
            <span class="badge bg-warning text-dark"><?= count($pendingList) ?> Awaiting Approval</span>
        </div>

        <?php if (empty($pendingList)): ?>
            <div class="text-center py-4">
                <i class="bi bi-check2-circle display-3 text-success opacity-75"></i>
                <p class="text-muted mt-2 mb-0">All research submissions have been reviewed!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Title & Details</th>
                            <th>Uploader</th>
                            <th>Category</th>
                            <th>Academic Year</th>
                            <th>File</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingList as $paper): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block"><?= htmlspecialchars($paper['title']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($paper['research_type']) ?></small>
                                </td>
                                <td>
                                    <span class="d-block fw-semibold"><?= htmlspecialchars($paper['uploader_name']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($paper['uploader_email']) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($paper['category_name'] ?? 'General') ?></span></td>
                                <td><?= htmlspecialchars($paper['academic_year']) ?></td>
                                <td>
                                    <a href="../uploads/<?= htmlspecialchars($paper['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-file-pdf"></i> View
                                    </a>
                                </td>
                                <td class="text-center">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="paper_id" value="<?= $paper['id'] ?>">
                                        <button type="submit" name="paper_action" value="Approved" class="btn btn-sm btn-success me-1">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="submit" name="paper_action" value="Rejected" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this submission?');">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Accounts Management -->
    <div class="card card-stat p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-person-gear text-primary"></i> System Users Overview</h5>
            <span class="badge bg-secondary">Recent Registrations</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>ID / Employee No.</th>
                        <th>Registered Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $usr): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($usr['username']) ?></td>
                            <td><?= htmlspecialchars($usr['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $usr['role'] === 'Admin' ? 'danger' : ($usr['role'] === 'Teacher / Faculty' ? 'primary' : 'info') ?>">
                                    <?= htmlspecialchars($usr['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($usr['id_number']) ?></td>
                            <td><?= date('M d, Y', strtotime($usr['created_at'])) ?></td>
                            <td class="text-end">
                                <!-- Edit User Button -->
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $usr['id'] ?>">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <?php if ($usr['id'] !== $_SESSION['user']['id']): ?>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user? This operation cannot be undone.');">
                                        <input type="hidden" name="delete_user_id" value="<?= $usr['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">Active Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade text-start" id="editUserModal<?= $usr['id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= $usr['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold" id="editUserModalLabel<?= $usr['id'] ?>">
                                                <i class="bi bi-person-gear text-primary"></i> Edit Account Details
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Username</label>
                                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($usr['username']) ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Email Address</label>
                                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usr['email']) ?>" required>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Role</label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="Student" <?= $usr['role'] === 'Student' ? 'selected' : '' ?>>Student</option>
                                                        <option value="Teacher / Faculty" <?= $usr['role'] === 'Teacher / Faculty' ? 'selected' : '' ?>>Teacher / Faculty</option>
                                                        <option value="Admin" <?= $usr['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">ID / Employee No.</label>
                                                    <input type="text" name="id_number" class="form-control" value="<?= htmlspecialchars($usr['id_number']) ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">New Password</label>
                                                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                                <small class="text-muted">Only fill this field if you want to reset the user's password.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_user" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>