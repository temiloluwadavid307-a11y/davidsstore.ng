-- Full original copy of codesbyd4.sql (created by migration tool)

-- ============================================================
-- Swagbag - Premium Fashion E-Commerce Database
-- Import this file into your chosen database using phpMyAdmin or command line.
-- Example: mysql -u your_user -p your_database < database/codesbyd4.sql
-- ============================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS vendors;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories
-- ------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-tshirt',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Brands
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT NULL,
    brand_id INT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    sku VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    features TEXT,
    specifications TEXT,
    price DECIMAL(12,2) NOT NULL,
    old_price DECIMAL(12,2) DEFAULT NULL,
    discount_percent INT DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0,
    reviews_count INT DEFAULT 0,
    availability ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    image_primary VARCHAR(500),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_price (price),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Product Images
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NULL,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    notes TEXT,
    subtotal DECIMAL(12,2) NOT NULL,
    shipping_fee DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'pay_on_delivery',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Order Items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Contact Messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Newsletter Subscribers
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- ------------------------------------------------------------
-- Email verification tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Password reset tokens
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Email delivery logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Email queue for retrying failed deliveries
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html TEXT NOT NULL,
    text TEXT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    last_attempt_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;
-- ============================================================
-- SEED DATA
-- ============================================================

-- Vendors (primary store vendor)
INSERT INTO vendors (name, email, is_active) VALUES
('David Store Main', 'store@davidsstore.ng', 1);

-- Categories
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Hoodies', 'hoodies', 'Premium heavyweight hoodies and sweatshirts', 'fa-tshirt', 1),
('T-Shirts', 't-shirts', 'Oversized tees, graphic prints, and essentials', 'fa-tshirt', 2),
('Cargo Pants', 'cargo-pants', 'Utility cargos and tactical streetwear pants', 'fa-socks', 3),
('Denim', 'denim', 'Premium denim jackets, jeans, and skirts', 'fa-vest', 4),
('Sneakers', 'sneakers', 'Limited edition and lifestyle sneakers', 'fa-shoe-prints', 5),
('Caps', 'caps', 'Structured caps, dad hats, and snapbacks', 'fa-hat-cowboy', 6),
('Accessories', 'accessories', 'Bags, belts, jewelry, and lifestyle accessories', 'fa-gem', 7);

-- Brands
INSERT INTO brands (name, slug, description) VALUES
('David Originals', 'david-originals', 'In-house premium streetwear line'),
('Lagos Atelier', 'lagos-atelier', 'Contemporary Nigerian fashion house'),
('Afro Street Co.', 'afro-street', 'Bold African-inspired streetwear'),
('Naija Kicks', 'naija-kicks', 'Premium footwear for the culture'),
('Urban Heritage', 'urban-heritage', 'Heritage meets modern street style'),
('Code District', 'code-district', 'Tech-meets-fashion lifestyle brand'),
('Prime Label', 'prime-label', 'Luxury casual wear and essentials'),
('Metro Craft', 'metro-craft', 'Handcrafted accessories and leather goods');

-- Admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
('David', 'Admin', 'admin@davidsstore.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Demo customer (password: customer123)
INSERT INTO users (first_name, last_name, email, password_hash, phone, role) VALUES
('Adaeze', 'Okonkwo', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08012345678', 'customer');

-- ============================================================
-- PRODUCTS (35 Premium Fashion Items)
-- ============================================================

INSERT INTO products (vendor_id, category_id, brand_id, name, slug, sku, description, features, specifications, price, old_price, discount_percent, stock_quantity, rating, reviews_count, availability, image_primary, is_featured) VALUES

-- HOODIES (1-5)
(1, 1, 1, 'Premium Fleece Hoodie — Midnight Black', 'premium-fleece-hoodie-midnight-black', 'DS-HD-001',
 'Crafted from 400gsm heavyweight French terry fleece, this premium hoodie delivers unmatched warmth and structure. Features a double-layered hood, kangaroo pocket, and reinforced ribbed cuffs built for Lagos nights and Abuja mornings.',
 'Heavyweight 400gsm fleece|Double-layered hood|Kangaroo front pocket|Reinforced ribbed cuffs|Pre-shrunk fabric|Oversized relaxed fit',
 'Material: 80% Cotton, 20% Polyester|Weight: 400gsm|Fit: Oversized|Care: Machine wash cold|Sizes: S, M, L, XL, XXL',
 38500, 52000, 26, 45, 4.8, 312, 'in_stock',
 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&h=600&fit=crop', 1),

(1, 1, 3, 'Afro Street Heritage Hoodie — Earth Tone', 'afro-street-heritage-hoodie-earth', 'DS-HD-002',
 'A celebration of African heritage through premium streetwear. Features embroidered Adinkra symbols on the chest, earth-tone colourway, and a brushed interior for all-day comfort.',
 'Embroidered Adinkra symbols|Brushed fleece interior|Earth-tone palette|Drop-shoulder construction|Limited edition run',
 'Material: 85% Organic Cotton, 15% Recycled Polyester|Fit: Relaxed|Colours: Terracotta, Olive|Sizes: M–XXL',
 42000, 55000, 24, 28, 4.9, 187, 'in_stock',
 'https://images.unsplash.com/photo-1578587018453-892b5fccb824?w=600&h=600&fit=crop', 1),

(1, 1, 5, 'Urban Heritage Zip Hoodie — Slate Grey', 'urban-heritage-zip-hoodie-slate', 'DS-HD-003',
 'Full-zip heavyweight hoodie with matte gunmetal hardware, side zip pockets, and a structured silhouette that transitions seamlessly from studio to street.',
 'Full-zip front|Matte gunmetal YKK zipper|Side zip pockets|Structured silhouette|Premium ribbed hem',
 'Material: 70% Cotton, 30% Polyester|Weight: 380gsm|Fit: Regular|Sizes: S–XXL',
 45000, NULL, 0, 33, 4.6, 94, 'in_stock',
 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&h=600&fit=crop', 0),

(1, 1, 2, 'Lagos Atelier Cropped Hoodie — Cream', 'lagos-atelier-cropped-hoodie-cream', 'DS-HD-004',
 'A refined cropped hoodie in soft cream French terry. Perfect for layering over high-waisted cargos or denim. Designed and cut in Lagos.',
 'Cropped length|Soft cream French terry|Raw-edge hem detail|Designed in Lagos|Limited 200-piece run',
 'Material: 100% Organic Cotton|Fit: Cropped, Relaxed|Length: 52cm (size M)|Sizes: XS–L',
 35000, 42000, 17, 18, 4.7, 56, 'low_stock',
 'https://images.unsplash.com/photo-1509941945505-73608b42f4e1?w=600&h=600&fit=crop', 1),

(1, 1, 7, 'Prime Label Tech Fleece Hoodie — Navy', 'prime-label-tech-fleece-navy', 'DS-HD-005',
 'Engineered with four-way stretch tech fleece for maximum mobility. Water-resistant outer shell with breathable mesh lining — built for the modern commuter.',
 'Four-way stretch tech fleece|Water-resistant shell|Breathable mesh lining|Reflective logo detail|Hidden phone pocket',
 'Material: 88% Polyester, 12% Elastane|Water Resistance: DWR coating|Fit: Athletic|Sizes: S–XXL',
 48000, 62000, 23, 22, 4.5, 78, 'in_stock', 0),

-- T-SHIRTS (6-11)
(1, 2, 1, 'Oversized Essential Tee — Pure White', 'oversized-essential-tee-white', 'DS-TS-001',
 'The foundation of every wardrobe. Our signature oversized tee in premium 220gsm combed cotton with a dropped shoulder and extended body length for that effortless street look.',
 '220gsm combed cotton|Dropped shoulder|Extended body length|Reinforced neckline|Pre-washed for softness',
 'Material: 100% Combed Cotton|Weight: 220gsm|Fit: Oversized|Sizes: S–XXL|Colours: White, Black, Sand',
 18000, 24000, 25, 120, 4.7, 445, 'in_stock',
 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=600&fit=crop', 1),

-- (file continues)
