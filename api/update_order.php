<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;
$status = $data['status'] ?? '';

$allowed_statuses = ['pending', 'cooking', 'delivering', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Неверный статус']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
if ($stmt->execute([$status, $order_id])) {
    echo json_encode(['success' => true, 'message' => 'Статус заказа обновлён']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении статуса']);
}
?>
