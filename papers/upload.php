<?php
require_once __DIR__ . '/../includes/header.php';

// Auth Guard: Only logged-in users can access the upload form
if (!isset($_SESSION['user'])) {
    header("Location: ../authentication/login.php");
    exit;
}

$message = "";
$messageType = "";

// Fetch categories from the database for the dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_paper'])) {
    $title         = trim($_POST['title'] ?? '');
    $category_id   = intval($_POST['category_id'] ?? 0);
    $research_type = trim($_POST['research_type'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $user_id       = $_SESSION['user']['id'];

    // Basic Validation
    if (empty($title) || $category_id <= 0 || empty($research_type) || empty($academic_year) || !isset($_FILES['pdf_file'])) {
        $message = "Please fill in all required fields and select a PDF file.";
        $messageType = "danger";
    } else {
        $file = $_FILES['pdf_file'];

        // File Validation
        $fileName     = $file['name'];
        $fileTmpName  = $file['tmp_name'];
        $fileSize     = $file['size'];
        $fileError    = $file['error'];
        $fileExt      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['pdf'];
        $maxSizeBytes      = 15 * 1024 * 1024; // 15 MB Limit

        if (!in_array($fileExt, $allowedExtensions)) {
            $message = "Invalid file format. Only PDF files (.pdf) are allowed.";
            $messageType = "danger";
        } elseif ($fileSize > $maxSizeBytes) {
            $message = "File size exceeds the 15 MB maximum limit.";
            $messageType = "danger";
        } elseif ($fileError !== 0) {
            $message = "An error occurred while uploading the file. Code: " . $fileError;
            $messageType = "danger";
        } else {
            // Target Directory setup
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename to avoid overwrites
            $uniqueFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
            $targetFilePath = $uploadDir . $uniqueFileName;

            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                // Insert research record with 'Pending' status
                $insertStmt = $pdo->prepare("
                    INSERT INTO papers (user_id, category_id, title, research_type, academic_year, file_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')
                ");
                $insertStmt->execute([$user_id, $category_id, $title, $research_type, $academic_year, $uniqueFileName]);

                $message = "Your research study has been submitted successfully and is currently <strong>Pending Review</strong> by an administrator.";
                $messageType = "success";
            } else {
                $message = "Failed to save the uploaded file to the server destination.";
                $messageType = "danger";
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Navigation Back Button -->
            <div class="mb-3">
                <a href="browse.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left"></i> Back to Research Portal
                </a>
            </div>

            <!-- Main Upload Form Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white p-4 rounded-top">
                    <h4 class="fw-bold mb-1"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Submit Research Manuscript</h4>
                    <p class="mb-0 small opacity-90">Upload completed student investigatory projects, capstone studies, or research papers to Project SPARK.</p>
                </div>

                <div class="card-body p-4">
                    <!-- Notification Alerts -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <!-- Research Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Research Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Enter full research project title..." required>
                        </div>

                        <!-- Category Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category / Field <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="" selected disabled>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Research Type -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Research Type <span class="text-danger">*</span></label>
                            <select name="research_type" class="form-select" required>
                                <option value="" selected disabled>Select Type</option>
                                <option value="Investigatory Project">Mathematical Investigatory Model Project</option>
                                <option value="Capstone Study">Capstone Study</option>
                                <option value="Research Paper">Research Paper</option>
                                <option value="Innovation Project">Innovation Project</option>
                            </select>
                        </div>

                        <!-- Academic Year -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" name="academic_year" class="form-control" placeholder="e.g., 2025-2026" required>
                        </div>

                        <!-- PDF Manuscript Upload -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Upload PDF Document <span class="text-danger">*</span></label>
                            <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                            <small class="text-muted">Accepted format: PDF only (Max: 15 MB)</small>
                        </div>

                        <!-- Info Box -->
                        <div class="col-12">
                            <div class="bg-light p-3 rounded border small text-muted">
                                <i class="bi bi-info-circle text-primary me-1"></i>
                                <strong>Note:</strong> All submitted manuscripts will undergo moderation review by system administrators or research advisers prior to public publishing on the portal.
                            </div>
                        </div>

                        <!-- Submission Button -->
                        <div class="col-12 text-end pt-2">
                            <button type="submit" name="submit_paper" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-send-check me-1"></i> Submit Paper
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>