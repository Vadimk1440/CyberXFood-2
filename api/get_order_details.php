<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо войти в систему']);
    exit();
}

$order_id = $_GET['id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

if (!isAdmin()) {
    $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit();
    }
}

// Получение информации о заказе
$stmt = $db->prepare("
    SELECT o.*, u.username, u.full_name, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
    exit();
}

// Получение товаров в заказе
$stmt = $db->prepare("
    SELECT oi.*, mi.name
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$order['items'] = $items;

echo json_encode(['success' => true, 'data' => $order]);
?>
