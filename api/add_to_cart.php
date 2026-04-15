<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо войти в систему']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? 0;
$quantity = $data['quantity'] ?? 1;

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT id FROM menu_items WHERE id = ? AND is_available = 1");
$stmt->execute([$item_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("INSERT INTO cart (user_id, item_id, quantity)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE quantity = quantity + ?");

if ($stmt->execute([$user_id, $item_id, $quantity, $quantity])) {
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'cart_count' => $result['total'] ?? 0
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении']);
}
?>
