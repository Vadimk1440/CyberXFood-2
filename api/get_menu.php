<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'items' => $menu_items]);
?>
