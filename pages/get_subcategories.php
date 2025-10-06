<?php
require_once '../function.php';

if (isset($_GET['category_id'])) {
    $category_id = (int) $_GET['category_id'];
    $db = connectDB();

    $stmt = $db->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($subcategories);
    exit;
}
