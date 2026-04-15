document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');

        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        this.classList.add('active');
        document.getElementById(`tab-${tabId}`).classList.add('active');
    });
});

// Фильтр заказов 
const statusFilter = document.getElementById('status-filter');
if (statusFilter) {
    statusFilter.addEventListener('change', function() {
        const status = this.value;
        window.location.href = `admin.php?status=${status}`;
    });
}

// Изменение статуса заказа
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', async function() {
        const orderId = this.getAttribute('data-order-id');
        const newStatus = this.value;

        try {
            const response = await fetch('api/update_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: newStatus })
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Статус заказа обновлён', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(result.message || 'Ошибка при обновлении статуса', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Ошибка при обновлении статуса', 'error');
        }
    });
});

// Показать/скрыть форму добавления товара
const showAddFormBtn = document.getElementById('show-add-form');
const addItemForm = document.getElementById('add-item-form');
const cancelAddBtn = document.getElementById('cancel-add');

if (showAddFormBtn) {
    showAddFormBtn.addEventListener('click', () => {
        addItemForm.classList.toggle('active');
    });
}

if (cancelAddBtn) {
    cancelAddBtn.addEventListener('click', () => {
        addItemForm.classList.remove('active');
        document.getElementById('new-item-form').reset();
    });
}

// Добавление нового товара
const newItemForm = document.getElementById('new-item-form');
if (newItemForm) {
    newItemForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'add_menu_item');
        formData.append('name', document.getElementById('new-item-name').value);
        formData.append('price', document.getElementById('new-item-price').value);
        formData.append('category', document.getElementById('new-item-category').value);
        formData.append('description', document.getElementById('new-item-desc').value);
        formData.append('image_url', document.getElementById('new-item-image').value);

        try {
            const response = await fetch('api/manage_menu.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Товар добавлен успешно', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(result.message || 'Ошибка при добавлении', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Ошибка при добавлении товара', 'error');
        }
    });
}

// Переключение доступности товара
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', async function() {
        const itemId = this.getAttribute('data-item-id');
        const currentAvailable = this.getAttribute('data-available') === '1';
        const newAvailable = currentAvailable ? 0 : 1;

        const formData = new FormData();
        formData.append('action', 'update_menu_item');
        formData.append('item_id', itemId);
        formData.append('is_available', newAvailable);

        try {
            const response = await fetch('api/manage_menu.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Статус товара обновлён', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(result.message || 'Ошибка при обновлении статуса', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Ошибка при обновлении статуса', 'error');
        }
    });
});

// Удаление товара
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm('Вы уверены, что хотите удалить этот товар?')) return;

        const itemId = this.getAttribute('data-item-id');

        const formData = new FormData();
        formData.append('action', 'delete_menu_item');
        formData.append('item_id', itemId);

        try {
            const response = await fetch('api/manage_menu.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Товар удалён', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(result.message || 'Ошибка при удалении товара', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Ошибка при удалении товара', 'error');
        }
    });
});

// Просмотр деталей заказа
document.querySelectorAll('.btn-view-order').forEach(btn => {
    btn.addEventListener('click', async function() {
        const orderId = this.getAttribute('data-order-id');

        try {
            const response = await fetch(`api/get_order_details.php?id=${orderId}`);
            const result = await response.json();

            const modal = document.getElementById('order-modal');
            const detailsDiv = document.getElementById('order-details');

            if (result.success) {
                const order = result.data;
                detailsDiv.innerHTML = `
                    <div style="background: #1a1a1a; padding: 20px; border-radius: 10px;">
                        <h3>Заказ #${order.order_number}</h3>
                        <p><strong>Дата:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                        <p><strong>Клиент:</strong> ${escapeHtml(order.full_name || order.username)}</p>
                        <p><strong>Телефон:</strong> ${escapeHtml(order.phone || '-')}</p>
                        <p><strong>Адрес доставки:</strong> ${escapeHtml(order.delivery_address)}</p>
                        <p><strong>Примечания:</strong> ${escapeHtml(order.notes || '-')}</p>
                        <p><strong>Статус:</strong> ${getStatusText(order.status)}</p>
                        <h4 style="margin-top: 20px;">Товары:</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #333;">
                                    <th style="padding: 10px; text-align: left;">Товар</th>
                                    <th style="padding: 10px; text-align: center;">Кол-во</th>
                                    <th style="padding: 10px; text-align: right;">Цена</th>
                                    <th style="padding: 10px; text-align: right;">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr style="border-bottom: 1px solid #333;">
                                        <td style="padding: 10px;">${escapeHtml(item.name)}</td>
                                        <td style="padding: 10px; text-align: center;">${item.quantity}</td>
                                        <td style="padding: 10px; text-align: right;">${item.price_at_time} ₽</td>
                                        <td style="padding: 10px; text-align: right;">${item.price_at_time * item.quantity} ₽</td>
                                     </tr>
                                `).join('')}
                                <tr style="font-weight: bold; border-top: 2px solid #ff0000;">
                                    <td colspan="3" style="padding: 10px; text-align: right;">Итого:</td>
                                    <td style="padding: 10px; text-align: right;">${order.total_amount} ₽</td>
                                 </tr>
                            </tbody>
                        </table>
                    </div>
                `;
                modal.style.display = 'block';
            } else {
                showNotification(result.message || 'Ошибка при загрузке деталей заказа', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Ошибка при загрузке деталей заказа', 'error');
        }
    });
});

// Закрытие модального окна
document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    });
});

window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

function getStatusText(status) {
    const statuses = {
        'pending': ' Ожидает',
        'cooking': ' Готовится',
        'delivering': ' Доставляется',
        'completed': ' Завершён',
        'cancelled': ' Отменён'
    };
    return statuses[status] || status;
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
    notification.className = `notification notification-${type}`;
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
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
