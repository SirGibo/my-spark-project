<?php
require_once '../includes/header.php';

$schools = $pdo->query("SELECT * FROM schools")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $searched = true;
    $title = $_GET['title'] ?? '';
    $keywords = $_GET['keywords'] ?? '';
    $category_id = $_GET['category_id'] ?? '';
    $school_id = $_GET['school_id'] ?? '';
    $department_id = $_GET['department_id'] ?? '';
    $sort = $_GET['sort'] ?? 'DESC';

    $sql = "SELECT * FROM papers WHERE status = 'Approved'";
    $params = [];

    if ($title) { $sql .= " AND title LIKE ?"; $params[] = "%$title%"; }
    if ($keywords) { $sql .= " AND (keywords LIKE ? OR abstract LIKE ?)"; $params[] = "%$keywords%"; $params[] = "%$keywords%"; }
    if ($category_id) { $sql .= " AND category_id = ?"; $params[] = $category_id; }
    if ($school_id) { $sql .= " AND school_id = ?"; $params[] = $school_id; }
    if ($department_id) { $sql .= " AND department_id = ?"; $params[] = $department_id; }

    $sql .= " ORDER BY created_at " . ($sort === 'ASC' ? 'ASC' : 'DESC');

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}
?>

<div class="container py-4">
    <div class="card card-stat p-4 mb-4">
        <h3 class="fw-bold mb-1"><i class="bi bi-search text-primary"></i> Advanced Research Search</h3>
        <p class="text-muted small mb-4">Filter papers through specific parameters including authors, advisers, department, and academic criteria.</p>

        <form method="GET">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Research Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Machine Learning in Agriculture" value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Keywords / Abstract Query</label>
                    <input type="text" name="keywords" class="form-control" placeholder="e.g., Neural Networks, Crop Yield" value="<?= htmlspecialchars($_GET['keywords'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= $c['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">School / Campus</label>
                    <select name="school_id" class="form-select">
                        <option value="">All Schools</option>
                        <?php foreach($schools as $s): ?><option value="<?= $s['id'] ?>"><?= $s['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= $d['name'] ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-filter"></i> Filter Papers</button>
                <a href="search.php" class="btn btn-outline-secondary px-4">Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Search Results Display -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Search Results</h4>
        <span class="badge bg-secondary fs-6"><?= count($results) ?> Result(s) Found</span>
    </div>

    <?php if ($searched && empty($results)): ?>
        <div class="alert alert-light border text-center py-4">No published papers match your specified filters.</div>
    <?php else: ?>
        <?php foreach ($results as $p): ?>
            <div class="card card-stat p-3 mb-3">
                <h5 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h5>
                <p class="text-muted small"><?= htmlspecialchars($p['abstract']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>