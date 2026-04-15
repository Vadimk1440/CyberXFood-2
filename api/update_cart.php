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
$quantity = $data['quantity'] ?? 0;

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

if ($quantity <= 0) {
    // Удаление товара из корзины
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
} else {
    // Обновление количества
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$quantity, $user_id, $item_id]);
}

$stmt = $db->prepare("
    SELECT SUM(quantity) as total_count, SUM(m.price * c.quantity) as total_sum
    FROM cart c
    JOIN menu_items m ON c.item_id = m.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => 'Корзина обновлена',
    'cart_count' => $result['total_count'] ?? 0,
    'cart_total' => $result['total_sum'] ?? 0
]);
?>
