<?php
require_once __DIR__ . '/includes/header.php';

// Fetch Platform Metrics
$papersCount     = $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'Approved'")->fetchColumn();
$usersCount      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Fetch Latest 6 Approved Research Papers
$recentPapersStmt = $pdo->prepare("
    SELECT p.*, u.username AS uploader_name, c.name AS category_name 
    FROM papers p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'Approved' 
    ORDER BY p.id DESC 
    LIMIT 6
");
$recentPapersStmt->execute();
$recentPapers = $recentPapersStmt->fetchAll();
?>

<!-- Hero & Search Banner -->
<div class="bg-dark text-white py-5 mb-4 position-relative" style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%);">
    <div class="container py-4 text-center">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-uppercase mb-3">
            <img src="/spark/Logo.jpg" alt="Project SPARK Logo" height="32" class="rounded">
            <i class="bi bi-shield-check"></i> Institutional Research Portal
        </span>
        <h1 class="display-4 fw-bold mb-2">Project SPARK</h1>
        <p class="lead opacity-75 mx-auto mb-4" style="max-width: 650px;">
            Student Portal for Accessing Research Knowledge — Discover, reference, and build upon scholarly works at Regional Science High School for Region II.
        </p>

        <!-- Search Bar Engine -->
        <form action="papers/browse.php" method="GET" class="col-lg-8 mx-auto">
            <div class="input-group input-group-lg shadow">
                <span class="input-group-text bg-body border-0 text-body-secondary ps-3">
                    <i class="bi bi-search fs-5"></i>
                </span>
                <input type="text" name="search" class="form-control bg-body text-body border-0 fs-6 py-3" placeholder="Search by research title, author, keyword, or topic...">
                <button class="btn btn-warning px-4 fw-bold text-dark" type="submit">
                    Search Studies
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container mb-5">
    <!-- Session Alert Banners -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- System Statistics Bar -->
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card card-stat bg-body-tertiary p-4 border-0 shadow-sm">
                <i class="bi bi-journal-bookmark-fill text-primary display-5 mb-2"></i>
                <h2 class="fw-bold mb-0 text-body"><?= $papersCount ?></h2>
                <span class="text-body-secondary small text-uppercase fw-semibold">Published Papers</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-body-tertiary p-4 border-0 shadow-sm">
                <i class="bi bi-people-fill text-success display-5 mb-2"></i>
                <h2 class="fw-bold mb-0 text-body"><?= $usersCount ?></h2>
                <span class="text-body-secondary small text-uppercase fw-semibold">Registered Researchers</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-body-tertiary p-4 border-0 shadow-sm">
                <i class="bi bi-folder-fill text-warning display-5 mb-2"></i>
                <h2 class="fw-bold mb-0 text-body"><?= $categoriesCount ?></h2>
                <span class="text-body-secondary small text-uppercase fw-semibold">Research Fields</span>
            </div>
        </div>
    </div>
</div>

<!-- Recently Uploaded Research Section -->
<div class="container py-3 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-body"><i class="bi bi-clock-history text-primary me-2"></i> Recently Published Studies</h3>
            <p class="text-body-secondary small mb-0">Explore the latest approved student research submissions</p>
        </div>
        <a href="papers/browse.php" class="btn btn-outline-primary btn-sm fw-semibold">
            View All Studies <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($recentPapers)): ?>
        <div class="card bg-body-tertiary border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-journal-x display-3 text-body-secondary opacity-50 mb-3 d-block"></i>
                <h5 class="fw-bold text-body">No Uploaded Studies Yet</h5>
                <p class="text-body-secondary small mb-3">Be the first student or researcher to publish a manuscript on Project SPARK.</p>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="papers/upload.php" class="btn btn-primary btn-sm px-3 fw-semibold"><i class="bi bi-upload"></i> Submit Paper</a>
                <?php else: ?>
                    <a href="authentication/login.php" class="btn btn-primary btn-sm px-3 fw-semibold"><i class="bi bi-box-arrow-in-right"></i> Log In to Submit</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($recentPapers as $paper): ?>
                <?php 
                    // Check if current logged-in user can delete this paper
                    $canDelete = isset($_SESSION['user']) && (
                        $_SESSION['user']['role'] === 'Admin' || 
                        $_SESSION['user']['id'] == $paper['user_id']
                    );
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 bg-body-tertiary border-0 shadow-sm card-stat d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <!-- Category & Year Badges -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($paper['category_name'] ?? 'General') ?>
                                </span>
                                <span class="badge bg-body-secondary text-body border">
                                    A.Y. <?= htmlspecialchars($paper['academic_year']) ?>
                                </span>
                            </div>

                            <!-- Research Title -->
                            <h5 class="card-title fw-bold text-body my-2 lh-base">
                                <?= htmlspecialchars($paper['title']) ?>
                            </h5>

                            <!-- Research Details -->
                            <p class="card-text text-body-secondary small mb-3">
                                <i class="bi bi-journal-text me-1"></i> <strong>Type:</strong> <?= htmlspecialchars($paper['research_type']) ?>
                            </p>

                            <!-- Card Footer Actions -->
                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                                <div class="small text-body-secondary text-truncate me-auto">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <span><?= htmlspecialchars($paper['uploader_name']) ?></span>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <!-- Citation Trigger -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="showCitationModal('<?= htmlspecialchars(addslashes($paper['title']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($paper['uploader_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars($paper['academic_year']) ?>')">
                                        <i class="bi bi-quote"></i> Cite
                                    </button>

                                    <!-- PDF View -->
                                    <a href="uploads/<?= htmlspecialchars($paper['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold">
                                        <i class="bi bi-file-earmark-pdf"></i> View
                                    </a>

                                    <!-- Delete Button (Shown only to Uploader or Admin) -->
                                    <?php if ($canDelete): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDeletePaper(<?= $paper['id'] ?>, '<?= htmlspecialchars(addslashes($paper['title']), ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Deletion Confirmation Modal -->
<div class="modal fade" id="deletePaperModal" tabindex="-1" aria-labelledby="deletePaperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="deletePaperModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Remove Research Paper
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-2 fs-5 text-body">Are you sure you want to delete this research paper?</p>
                <p class="text-danger fw-semibold small mb-3" id="deletePaperTitleName"></p>
                <p class="text-body-secondary small mb-0">This action is permanent and will remove the PDF document from the system server.</p>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <form action="papers/delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="paper_id" id="deletePaperIdInput">
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold px-3">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Deletion Modal Script -->
<script>
    function confirmDeletePaper(paperId, paperTitle) {
        document.getElementById('deletePaperIdInput').value = paperId;
        document.getElementById('deletePaperTitleName').innerText = `"${paperTitle}"`;
        const modal = new bootstrap.Modal(document.getElementById('deletePaperModal'));
        modal.show();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>