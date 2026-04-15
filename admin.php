<?php
require_once 'config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Получение статистики
$stats = [];

// Общая выручка
$stmt = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_revenue'] = $result['total'] ?? 0;

// Всего заказов
$stmt = $db->query("SELECT COUNT(*) as count FROM orders");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_orders'] = $result['count'] ?? 0;

// Средний чек
$stmt = $db->query("SELECT AVG(total_amount) as avg FROM orders WHERE status = 'completed'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['avg_order'] = round($result['avg'] ?? 0);

// Самое популярное блюдо
$stmt = $db->query("
    SELECT mi.name, SUM(oi.quantity) as total_count
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.id
    GROUP BY oi.item_id
    ORDER BY total_count DESC
    LIMIT 1
");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['popular_item'] = $result['name'] ?? '-';

// Количество пользователей
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_users'] = $result['count'] ?? 0;

// Заказы по дням
$stats['orders_by_day'] = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['orders_by_day'][$date] = $result['count'] ?? 0;
}

// Получение всех заказов
$status_filter = $_GET['status'] ?? 'all';
$sql = "SELECT o.*, u.username, u.full_name, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id";
if ($status_filter !== 'all') {
    $sql .= " WHERE o.status = :status";
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
if ($status_filter !== 'all') {
    $stmt->execute(['status' => $status_filter]);
} else {
    $stmt->execute();
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение товаров меню для управления
$stmt = $db->query("SELECT * FROM menu_items ORDER BY category, name");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'update_order_status') {
        $order_id = $_POST['order_id'] ?? 0;
        $new_status = $_POST['status'] ?? '';

        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $order_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }

    if ($action === 'update_menu_item') {
        $item_id = $_POST['item_id'] ?? 0;
        $is_available = $_POST['is_available'] ?? 1;

        $stmt = $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        if ($stmt->execute([$is_available, $item_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }

    if ($action === 'delete_menu_item') {
        $item_id = $_POST['item_id'] ?? 0;

        $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
        if ($stmt->execute([$item_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberXFood - Административная панель</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-container {
            padding-top: 100px;
            min-height: 100vh;
            background-color: #0a0a0a;
        }

        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
            border-bottom: 2px solid #ff0000;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 100%;
            margin-bottom: 40px;
        }

        .stat-card {
            display: flex;
            justify-content: space-between;
            background: linear-gradient(135deg, #1a1a1a, #252525);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #ff0000;
            transition: transform 0.3s ease;
        }


        .stat-icon {
            font-size: 2rem;
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1rem;
            font-weight: bold;
            color: #ff0000;
        }

        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .admin-tab {
            padding: 12px 24px;
            background-color: transparent;
            color: #aaa;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border-radius: 5px;
        }

        .admin-tab:hover {
            background-color: #1a1a1a;
            color: #ff0000;
        }

        .admin-tab.active {
            background-color: #ff0000;
            color: white;
        }

        .tab-content {
            display: none;
            margin-bottom: 50px;
        }

        .tab-content.active {
            display: block;
        }

        .orders-table, .menu-table {
            width: 100%;
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background-color: #ff0000;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #252525;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-pending { background-color: #ff9800; color: #fff; }
        .status-cooking { background-color: #2196f3; color: #fff; }
        .status-delivering { background-color: #9c27b0; color: #fff; }
        .status-completed { background-color: #4caf50; color: #fff; }
        .status-cancelled { background-color: #f44336; color: #fff; }

        .status-select {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ff0000;
            background-color: #333;
            color: white;
            cursor: pointer;
        }

        .btn-toggle {
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-available {
            background-color: #4caf50;
            color: white;
        }

        .btn-unavailable {
            background-color: #f44336;
            color: white;
        }

        .btn-delete {
            background-color: #ff0000;
            color: white;
            width: 90px;
            height: 30px;
        }

        .btn-delete:hover {
            background-color: #cc0000;
        }

        .filter-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-select {
            padding: 8px 15px;
            background-color: #1a1a1a;
            color: white;
            border: 1px solid #ff0000;
            border-radius: 5px;
            cursor: pointer;
        }

        .chart-container {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 30px;
            margin-top: 30px;
        }

        .chart-bars {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            height: 300px;
            gap: 10px;
            margin-top: 20px;
        }

        .chart-bar-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .chart-bar {
            width: 100%;
            background-color: #ff0000;
            border-radius: 5px;
            transition: height 0.3s ease;
            min-height: 5px;
        }

        .chart-label {
            color: #aaa;
            font-size: 0.8rem;
            text-align: center;
        }

        .add-item-form {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: none;
        }

        .add-item-form.active {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-tabs {
                flex-direction: column;
            }

            .orders-table {
                overflow-x: scroll;
            }

            table {
                font-size: 0.85rem;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">CyberX<span>Food</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="admin.php" class="nav-active"> Admin panel</a></li>
                    <li><a href="logout.php"> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <div class="container">
                <h1>Административная панель</h1>
            </div>
        </div>

        <div class="container">
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-info">
                        <div class="stat-label">Общая выручка</div>
                        <div class="stat-value"><?php echo number_format($stats['total_revenue'], 0, '.', ' '); ?> ₽</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-info">
                        <div class="stat-label">Всего заказов</div>
                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-info">
                        <div class="stat-label">Пользователей</div>
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-info">
                        <div class="stat-label">Популярное блюдо</div>
                        <div class="stat-value"><?php echo htmlspecialchars($stats['popular_item']); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"></div>
                    <div class="stat-info">
                        <div class="stat-label">Средний чек</div>
                        <div class="stat-value"><?php echo number_format($stats['avg_order'], 0, '.', ' '); ?> ₽</div>
                    </div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="admin-tabs">
                <button class="admin-tab active" data-tab="orders"> Заказы</button>
                <button class="admin-tab" data-tab="menu"> Управление меню</button>
                <button class="admin-tab" data-tab="stats"> Статистика</button>
            </div>

            <!-- Вкладка Заказы -->
            <div id="tab-orders" class="tab-content active">
                <div class="filter-bar">
                    <label>Фильтр по статусу:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все заказы</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Ожидают</option>
                        <option value="cooking" <?php echo $status_filter === 'cooking' ? 'selected' : ''; ?>>Готовятся</option>
                        <option value="delivering" <?php echo $status_filter === 'delivering' ? 'selected' : ''; ?>>Доставляются</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Завершенные</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Отмененные</option>
                    </select>
                </div>

                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Номер заказа</th>
                                <th>Клиент</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Заказов не найдено</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['full_name'] ?: $order['username']); ?><br>
                                            <small><?php echo htmlspecialchars($order['phone'] ?: '-'); ?></small>
                                        </td>
                                        <td><?php echo number_format($order['total_amount'], 0, '.', ' '); ?> ₽</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php
                                                    $statuses = [
                                                        'pending' => ' Ожидает',
                                                        'cooking' => ' Готовится',
                                                        'delivering' => ' Доставляется',
                                                        'completed' => ' Завершён',
                                                        'cancelled' => ' Отменён'
                                                    ];
                                                    echo $statuses[$order['status']];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <select class="status-select" data-order-id="<?php echo $order['id']; ?>">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>> Ожидает</option>
                                                <option value="cooking" <?php echo $order['status'] === 'cooking' ? 'selected' : ''; ?>> Готовится</option>
                                                <option value="delivering" <?php echo $order['status'] === 'delivering' ? 'selected' : ''; ?>> Доставляется</option>
                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>> Завершен</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>> Отменен</option>
                                            </select>
                                            <button class="btn-view-order" data-order-id="<?php echo $order['id']; ?>" style="margin-top: 5px; padding: 5px 10px; background: #2196f3; color: white; border: none; border-radius: 3px; cursor: pointer;">Детали</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!--Вкладка Управление меню -->
            <div id="tab-menu" class="tab-content">
                <button id="show-add-form" class="btn" style="margin-bottom: 20px; background-color: #4caf50;"> Добавить новое блюдо</button>

                <div id="add-item-form" class="add-item-form">
                    <h3>Добавление нового блюда</h3>
                    <form id="new-item-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Название блюда </label>
                                <input type="text" id="new-item-name" required>
                            </div>
                            <div class="form-group">
                                <label>Цена </label>
                                <input type="number" id="new-item-price" required>
                            </div>
                            <div class="form-group">
                                <label>Категория </label>
                                <select id="new-item-category">
                                    <option value="burgers">Бургеры</option>
                                    <option value="pizza">Пицца</option>
                                    <option value="snacks">Закуски</option>
                                    <option value="drinks">Напитки</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Описание</label>
                            <textarea id="new-item-desc" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Ссылка на изображение</label>
                            <input type="text" id="new-item-image">
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="button" id="cancel-add" class="btn" style="background-color: #666;">Отмена</button>
                    </form>
                </div>

                <div class="menu-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu_items as $item): ?>
                                <tr data-item-id="<?php echo $item['id']; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <?php
                                            $categories = [
                                                'burgers' => ' Бургеры',
                                                'pizza' => ' Пицца',
                                                'snacks' => ' Закуски',
                                                'drinks' => ' Напитки'
                                            ];
                                            echo $categories[$item['category']];
                                        ?>
                                    </td>
                                    <td><?php echo $item['price']; ?> ₽</td>
                                    <td>
                                        <button class="btn-toggle <?php echo $item['is_available'] ? 'btn-available' : 'btn-unavailable'; ?>"
                                                data-item-id="<?php echo $item['id']; ?>"
                                                data-available="<?php echo $item['is_available']; ?>">
                                            <?php echo $item['is_available'] ? ' Доступно' : ' Недоступно'; ?>
                                        </button>
                                    </td>
                                    <td>
                                        <button class="btn-delete" data-item-id="<?php echo $item['id']; ?>"> Удалить</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Вкладка Статистика -->
            <div id="tab-stats" class="tab-content">
                <div class="chart-container">
                    <h3>Заказы за последние 7 дней</h3>
                    <div class="chart-bars">
                        <?php
                        $max_orders = max($stats['orders_by_day']) ?: 1;
                        foreach ($stats['orders_by_day'] as $date => $count):
                            $height = ($count / $max_orders) * 250;
                            $display_date = date('d.m', strtotime($date));
                        ?>
                            <div class="chart-bar-wrapper">
                                <div class="chart-bar" style="height: <?php echo $height; ?>px;"></div>
                                <div class="chart-label"><?php echo $display_date; ?></div>
                                <div class="chart-label"><?php echo $count; ?> зак.</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

        </div>
    </div>

    <!--Окно деталей заказа -->
    <div id="order-modal" class="modal">
        <div class="modal-content modal-large">
            <span class="close-modal">&times;</span>
            <h2>Детали заказа</h2>
            <div id="order-details"></div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>
