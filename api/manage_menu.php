<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

if ($action === 'add_menu_item') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? 'burgers';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if (empty($name) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
        exit();
    }

    $stmt = $db->prepare("
        INSERT INTO menu_items (name, description, price, category, image_url)
        VALUES (?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$name, $description, $price, $category, $image_url])) {
        echo json_encode(['success' => true, 'message' => 'Товар добавлен', 'id' => $db->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении']);
    }
    exit();
}

if ($action === 'update_menu_item') {
    $item_id = $_POST['item_id'] ?? 0;
    $is_available = $_POST['is_available'] ?? 1;

    $stmt = $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
    if ($stmt->execute([$is_available, $item_id])) {
        echo json_encode(['success' => true, 'message' => 'Статус товара обновлён']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении']);
    }
    exit();
}

if ($action === 'delete_menu_item') {
    $item_id = $_POST['item_id'] ?? 0;

    $stmt = $db->prepare("SELECT id FROM order_items WHERE item_id = ? LIMIT 1");
    $stmt->execute([$item_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Нельзя удалить товар, который есть в заказах']);
        exit();
    }

    $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
    if ($stmt->execute([$item_id])) {
        echo json_encode(['success' => true, 'message' => 'Товар удалён']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении']);
    }
    exit();
}

if ($action === 'edit_menu_item') {
    $item_id = $_POST['item_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    $stmt = $db->prepare("
        UPDATE menu_items
        SET name = ?, price = ?, category = ?, description = ?, image_url = ?
        WHERE id = ?
    ");

    if ($stmt->execute([$name, $price, $category, $description, $image_url, $item_id])) {
        echo json_encode(['success' => true, 'message' => 'Товар обновлён']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
?>
