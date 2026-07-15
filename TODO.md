# TODO - Professional Ecommerce Hardening

## Phase 1 (Approved): CSRF + API Hardening + Checkout Reliability
- [x] Add CSRF token utilities in `includes/functions.php`.
- [ ] Update storefront forms to include CSRF tokens (cart update/remove forms, product add-to-cart form).
- [ ] Enforce CSRF validation in `actions/cart.php`.
- [ ] Remove hardcoded JWT secret defaults; add `API_JWT_SECRET` in `includes/config.php`.
- [ ] Harden API endpoints by converting all dynamic SQL in:
  - `server/auth.php`
  - `server/products.php`
  - `server/cart.php`
  - `server/orders.php`
  to prepared statements.
- [ ] Tighten API CORS policy in `server/config.php`.
- [ ] Make API product visibility consistent with storefront (`p.is_active = 1`).
- [ ] Improve checkout robustness:
  - check stock reservation/affected rows per item
  - prevent order creation if any item fails
  - basic double-submit guard (session-based token)

## Phase 2 (Not fully approved yet): Paystack Payment + Webhooks
- [ ] Add Paystack integration endpoints (server-side) and checkout flow.
- [ ] Implement webhook endpoint to mark payments as paid/failed.
- [ ] Update admin/order status workflow to reflect payment status.

