# Marketplace Architecture Blueprint

## Objective

This project now uses a shared browser-side marketplace state layer to keep the storefront, customer dashboard, vendor dashboard, and admin dashboard synchronized. The next production step is to replace the browser persistence with API-backed services without changing the high-level domain model.

## Recommended Stack

- Frontend: TypeScript, React or Next.js, Bootstrap-compatible design system, TanStack Query
- Backend: Node.js with NestJS or Express + TypeScript
- Database: PostgreSQL
- Cache: Redis
- Search: PostgreSQL full text initially, Elasticsearch/OpenSearch when scale requires it
- Queue: BullMQ or RabbitMQ for emails, notifications, webhooks, image processing
- Storage: S3-compatible object storage for product images and assets
- Auth: JWT access token + rotating refresh token, optional 2FA
- Observability: OpenTelemetry, structured logs, error tracking, uptime checks

## Core Bounded Contexts

- Identity: admins, vendors, customers, roles, permissions, sessions, MFA
- Catalog: products, brands, categories, variants, inventory, media
- Commerce: cart, wishlist, compare, checkout, coupons, orders, returns
- Payments: provider orchestration, payment intents, settlements, refunds, ledgers
- Fulfillment: shipping, tracking, delivery milestones, returns workflows
- Communication: notifications, support tickets, live chat, email templates
- Analytics: revenue, sales, traffic, vendor ranking, conversion, cohort metrics

## Relational Model

### Users and Roles

- `users`
  - `id`, `email`, `password_hash`, `phone`, `status`, `email_verified_at`, `last_login_at`
- `profiles`
  - `user_id`, `first_name`, `last_name`, `avatar_url`, `preferred_theme`
- `roles`
  - `id`, `name`
- `permissions`
  - `id`, `name`, `resource`, `action`
- `user_roles`
  - `user_id`, `role_id`
- `role_permissions`
  - `role_id`, `permission_id`
- `sessions`
  - `id`, `user_id`, `refresh_token_hash`, `ip_address`, `user_agent`, `expires_at`
- `mfa_methods`
  - `id`, `user_id`, `type`, `secret`, `verified_at`

### Marketplace Actors

- `vendors`
  - `id`, `user_id`, `store_name`, `slug`, `verification_status`, `rating`, `wallet_balance`
- `customers`
  - `id`, `user_id`, `loyalty_points`, `default_address_id`
- `admins`
  - `id`, `user_id`, `title`

### Catalog

- `categories`
  - `id`, `parent_id`, `name`, `slug`, `status`, `sort_order`
- `brands`
  - `id`, `name`, `slug`, `status`
- `products`
  - `id`, `vendor_id`, `category_id`, `brand_id`, `name`, `slug`, `description`, `status`, `sku`, `barcode`
- `product_media`
  - `id`, `product_id`, `type`, `url`, `sort_order`, `alt_text`
- `product_variants`
  - `id`, `product_id`, `name`, `sku`, `price`, `compare_at_price`
- `inventory`
  - `id`, `product_id`, `variant_id`, `stock_on_hand`, `stock_reserved`, `low_stock_threshold`
- `product_attributes`
  - `id`, `product_id`, `attribute_name`, `attribute_value`

### Commerce

- `carts`
  - `id`, `customer_id`, `status`, `expires_at`
- `cart_items`
  - `id`, `cart_id`, `product_id`, `variant_id`, `quantity`, `unit_price`
- `wishlists`
  - `id`, `customer_id`
- `wishlist_items`
  - `id`, `wishlist_id`, `product_id`
- `coupons`
  - `id`, `code`, `type`, `value`, `starts_at`, `ends_at`, `usage_limit`
- `coupon_redemptions`
  - `id`, `coupon_id`, `customer_id`, `order_id`
- `orders`
  - `id`, `customer_id`, `vendor_id`, `status`, `payment_status`, `subtotal`, `shipping_amount`, `tax_amount`, `discount_amount`, `total_amount`
- `order_items`
  - `id`, `order_id`, `product_id`, `variant_id`, `quantity`, `unit_price`, `line_total`
- `order_status_history`
  - `id`, `order_id`, `status`, `changed_by_user_id`, `note`, `created_at`
- `returns`
  - `id`, `order_id`, `customer_id`, `status`, `reason`, `refund_amount`

### Payments and Finance

- `payment_providers`
  - `id`, `name`, `code`, `is_active`
- `payment_transactions`
  - `id`, `order_id`, `provider_id`, `reference`, `status`, `amount`, `currency`, `provider_payload`
- `refunds`
  - `id`, `payment_transaction_id`, `amount`, `status`, `reason`
- `wallet_transactions`
  - `id`, `vendor_id`, `type`, `amount`, `status`, `reference`
- `withdrawals`
  - `id`, `vendor_id`, `amount`, `status`, `requested_at`, `processed_at`

### Fulfillment and Communication

- `addresses`
  - `id`, `user_id`, `label`, `country`, `state`, `city`, `street`, `postal_code`, `phone`
- `shipments`
  - `id`, `order_id`, `carrier`, `tracking_number`, `status`, `estimated_delivery_at`
- `shipment_events`
  - `id`, `shipment_id`, `status`, `description`, `recorded_at`
- `notifications`
  - `id`, `user_id`, `channel`, `title`, `body`, `is_read`
- `messages`
  - `id`, `sender_user_id`, `recipient_user_id`, `order_id`, `body`
- `support_tickets`
  - `id`, `user_id`, `order_id`, `status`, `priority`, `subject`

### Analytics

- `page_views`
  - `id`, `session_id`, `user_id`, `path`, `referrer`, `created_at`
- `search_logs`
  - `id`, `user_id`, `query`, `results_count`, `created_at`
- `analytics_daily`
  - `date`, `gross_revenue`, `net_revenue`, `orders_count`, `customers_count`, `conversion_rate`

## Service Layer

- `AuthService`
  - register, login, refresh, forgot password, reset password, verify email, enable 2FA
- `CatalogService`
  - product CRUD, category CRUD, brand CRUD, media uploads, inventory updates
- `CartService`
  - add item, remove item, merge guest cart, apply coupon, quote totals
- `OrderService`
  - create order, reserve inventory, emit timeline events, vendor/admin notifications
- `PaymentService`
  - provider adapters for Paystack, Flutterwave, Stripe, PayPal, Apple Pay, Google Pay, COD
- `VendorService`
  - analytics, settlements, withdrawals, product moderation state
- `AdminService`
  - cross-marketplace analytics, audit logs, vendor approval, permissions
- `NotificationService`
  - in-app, email, SMS, push, toast event mapping

## API Surface

### Authentication

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `POST /api/v1/auth/verify-email`
- `POST /api/v1/auth/2fa/verify`

### Catalog

- `GET /api/v1/products`
- `GET /api/v1/products/:id`
- `POST /api/v1/vendor/products`
- `PATCH /api/v1/vendor/products/:id`
- `DELETE /api/v1/vendor/products/:id`
- `GET /api/v1/categories`
- `GET /api/v1/brands`

### Commerce

- `GET /api/v1/cart`
- `POST /api/v1/cart/items`
- `PATCH /api/v1/cart/items/:id`
- `DELETE /api/v1/cart/items/:id`
- `GET /api/v1/wishlist`
- `POST /api/v1/wishlist/items`
- `DELETE /api/v1/wishlist/items/:productId`
- `POST /api/v1/checkout`
- `GET /api/v1/orders`
- `GET /api/v1/orders/:id`
- `POST /api/v1/orders/:id/returns`

### Vendor

- `GET /api/v1/vendor/dashboard`
- `GET /api/v1/vendor/orders`
- `PATCH /api/v1/vendor/orders/:id/status`
- `GET /api/v1/vendor/wallet`
- `POST /api/v1/vendor/withdrawals`

### Admin

- `GET /api/v1/admin/dashboard`
- `GET /api/v1/admin/vendors`
- `PATCH /api/v1/admin/vendors/:id`
- `GET /api/v1/admin/products`
- `GET /api/v1/admin/orders`
- `GET /api/v1/admin/audit-logs`

## Security Requirements

- Enforce RBAC at route and resource level
- Hash passwords with Argon2 or bcrypt
- Sign JWT access tokens with short TTL and rotate refresh tokens
- Rate limit auth, checkout, and search endpoints
- Scan uploads, validate MIME types, and store signed URLs only
- Emit audit logs for admin and vendor-sensitive actions
- Encrypt provider secrets and environment variables

## Migration Strategy

- Replace `localStorage` reads in the new shared client layer with `fetch` or an API service
- Keep the existing method names as client facades to reduce UI churn
- Start with catalog, cart, checkout, and orders
- Add vendor product management and admin analytics next
- Move notifications and support to websocket or SSE after base CRUD is stable
