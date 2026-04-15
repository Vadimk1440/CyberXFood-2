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
$delivery_address = $data['address'] ?? '';
$notes = $data['notes'] ?? '';

if (empty($delivery_address)) {
    echo json_encode(['success' => false, 'message' => 'Укажите адрес доставки']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Получение товаров из корзины
$stmt = $db->prepare("
    SELECT c.item_id, c.quantity, m.price, m.name
    FROM cart c
    JOIN menu_items m ON c.item_id = m.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
    exit();
}

// Расчет суммы
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}
$delivery_cost = 200;
$total_amount += $delivery_cost;

$order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

// Создание заказа
$db->beginTransaction();

try {
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, delivery_address, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $order_number, $total_amount, $delivery_address, $notes]);
    $order_id = $db->lastInsertId();

    // Добавление товаров в заказ
    $stmt = $db->prepare("
        INSERT INTO order_items (order_id, item_id, quantity, price_at_time)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $stmt->execute([$order_id, $item['item_id'], $item['quantity'], $item['price']]);
    }

    // Очистка корзины
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно оформлен',
        'order_number' => $order_number,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Ошибка при оформлении заказа: ' . $e->getMessage()]);
}
?>
