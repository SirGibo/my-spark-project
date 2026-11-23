<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Authentication Check
if (!isset($_SESSION['user'])) {
    header('Location: ../authentication/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paper_id'])) {
    $paperId = (int)$_POST['paper_id'];
    $userId = $_SESSION['user']['id'];
    $userRole = $_SESSION['user']['role'] ?? 'Student';

    // Fetch paper record to verify ownership and get file path
    $stmt = $pdo->prepare("SELECT id, user_id, file_path, title FROM papers WHERE id = ?");
    $stmt->execute([$paperId]);
    $paper = $stmt->fetch();

    if ($paper) {
        // Authorization check: User must be the uploader or an Admin
        if ($userRole === 'Admin' || $paper['user_id'] == $userId) {
            
            // 1. Unlink physical file from uploads directory
            if (!empty($paper['file_path'])) {
                $fullFilePath = __DIR__ . '/../uploads/' . $paper['file_path'];
                if (file_exists($fullFilePath)) {
                    unlink($fullFilePath);
                }
            }

            // 2. Remove paper from database
            $deleteStmt = $pdo->prepare("DELETE FROM papers WHERE id = ?");
            $deleteStmt->execute([$paperId]);

            $_SESSION['flash_success'] = "Research paper \"" . htmlspecialchars($paper['title']) . "\" has been removed.";
        } else {
            $_SESSION['flash_error'] = "Unauthorized: You can only remove your own research submissions.";
        }
    } else {
        $_SESSION['flash_error'] = "Research paper not found.";
    }
}

// Redirect back to referring page or homepage
$redirectTo = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header('Location: ' . $redirectTo);
exit;