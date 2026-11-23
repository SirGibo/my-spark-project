<?php
// 1. Process Database, File Upload & Redirect Logic BEFORE ANY HTML OUTPUT
require_once '../config/db.php';

// Auth Guard: Ensure user is logged in and is a Teacher / Faculty member
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Teacher / Faculty') {
    header("Location: ../authentication/login.php");
    exit;
}

$error = "";

// Fetch categories for the dropdown menu
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $abstract      = trim($_POST['abstract'] ?? '');
    $category_id   = intval($_POST['category_id'] ?? 0);
    $research_type = $_POST['research_type'] ?? 'Action Research';
    $academic_year = trim($_POST['academic_year'] ?? '');
    $user_id       = $_SESSION['user']['id'];

    // Retrieve co-authors (Safely handle both string & array inputs)
    $authors_raw = $_POST['authors'] ?? '';
    if (is_array($authors_raw)) {
        $authors_array = array_map('trim', $authors_raw);
    } else {
        $authors_array = array_map('trim', explode(',', (string)$authors_raw));
    }

    // Basic Validation
    if (empty($title) || empty($abstract) || empty($academic_year) || $category_id <= 0) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_FILES['paper_file']) || $_FILES['paper_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a valid PDF, DOC, or DOCX document to upload.";
    } else {
        // Handle Document Upload
        $fileTmpPath   = $_FILES['paper_file']['tmp_name'];
        $fileName      = $_FILES['paper_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['pdf', 'doc', 'docx'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = "Invalid file format. Only PDF, DOC, and DOCX files are permitted.";
        } else {
            // Generate unique filename
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';

            // Create uploads directory if missing
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Insert Paper metadata into DB (Default status: Pending)
                $stmt = $pdo->prepare("
                    INSERT INTO papers (user_id, category_id, title, abstract, research_type, academic_year, file_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([$user_id, $category_id, $title, $abstract, $research_type, $academic_year, $newFileName]);
                $paper_id = $pdo->lastInsertId();

                // Save Co-Authors if present
                if (!empty($authors_array)) {
                    $authorStmt = $pdo->prepare("INSERT INTO paper_authors (paper_id, author_name) VALUES (?, ?)");
                    foreach ($authors_array as $authorName) {
                        if (!empty($authorName)) {
                            $authorStmt->execute([$paper_id, $authorName]);
                        }
                    }
                }

                // Redirect cleanly back to Faculty Dashboard
                header("Location: dashboard.php?uploaded=1");
                exit;
            } else {
                $error = "Failed to upload file to the server. Please check folder permissions.";
            }
        }
    }
}

// 2. Load UI Header AFTER authentication & processing logic finishes
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="col-md-8 mx-auto">
        <!-- Breadcrumb / Back Button -->
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card card-stat p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-cloud-upload-fill text-primary fs-3"></i>
                <div>
                    <h3 class="fw-bold mb-0">Submit Research Study</h3>
                    <p class="text-muted small mb-0">Upload new research for administrative review and publication.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Research Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Enhancing Reading Comprehension Using SPARK Platform" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
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
                        <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025-2026" required value="<?= htmlspecialchars($_POST['academic_year'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Co-Authors <small class="text-muted">(Comma-separated)</small></label>
                        <input type="text" name="authors" class="form-control" placeholder="e.g. Juan Dela Cruz, Maria Santos" value="<?= htmlspecialchars(is_array($_POST['authors'] ?? '') ? implode(', ', $_POST['authors']) : ($_POST['authors'] ?? '')) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Abstract / Summary <span class="text-danger">*</span></label>
                    <textarea name="abstract" class="form-control" rows="4" placeholder="Provide a brief summary of the study..." required><?= htmlspecialchars($_POST['abstract'] ?? '') ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Attach Document (PDF / DOC / DOCX) <span class="text-danger">*</span></label>
                    <input type="file" name="paper_file" class="form-control" accept=".pdf,.doc,.docx" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 fw-bold">
                        <i class="bi bi-send-check"></i> Submit Paper for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>