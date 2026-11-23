<?php
require_once __DIR__ . '/../includes/header.php';

// Get Search and Filter Parameters
$search   = trim($_GET['search'] ?? '');
$category = intval($_GET['category'] ?? 0);
$type     = trim($_GET['type'] ?? '');
$year     = trim($_GET['year'] ?? '');

// Build Dynamic SQL Query for Approved Papers
$conditions = ["p.status = 'Approved'"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(p.title LIKE :search1 OR p.research_type LIKE :search2 OR u.username LIKE :search3)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($category > 0) {
    $conditions[] = "p.category_id = :category";
    $params[':category'] = $category;
}

if (!empty($type)) {
    $conditions[] = "p.research_type = :type";
    $params[':type'] = $type;
}

if (!empty($year)) {
    $conditions[] = "p.academic_year = :year";
    $params[':year'] = $year;
}

$whereSql = "WHERE " . implode(" AND ", $conditions);

$sql = "
    SELECT p.*, u.username AS uploader_name, u.email AS uploader_email, c.name AS category_name 
    FROM papers p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    $whereSql 
    ORDER BY p.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll();

// Fetch Filter Dropdown Data
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$typesList = $pdo->query("SELECT DISTINCT research_type FROM papers WHERE status = 'Approved' ORDER BY research_type ASC")->fetchAll();
$yearsList = $pdo->query("SELECT DISTINCT academic_year FROM papers WHERE status = 'Approved' ORDER BY academic_year DESC")->fetchAll();
?>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="bg-primary bg-gradient text-white p-4 p-md-5 rounded-3 mb-4 shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-2"><i class="bi bi-journal-check"></i> Browse Research Portal</h1>
                <p class="lead mb-0 opacity-90">
                    Explore approved student investigatory projects, capstone studies, and research outputs from Regional Science High School for Region II.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-light text-dark fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-file-earmark-text text-primary"></i> <?= count($papers) ?> Published Studies
                </span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="browse.php" class="row g-3">
                <!-- Search Input -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted"><i class="bi bi-search"></i> Search Keyword</label>
                    <input type="text" name="search" class="form-control" placeholder="Search title, author, or keywords..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- Category Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted"><i class="bi bi-folder2-open"></i> Category</label>
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category === $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Research Type Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted"><i class="bi bi-bookmarks"></i> Research Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($typesList as $t): ?>
                            <option value="<?= htmlspecialchars($t['research_type']) ?>" <?= $type === $t['research_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['research_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Academic Year Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted"><i class="bi bi-calendar-event"></i> Academic Year</label>
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($yearsList as $y): ?>
                            <option value="<?= htmlspecialchars($y['academic_year']) ?>" <?= $year === $y['academic_year'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($y['academic_year']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="col-12 d-flex gap-2 justify-content-end mt-3">
                    <?php if (!empty($search) || $category > 0 || !empty($type) || !empty($year)): ?>
                        <a href="browse.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Search Info Bar -->
    <?php if (!empty($search) || $category > 0 || !empty($type) || !empty($year)): ?>
        <div class="d-flex align-items-center justify-content-between mb-3 text-muted small">
            <span>Showing results for current filters</span>
            <span class="badge bg-secondary"><?= count($papers) ?> result(s) found</span>
        </div>
    <?php endif; ?>

    <!-- Research Cards Results List -->
    <?php if (empty($papers)): ?>
        <div class="card border-0 shadow-sm text-center py-5 my-3">
            <div class="card-body">
                <i class="bi bi-search-heart display-1 text-muted opacity-50 mb-3 d-block"></i>
                <h4 class="fw-bold text-dark">No Research Studies Found</h4>
                <p class="text-muted small">No approved research papers match your specified search keywords or filter criteria.</p>
                <a href="browse.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-arrow-counterclockwise"></i> Reset Search</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($papers as $paper): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm card-stat d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <!-- Badges Header -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($paper['category_name'] ?? 'General') ?>
                                </span>
                                <span class="badge bg-light text-dark border">
                                    A.Y. <?= htmlspecialchars($paper['academic_year']) ?>
                                </span>
                            </div>

                            <!-- Research Title -->
                            <h5 class="card-title fw-bold text-dark my-2 lh-base">
                                <?= htmlspecialchars($paper['title']) ?>
                            </h5>

                            <!-- Metadata Subtext -->
                            <p class="card-text text-muted small mb-3">
                                <i class="bi bi-journal-text me-1"></i> <strong>Type:</strong> <?= htmlspecialchars($paper['research_type']) ?>
                            </p>

                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                                <div class="small text-muted">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <span><?= htmlspecialchars($paper['uploader_name']) ?></span>
                                </div>
                                <a href="../uploads/<?= htmlspecialchars($paper['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold">
                                    <i class="bi bi-file-earmark-pdf"></i> View Paper
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>