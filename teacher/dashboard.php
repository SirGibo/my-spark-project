<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Get Stats
$total = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE user_id = ?"); $total->execute([$user_id]); $totalCount = $total->fetchColumn();
$approved = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE user_id = ? AND status = 'Approved'"); $approved->execute([$user_id]); $approvedCount = $approved->fetchColumn();
$pending = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE user_id = ? AND status = 'Pending'"); $pending->execute([$user_id]); $pendingCount = $pending->fetchColumn();
$rejected = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE user_id = ? AND status = 'Rejected'"); $rejected->execute([$user_id]); $rejectedCount = $rejected->fetchColumn();
$views = $pdo->prepare("SELECT IFNULL(SUM(views), 0) FROM papers WHERE user_id = ?"); $views->execute([$user_id]); $viewsCount = $views->fetchColumn();
$downloads = $pdo->prepare("SELECT IFNULL(SUM(downloads), 0) FROM papers WHERE user_id = ?"); $downloads->execute([$user_id]); $downloadsCount = $downloads->fetchColumn();

// Get User Papers
$stmt = $pdo->prepare("SELECT * FROM papers WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$myPapers = $stmt->fetchAll();
?>

<div class="container py-4">
    <?php if (isset($_GET['logged'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Welcome back, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="fw-bold mb-0">Teacher Dashboard</h2>
            <p class="text-muted small">Manage your published research papers, track performance, and upload new studies.</p>
        </div>
        <a href="upload.php" class="btn btn-primary px-3"><i class="bi bi-cloud-upload"></i> Upload Research</a>
    </div>

    <!-- 6 Metric Cards matching Screenshot (164) -->
    <div class="row g-3 text-center mb-4">
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-card-checklist text-primary fs-3"></i>
                <h3 class="fw-bold my-1"><?= $totalCount ?></h3>
                <span class="text-muted small">Total Papers</span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-check-circle text-success fs-3"></i>
                <h3 class="fw-bold my-1"><?= $approvedCount ?></h3>
                <span class="text-muted small">Approved</span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-hourglass-split text-warning fs-3"></i>
                <h3 class="fw-bold my-1"><?= $pendingCount ?></h3>
                <span class="text-muted small">Pending</span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-x-circle text-danger fs-3"></i>
                <h3 class="fw-bold my-1"><?= $rejectedCount ?></h3>
                <span class="text-muted small">Rejected</span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-eye text-info fs-3"></i>
                <h3 class="fw-bold my-1"><?= $viewsCount ?></h3>
                <span class="text-muted small">Total Views</span>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3">
                <i class="bi bi-download text-dark fs-3"></i>
                <h3 class="fw-bold my-1"><?= $downloadsCount ?></h3>
                <span class="text-muted small">Downloads</span>
            </div>
        </div>
    </div>

    <!-- Managed Publications Table or Empty State -->
    <div class="card card-stat p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-archive text-primary"></i> My Managed Publications</h5>
            <span class="badge bg-secondary"><?= count($myPapers) ?> Record(s)</span>
        </div>

        <?php if (empty($myPapers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam display-1 text-muted opacity-50"></i>
                <p class="text-muted mt-2">You haven't uploaded any research papers yet.</p>
                <a href="upload.php" class="btn btn-outline-primary">Upload Your First Research</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Academic Year</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPapers as $paper): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($paper['title']) ?></td>
                                <td><?= htmlspecialchars($paper['academic_year']) ?></td>
                                <td><?= htmlspecialchars($paper['research_type']) ?></td>
                                <td><span class="badge bg-<?= $paper['status'] === 'Approved' ? 'success' : ($paper['status'] === 'Pending' ? 'warning' : 'danger') ?>"><?= $paper['status'] ?></span></td>
                                <td><?= $paper['views'] ?></td>
                                <td><?= $paper['downloads'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>