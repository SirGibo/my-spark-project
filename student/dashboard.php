<?php
// 1. Process Database, Authenticate & Actions BEFORE ANY HTML OUTPUT
require_once '../config/db.php';

// Auth Guard: Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$error = "";

// --- ACTION 1: Handle Bookmark Removal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_bookmark_id'])) {
    $bookmark_id = intval($_POST['remove_bookmark_id']);
    $del = $pdo->prepare("DELETE FROM bookmarks WHERE id = ? AND user_id = ?");
    $del->execute([$bookmark_id, $user_id]);
    header("Location: dashboard.php?action=removed");
    exit;
}

// --- ACTION 2: Handle Research Paper Upload by Student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_upload_paper'])) {
    $title         = trim($_POST['title'] ?? '');
    $abstract      = trim($_POST['abstract'] ?? '');
    $category_id   = intval($_POST['category_id'] ?? 0);
    $research_type = $_POST['research_type'] ?? 'Action Research';
    $academic_year = trim($_POST['academic_year'] ?? '');

    // Retrieve co-authors (Safely handles string or array)
    $authors_raw = $_POST['authors'] ?? '';
    if (is_array($authors_raw)) {
        $authors_array = array_map('trim', $authors_raw);
    } else {
        $authors_array = array_map('trim', explode(',', (string)$authors_raw));
    }

    if (empty($title) || empty($abstract) || empty($academic_year) || $category_id <= 0) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_FILES['paper_file']) || $_FILES['paper_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please attach a valid PDF, DOC, or DOCX document.";
    } else {
        $fileTmpPath   = $_FILES['paper_file']['tmp_name'];
        $fileName      = $_FILES['paper_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts   = ['pdf', 'doc', 'docx'];

        if (!in_array($fileExtension, $allowedExts)) {
            $error = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
        } else {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadDir   = '../uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                // Insert Paper metadata into DB (Default status: Pending for admin review)
                $stmt = $pdo->prepare("
                    INSERT INTO papers (user_id, category_id, title, abstract, research_type, academic_year, file_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([$user_id, $category_id, $title, $abstract, $research_type, $academic_year, $newFileName]);
                $paper_id = $pdo->lastInsertId();

                // Insert Co-Authors
                if (!empty($authors_array)) {
                    $authorStmt = $pdo->prepare("INSERT INTO paper_authors (paper_id, author_name) VALUES (?, ?)");
                    foreach ($authors_array as $authorName) {
                        if (!empty($authorName)) {
                            $authorStmt->execute([$paper_id, $authorName]);
                        }
                    }
                }

                header("Location: dashboard.php?uploaded=1");
                exit;
            } else {
                $error = "Failed to save file to server. Please check directory permissions.";
            }
        }
    }
}

// --- FETCH DATA FOR DASHBOARD ---
// Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Student Stats
$saved_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
$saved_count_stmt->execute([$user_id]);
$savedCount = $saved_count_stmt->fetchColumn();

$authored_count_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id) 
    FROM papers p 
    LEFT JOIN paper_authors pa ON p.id = pa.paper_id 
    WHERE p.user_id = ? OR pa.author_name LIKE ?
");
$authored_count_stmt->execute([$user_id, "%$username%"]);
$authoredCount = $authored_count_stmt->fetchColumn();

$approved_count_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id) 
    FROM papers p 
    LEFT JOIN paper_authors pa ON p.id = pa.paper_id 
    WHERE (p.user_id = ? OR pa.author_name LIKE ?) AND p.status = 'Approved'
");
$approved_count_stmt->execute([$user_id, "%$username%"]);
$approvedCount = $approved_count_stmt->fetchColumn();

$downloads_stmt = $pdo->prepare("
    SELECT IFNULL(SUM(p.downloads), 0) 
    FROM papers p 
    LEFT JOIN paper_authors pa ON p.id = pa.paper_id 
    WHERE p.user_id = ? OR pa.author_name LIKE ?
");
$downloads_stmt->execute([$user_id, "%$username%"]);
$downloadsCount = $downloads_stmt->fetchColumn();

// Saved Papers List
$saved_papers_stmt = $pdo->prepare("
    SELECT b.id AS bookmark_id, b.created_at AS saved_at, p.*, c.name AS category_name
    FROM bookmarks b
    JOIN papers p ON b.paper_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$saved_papers_stmt->execute([$user_id]);
$savedPapers = $saved_papers_stmt->fetchAll();

// My Submissions & Co-Authored Studies
$my_papers_stmt = $pdo->prepare("
    SELECT DISTINCT p.*, c.name AS category_name 
    FROM papers p 
    LEFT JOIN paper_authors pa ON p.id = pa.paper_id 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ? OR pa.author_name LIKE ? 
    ORDER BY p.id DESC
");
$my_papers_stmt->execute([$user_id, "%$username%"]);
$myPapers = $my_papers_stmt->fetchAll();

// 2. Load UI Header AFTER processing logic completes
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Action Feedback Alerts -->
    <?php if (isset($_GET['uploaded'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> Research paper submitted successfully! It is now pending approval.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'removed'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-trash-fill me-1"></i> Paper removed from saved bookmarks.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-0">Student Dashboard</h2>
            <p class="text-muted small mb-0">Submit research, track saved papers, and monitor your publications.</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Modal Trigger Button -->
            <button type="button" class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#uploadResearchModal">
                <i class="bi bi-cloud-upload-fill me-1"></i> Submit Research
            </button>
            <a href="../repository/search.php" class="btn btn-outline-secondary px-3">
                <i class="bi bi-search"></i> Browse
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 text-center mb-4">
        <div class="col-md-3">
            <div class="card card-stat p-3">
                <i class="bi bi-bookmark-star text-primary fs-3"></i>
                <h3 class="fw-bold my-1"><?= $savedCount ?></h3>
                <span class="text-muted small">Saved Papers</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3">
                <i class="bi bi-journal-text text-info fs-3"></i>
                <h3 class="fw-bold my-1"><?= $authoredCount ?></h3>
                <span class="text-muted small">My Submissions</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3">
                <i class="bi bi-check-circle text-success fs-3"></i>
                <h3 class="fw-bold my-1"><?= $approvedCount ?></h3>
                <span class="text-muted small">Approved Papers</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3">
                <i class="bi bi-download text-warning fs-3"></i>
                <h3 class="fw-bold my-1"><?= $downloadsCount ?></h3>
                <span class="text-muted small">Total Downloads</span>
            </div>
        </div>
    </div>

    <!-- Section 1: My Submissions & Co-Authored Studies -->
    <div class="card card-stat p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-folder-check text-primary me-2"></i>My Submissions & Research Papers
            </h5>
            <span class="badge bg-secondary"><?= count($myPapers) ?> Submitted</span>
        </div>

        <?php if (empty($myPapers)): ?>
            <div class="text-center py-4">
                <i class="bi bi-inbox display-3 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-3">You haven't uploaded any research studies yet.</p>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadResearchModal">
                    <i class="bi bi-cloud-upload me-1"></i> Submit Your First Research
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPapers as $paper): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($paper['title']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($paper['category_name'] ?? 'General') ?></span></td>
                                <td><?= htmlspecialchars($paper['research_type']) ?></td>
                                <td><?= htmlspecialchars($paper['academic_year']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $paper['status'] === 'Approved' ? 'success' : ($paper['status'] === 'Pending' ? 'warning text-dark' : 'danger') ?>">
                                        <?= $paper['status'] ?>
                                    </span>
                                </td>
                                <td><?= $paper['views'] ?></td>
                                <td><?= $paper['downloads'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Saved Research Papers -->
    <div class="card card-stat p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-bookmark-fill text-primary me-2"></i>Saved Research Papers
            </h5>
            <span class="badge bg-secondary"><?= count($savedPapers) ?> Saved</span>
        </div>

        <?php if (empty($savedPapers)): ?>
            <div class="text-center py-4">
                <i class="bi bi-journal-x display-4 text-muted opacity-50"></i>
                <p class="text-muted mt-2 mb-0">You haven't bookmarked any research papers yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Academic Year</th>
                            <th>Saved Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedPapers as $paper): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($paper['title']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($paper['category_name'] ?? 'General') ?></span></td>
                                <td><?= htmlspecialchars($paper['academic_year']) ?></td>
                                <td><?= date('M d, Y', strtotime($paper['saved_at'])) ?></td>
                                <td class="text-end">
                                    <a href="../uploads/<?= htmlspecialchars($paper['file_path']) ?>" download class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Remove this paper from saved bookmarks?');">
                                        <input type="hidden" name="remove_bookmark_id" value="<?= $paper['bookmark_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Bookmark">
                                            <i class="bi bi-trash"></i>
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
</div>

<!-- Upload Research Modal -->
<div class="modal fade" id="uploadResearchModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="uploadModalLabel">
                    <i class="bi bi-cloud-upload-fill text-primary me-2"></i>Submit Research Paper
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action_upload_paper" value="1">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Research Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Assessing Student Performance in Digital Learning Environments" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="" disabled selected>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Research Type <span class="text-danger">*</span></label>
                            <select name="research_type" class="form-select" required>
                                <option value="Action Research">Action Research</option>
                                <option value="Basic Research">Basic Research</option>
                                <option value="Applied Research">Applied Research</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025-2026" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Co-Authors <small class="text-muted">(Comma-separated)</small></label>
                            <input type="text" name="authors" class="form-control" placeholder="e.g. Juan Dela Cruz, Maria Santos">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Abstract / Summary <span class="text-danger">*</span></label>
                        <textarea name="abstract" class="form-control" rows="4" placeholder="Brief summary of your research paper..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Attach Document (PDF / DOC / DOCX) <span class="text-danger">*</span></label>
                        <input type="file" name="paper_file" class="form-control" accept=".pdf,.doc,.docx" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-send-check me-1"></i> Submit Paper
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>