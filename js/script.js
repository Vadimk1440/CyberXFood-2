let cart = [];

async function addToCart(itemId) {
    try {
        const response = await fetch('api/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId, quantity: 1 })
        });

        const result = await response.json();

        if (result.success) {
            showNotification(result.message, 'success');
            updateCartCount(result.cart_count);
            const modal = document.getElementById('cart-modal');
            if (modal && modal.style.display === 'block') {
                await loadCart();
            }
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Ошибка при добавлении в корзину', 'error');
    }
}

async function loadCart() {
    try {
        const response = await fetch('api/get_cart.php');
        const result = await response.json();

        if (result.success) {
            cart = result.items || [];
            displayCart();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function updateCartItem(itemId, quantity) {
    try {
        const response = await fetch('api/update_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: itemId, quantity: quantity })
        });

        const result = await response.json();

        if (result.success) {
            await loadCart();
            updateCartCount(result.cart_count);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Ошибка при обновлении корзины', 'error');
    }
}

async function checkout() {
    const address = document.getElementById('address');
    const notes = document.getElementById('notes');

    if (!address) {
        showNotification('Поле адреса не найдено', 'error');
        return;
    }

    const addressValue = address.value;
    const notesValue = notes ? notes.value : '';

    if (!addressValue) {
        showNotification('Укажите адрес доставки', 'error');
        address.focus();
        return;
    }

    if (!cart || cart.length === 0) {
        showNotification('Корзина пуста', 'error');
        return;
    }

    try {
        const response = await fetch('api/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ address: addressValue, notes: notesValue })
        });

        const result = await response.json();

        if (result.success) {
            showNotification(`Заказ ${result.order_number} оформлен!`, 'success');
            closeCartModal();
            cart = [];
            displayCart();
            updateCartCount(0);
            address.value = '';
            if (notes) notes.value = '';
        } else {
            showNotification(result.message || 'Ошибка при оформлении', 'error');
        }
    } catch (error) {
        console.error('Checkout error:', error);
        showNotification('Ошибка сервера', 'error');
    }
}

function displayCart() {
    const cartItemsDiv = document.getElementById('cart-items');
    if (!cartItemsDiv) return;

    if (!cart || cart.length === 0) {
        cartItemsDiv.innerHTML = '<p style="text-align:center;padding:40px;">Корзина пуста</p>';
        const itemsTotal = document.getElementById('items-total');
        const orderTotal = document.getElementById('order-total');
        if (itemsTotal) itemsTotal.textContent = '0 ₽';
        if (orderTotal) orderTotal.textContent = '200 ₽';
        return;
    }

    let itemsHtml = '<div>';
    let itemsTotal = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsTotal += itemTotal;

        const imageUrl = item.image_url || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"%3E%3Crect width="60" height="60" fill="%23333"/%3E%3Ctext x="30" y="35" text-anchor="middle" fill="%23aaa" font-size="12"%3E🍔%3C/text%3E%3C/svg%3E';

        itemsHtml += `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #333;">
                <div style="display:flex;gap:15px;align-items:center;">
                    <img src="${imageUrl}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                    <div>
                        <h4 style="margin:0 0 5px 0;">${escapeHtml(item.name)}</h4>
                        <p style="margin:0;color:#ff0000;">${item.price} ₽</p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button onclick="updateCartItem(${item.item_id}, ${item.quantity - 1})" style="width:30px;height:30px;border-radius:50%;background:#333;color:white;border:none;cursor:pointer;">-</button>
                    <span style="min-width:30px;text-align:center;">${item.quantity}</span>
                    <button onclick="updateCartItem(${item.item_id}, ${item.quantity + 1})" style="width:30px;height:30px;border-radius:50%;background:#333;color:white;border:none;cursor:pointer;">+</button>
                    <button onclick="updateCartItem(${item.item_id}, 0)" style="background:none;border:none;color:#ff6666;cursor:pointer;font-size:0.9rem;">Delete</button>
                </div>
            </div>
        `;
    });

    itemsHtml += '</div>';
    cartItemsDiv.innerHTML = itemsHtml;

    const deliveryCost = 200;
    const total = itemsTotal + deliveryCost;

    const itemsTotalElem = document.getElementById('items-total');
    const orderTotalElem = document.getElementById('order-total');

    if (itemsTotalElem) itemsTotalElem.textContent = `${itemsTotal} ₽`;
    if (orderTotalElem) orderTotalElem.textContent = `${total} ₽`;
}

function updateCartCount(count) {
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = count;
    });
}

function openCartModal() {
    const modal = document.getElementById('cart-modal');
    if (modal) {
        loadCart();
        modal.style.display = 'block';
    }
}

function closeCartModal() {
    const modal = document.getElementById('cart-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background-color: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        padding: 15px 25px;
        border-radius: 5px;
        z-index: 10000;
        font-weight: bold;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// ИНИЦИАЛИЗАЦИЯ
document.addEventListener('DOMContentLoaded', function() {
    console.log('Загружено');

    // Кнопки добавления в корзину
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.getAttribute('data-id');
            if (itemId) addToCart(itemId);
        });
    });

    // Ссылка на корзину
    const cartLink = document.getElementById('cart-link');
    if (cartLink) {
        cartLink.addEventListener('click', function(e) {
            e.preventDefault();
            openCartModal();
        });
    }

    // Кнопка оформления заказа
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            checkout();
        });
    }

    // Закрытие модального окна
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', closeCartModal);
    });

    window.addEventListener('click', (e) => {
        const modal = document.getElementById('cart-modal');
        if (e.target === modal) closeCartModal();
    });


    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const category = this.getAttribute('data-category');
            document.querySelectorAll('.menu-item').forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    console.log('Инициализация завершена');
});
