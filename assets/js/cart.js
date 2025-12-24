// Управление корзиной через AJAX
class CartManager {
    constructor() {
        this.init();
    }
    
    init() {
        // Обработка добавления в корзину
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', this.addToCart.bind(this));
        });
        
        // Обработка удаления из корзины
        document.querySelectorAll('.remove-from-cart').forEach(btn => {
            btn.addEventListener('click', this.removeFromCart.bind(this));
        });
        
        // Обработка изменения количества
        document.querySelectorAll('.update-quantity').forEach(input => {
            input.addEventListener('change', this.updateQuantity.bind(this));
        });
    }
    
    async addToCart(event) {
        const btn = event.currentTarget;
        const productId = btn.dataset.productId;
        const sizeId = btn.dataset.sizeId || document.getElementById('size').value;
        const quantity = btn.dataset.quantity || document.getElementById('quantity').value || 1;
        
        if (!sizeId) {
            alert('Выберите размер!');
            return;
        }
        
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    size_id: sizeId,
                    quantity: quantity
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.local_cart) {
                    // Для неавторизованных пользователей
                    const localCart = JSON.parse(localStorage.getItem('cart') || '{"items": []}');
                    const existingItem = localCart.items.find(item => 
                        item.product_id == productId && item.size_id == sizeId
                    );
                    
                    if (existingItem) {
                        existingItem.quantity += parseInt(quantity);
                    } else {
                        localCart.items.push({
                            product_id: productId,
                            size_id: sizeId,
                            quantity: parseInt(quantity)
                        });
                    }
                    
                    localStorage.setItem('cart', JSON.stringify(localCart));
                    this.updateCartCount();
                }
                
                alert('Товар добавлен в корзину!');
                this.updateCartCount();
            } else {
                alert('Ошибка при добавлении в корзину');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Ошибка соединения с сервером');
        }
    }
    
    updateCartCount() {
        let count = 0;
        
        // Проверяем локальную корзину
        const localCart = JSON.parse(localStorage.getItem('cart') || '{"items": []}');
        count += localCart.items.reduce((total, item) => total + item.quantity, 0);
        
        // Обновляем счетчик в интерфейсе
        const counters = document.querySelectorAll('.cart-count');
        counters.forEach(counter => {
            counter.textContent = count;
        });
    }
    
    async removeFromCart(event) {
        const btn = event.currentTarget;
        const itemId = btn.dataset.itemId;
        
        if (confirm('Удалить товар из корзины?')) {
            try {
                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'remove',
                        item_id: itemId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    btn.closest('.cart-item').remove();
                    this.updateCartCount();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }
    
    async updateQuantity(event) {
        const input = event.currentTarget;
        const itemId = input.dataset.itemId;
        const quantity = input.value;
        
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    item_id: itemId,
                    quantity: quantity
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                // Возвращаем старое значение
                input.value = input.dataset.oldValue;
            }
        } catch (error) {
            console.error('Error:', error);
            input.value = input.dataset.oldValue;
        }
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});