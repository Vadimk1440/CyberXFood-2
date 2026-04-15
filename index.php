<?php
require_once 'config/database.php';
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

// Получение товаров меню
$stmt = $db->prepare("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [
    'burgers' => [],
    'pizza' => [],
    'snacks' => [],
    'drinks' => []
];

foreach ($menu_items as $item) {
    $categories[$item['category']][] = $item;
}

// Получение количества товаров в корзине
$stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberXFood</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">CyberX<span>Food</span></a>
            <nav>
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="#menu">Меню</a></li>
                    <li><a href="#about">О нас</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin.php" class="admin-link"> Admin</a></li>
                    <?php endif; ?>
                    <li><a href="#" id="cart-link" class="cart-icon">
                         <span class="cart-icon-symbol"><img src="icons/basket_2.png" style="width: 25px; height: 25px;"></span>
                         <span class="cart-count"><?php echo $cart_count; ?></span>
                    </a></li>
                    <li><a href="logout.php" class="logout-link"> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-background">
                <div class="hero-image">
                    <img src="images/hero3.jpg" alt="Киберспортивный клуб">
                </div>
            </div>
            <div class="container hero-content">
                <h1>CYBERX FOOD — <span>ТОПЛИВО ДЛЯ ПОБЕД</span></h1>
                <p>Доставляем вкусную еду и напитки прямо в клуб. Быстро, вкусно, без отрыва от игры!</p>
                <a href="#menu" class="btn">Сделать заказ</a>
            </div>
        </section>

        <section id="menu" class="menu-section">
            <div class="container">
                <h2 class="section-title">Наше меню</h2>
                <div class="category-filters">
                    <button class="filter-btn active" data-category="all">Все</button>
                    <button class="filter-btn" data-category="burgers">Бургеры</button>
                    <button class="filter-btn" data-category="pizza">Пицца</button>
                    <button class="filter-btn" data-category="snacks">Закуски</button>
                    <button class="filter-btn" data-category="drinks">Напитки</button>
                </div>
                <div class="menu-grid" id="menu-grid">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="menu-item" data-category="<?php echo $item['category']; ?>">
                            <div class="menu-item-img">
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>" loading="lazy">
                            </div>
                            <div class="menu-item-content">
                                <h3 class="menu-item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="menu-item-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="menu-item-footer">
                                    <div class="menu-item-price"><?php echo $item['price']; ?> ₽</div>
                                    <button class="add-to-cart" data-id="<?php echo $item['id']; ?>">+</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="about" class="about-section">
            <div class="container">
                <div class="about-content">
                    <div class="about-text">
                        <h2>О нашей компании</h2>
                        <p>CyberXFood - это сервис приготовления и доставки еды, созданный специально для сети компьютерных клубов CyberXCommunity. Мы понимаем, насколько важно для геймеров не отвлекаться от игры, поэтому обеспечиваем быструю и качественную доставку.</p>
                        <p>Наша кухня оснащена современным оборудованием, а команда поваров готовит вкусные и сытные блюда, которые помогут вам оставаться в игре дольше.</p>
                        <p>Мы работаем с лучшими поставщиками продуктов, чтобы гарантировать качество каждого блюда. Наша цель - обеспечить вас вкусным и сытным перекусом без отрыва от игры.</p>
                        <a href="#menu" class="btn">Заказать сейчас</a>
                    </div>
                    <div class="about-image">
                        <div class="placeholder-image about-placeholder">
                            <img src="images/about_us.jpg" alt="Наша кухня">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <footer>
            <div class="container">
                <div class="footer-content">
                    <div class="footer-column">
                        <h3>CyberXFood</h3>
                        <p>Доставка еды в компьютерные клубы CyberXCommunity. Мы готовим, пока вы играете!</p>
                    </div>
                    <div class="footer-column">
                        <h3>Контакты</h3>
                        <p><span class="icon">📞</span> 8 (800) 444-13-02</p>
                        <p><span class="icon">✉️</span> info@cyberx-food.ru</p>
                        <p><span class="icon">📍</span> Москва, Профсоюзная, 56 (Башня Cherry Tower)</p>
                    </div>
                </div>
                <div class="copyright">
                    <p>&copy; 2026 CyberXCommunity. Все права защищены.</p>
            </div>
        </footer>
    </main>

    <!--Oкно корзины -->
    <div id="cart-modal" class="modal">
        <div class="modal-content modal-large">
            <span class="close-modal">&times;</span>
            <h2>Корзина</h2>
            <div id="cart-items"></div>
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Товары:</span>
                    <span id="items-total">0 ₽</span>
                </div>
                <div class="summary-row">
                    <span>Доставка:</span>
                    <span>200 ₽</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Итого:</span>
                    <span id="order-total">200 ₽</span>
                </div>
                <div class="form-group">
                    <label>Адрес доставки </label>
                    <input type="text" id="address" placeholder="Улица, дом">
                </div>
                <div class="form-group">
                    <label>Примечания</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                <button class="btn btn-primary" id="checkout-btn">Оформить заказ</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
