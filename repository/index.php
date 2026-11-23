<?php
require_once '../includes/header.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM papers p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'Approved'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.keywords LIKE ? OR p.abstract LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
if (!empty($category)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <!-- Left Filter Panel -->
        <div class="col-md-3">
            <div class="card card-stat p-3 mb-4">
                <h5 class="fw-bold"><i class="bi bi-funnel"></i> Filters</h5>
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Keywords</label>
                        <input type="text" name="search" class="form-control" placeholder="Title or topic..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
                </form>
            </div>
        </div>

        <!-- Main Results Grid -->
        <div class="col-md-9">
            <h3 class="fw-bold mb-1">Research Repository</h3>
            <p class="text-muted small">Showing <?= count($papers) ?> approved research publication(s)</p>

            <?php if (empty($papers)): ?>
                <div class="alert alert-warning py-3">
                    No research papers found matching your criteria.
                </div>
            <?php else: ?>
                <?php foreach($papers as $p): ?>
                    <div class="card card-stat p-3 mb-3">
                        <span class="badge bg-primary w-auto align-self-start mb-2"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span>
                        <h5 class="fw-bold text-dark"><?= htmlspecialchars($p['title']) ?></h5>
                        <p class="text-muted small"><?= substr(htmlspecialchars($p['abstract']), 0, 180) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-secondary"><i class="bi bi-calendar"></i> <?= $p['academic_year'] ?> | <i class="bi bi-eye"></i> <?= $p['views'] ?> views</small>
                            <a href="../uploads/<?= $p['file_path'] ?>" download class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>