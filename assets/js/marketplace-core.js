(async () => {
    const API_BASE = (window.__APP_URL__ || '') + "/server";

    // Helper functions
    const getAuthToken = () => localStorage.getItem('auth_token');
    const setAuthToken = (token) => localStorage.setItem('auth_token', token);
    const removeAuthToken = () => localStorage.removeItem('auth_token');
    
    const getCurrentUser = () => {
        const userStr = localStorage.getItem('current_user');
        return userStr ? JSON.parse(userStr) : null;
    };
    const setCurrentUser = (user) => localStorage.setItem('current_user', JSON.stringify(user));
    const removeCurrentUser = () => localStorage.removeItem('current_user');

    async function apiRequest(endpoint, options = {}) {
        const url = `${API_BASE}${endpoint}`;
        const headers = {
            "Content-Type": "application/json",
            ...options.headers
        };

        const token = getAuthToken();
        if (token) {
            headers["Authorization"] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || "Request failed");
            }
            return data;
        } catch (error) {
            console.error("API Request Error:", error);
            throw error;
        }
    }

    // Public API
    window.MarketplaceApp = {
        async login(credentials) {
            const data = await apiRequest("/auth.php/login", {
                method: "POST",
                body: JSON.stringify(credentials)
            });
            setAuthToken(data.token);
            setCurrentUser({
                id: data.user_id,
                email: credentials.email,
                role: data.role,
                name: credentials.email.split('@')[0]
            });
            return { success: true, data };
        },

        async register(userData) {
            const data = await apiRequest("/auth.php/register", {
                method: "POST",
                body: JSON.stringify(userData)
            });
            setAuthToken(data.token);
            setCurrentUser({
                id: data.user_id,
                email: userData.email,
                role: userData.role,
                name: userData.first_name + ' ' + userData.last_name
            });
            return { success: true, data };
        },

        logout() {
            removeAuthToken();
            removeCurrentUser();
        },

        getCurrentUser,

        async getProducts() {
            return await apiRequest("/products.php");
        },

        async getProductById(id) {
            return await apiRequest(`/products.php/${id}`);
        },

        async getCart() {
            return await apiRequest("/cart.php");
        },

        async addToCart(productId, quantity = 1) {
            return await apiRequest("/cart.php/items", {
                method: "POST",
                body: JSON.stringify({ product_id: productId, quantity })
            });
        },

        async getCartSummary() {
            try {
                const cart = await this.getCart();
                let count = 0;
                if (cart.items) {
                    count = cart.items.reduce((sum, item) => sum + item.quantity, 0);
                }
                return { count, subtotal: cart.subtotal || 0 };
            } catch (e) {
                return { count: 0, subtotal: 0 };
            }
        },

        getSearchSuggestions(query) {
            // Basic search for now
            return [];
        },

        recordRecentlyViewed(id) {
            // Placeholder
        },

        searchProducts(query) {
            // Placeholder for search
            return [];
        },

        formatCurrency(value) {
            return new Intl.NumberFormat("en-NG", {
                style: "currency",
                currency: "NGN",
                maximumFractionDigits: 0
            }).format(value);
        },

        resolveImage(imagePath) {
            if (!imagePath) return "https://picsum.photos/seed/placeholder/200/200";
            if (/^https?:/i.test(imagePath)) return imagePath;
            const isNested = /\/(customer|vendor|admin)\//.test(window.location.pathname);
            return isNested ? `../${imagePath.replace(/^\.\//, "")}` : imagePath.replace(/^\.\//, "");
        }
    };
})();
