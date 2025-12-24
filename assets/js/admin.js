// Управление локальной корзиной
class LocalCart {
    constructor() {
        this.cart = this.getCart();
    }
    
    getCart() {
        const cart = localStorage.getItem('cart');
        return cart ? JSON.parse(cart) : { items: [] };
    }
    
    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }
    
    addItem(productId, size, quantity = 1) {
        const existingItem = this.cart.items.find(item => 
            item.product_id === productId && item.size === size
        );
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.items.push({
                product_id: productId,
                size: size,
                quantity: quantity
            });
        }
        
        this.saveCart();
        this.updateCartCount();
    }
    
    removeItem(productId, size) {
        this.cart.items = this.cart.items.filter(item => 
            !(item.product_id === productId && item.size === size)
        );
        this.saveCart();
        this.updateCartCount();
    }
    
    updateCartCount() {
        const count = this.cart.items.reduce((total, item) => total + item.quantity, 0);
        const counter = document.getElementById('cart-count');
        if (counter) {
            counter.textContent = count;
        }
    }
    
    clear() {
        this.cart = { items: [] };
        this.saveCart();
        this.updateCartCount();
    }
}

// Инициализация корзины
const cart = new LocalCart();
cart.updateCartCount();

// Функция добавления в корзину
function addToCart(productId, size) {
    cart.addItem(productId, size);
    alert('Товар добавлен в корзину!');
}

// AJAX функции
async function apiCall(endpoint, data = {}) {
    const response = await fetch(`api/${endpoint}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    });
    
    return response.json();
}