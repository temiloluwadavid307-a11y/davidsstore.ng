-- ============================================================
-- David's Store - Premium Fashion E-Commerce Database
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
 48000, 62000, 23, 22, 4.5, 78, 'in_stock',
 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=600&fit=crop', 0),

-- T-SHIRTS (6-11)
(1, 2, 1, 'Oversized Essential Tee — Pure White', 'oversized-essential-tee-white', 'DS-TS-001',
 'The foundation of every wardrobe. Our signature oversized tee in premium 220gsm combed cotton with a dropped shoulder and extended body length for that effortless street look.',
 '220gsm combed cotton|Dropped shoulder|Extended body length|Reinforced neckline|Pre-washed for softness',
 'Material: 100% Combed Cotton|Weight: 220gsm|Fit: Oversized|Sizes: S–XXL|Colours: White, Black, Sand',
 18000, 24000, 25, 120, 4.7, 445, 'in_stock',
 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=600&fit=crop', 1),

(1, 2, 3, 'Afro Street Graphic Tee — Lagos Nights', 'afro-street-graphic-tee-lagos', 'DS-TS-002',
 'Bold screen-printed graphic celebrating Lagos nightlife culture. Plastisol ink on premium heavyweight cotton with a vintage wash finish.',
 'Screen-printed Lagos graphic|Vintage wash finish|Plastisol ink (crack-resistant)|Heavyweight 240gsm|Unisex fit',
 'Material: 100% Cotton|Weight: 240gsm|Print: Screen print|Sizes: S–XXL',
 22000, 28000, 21, 67, 4.8, 203, 'in_stock',
 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600&h=600&fit=crop', 1),

(1, 2, 6, 'Code District Minimal Logo Tee — Black', 'code-district-minimal-tee-black', 'DS-TS-003',
 'Clean, minimal branding on premium black cotton. Embroidered logo on chest, perfect for tech professionals who appreciate understated style.',
 'Embroidered chest logo|Premium black cotton|Minimal branding|Side-seam construction|Tagless label',
 'Material: 100% Cotton|Weight: 200gsm|Logo: Embroidered|Sizes: S–XXL',
 16000, NULL, 0, 89, 4.4, 67, 'in_stock',
 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=600&h=600&fit=crop', 0),

(1, 2, 2, 'Lagos Atelier Linen Blend Tee — Sage Green', 'lagos-atelier-linen-tee-sage', 'DS-TS-004',
 'Breathable linen-cotton blend tee designed for Nigerian heat. Relaxed fit with a subtle curved hem and tonal stitching throughout.',
 'Linen-cotton blend|Breathable for hot climate|Curved hem|Tonal stitching|Naturally cooling fabric',
 'Material: 55% Linen, 45% Cotton|Fit: Relaxed|Colours: Sage, Oatmeal|Sizes: S–XL',
 19500, 25000, 22, 54, 4.6, 112, 'in_stock',
 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=600&h=600&fit=crop', 0),

(1, 2, 5, 'Urban Heritage Longline Tee — Charcoal', 'urban-heritage-longline-tee-charcoal', 'DS-TS-005',
 'Extended longline cut with raw-edge hem and side slits. Heavyweight jersey construction that holds its shape wash after wash.',
 'Longline extended cut|Raw-edge hem|Side slit details|Heavyweight jersey|Shape-retaining fabric',
 'Material: 100% Cotton Jersey|Weight: 260gsm|Length: +8cm vs standard|Sizes: M–XXL',
 20000, 26000, 23, 41, 4.5, 88, 'in_stock',
 'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=600&h=600&fit=crop', 0),

(1, 2, 7, 'Prime Label Pima Cotton Polo — Navy', 'prime-label-pima-polo-navy', 'DS-TS-006',
 'Luxury Pima cotton polo with mother-of-pearl buttons and a structured collar. The elevated essential for smart-casual occasions.',
 'Pima cotton construction|Mother-of-pearl buttons|Structured collar|Side vent hem|Wrinkle-resistant finish',
 'Material: 100% Pima Cotton|Fit: Slim|Colours: Navy, White, Burgundy|Sizes: S–XXL',
 28000, 35000, 20, 36, 4.7, 45, 'in_stock',
 'https://images.unsplash.com/photo-1586363104862-3a5e2a60d5c0?w=600&h=600&fit=crop', 0),

-- CARGO PANTS (12-16)
(1, 3, 1, 'Tactical Cargo Pants — Olive Drab', 'tactical-cargo-pants-olive', 'DS-CG-001',
 'Six-pocket utility cargos in durable ripstop cotton. Articulated knees, adjustable ankle cuffs, and reinforced stitching at stress points.',
 'Six utility pockets|Ripstop cotton fabric|Articulated knee panels|Adjustable ankle cuffs|Reinforced stress points',
 'Material: 100% Cotton Ripstop|Fit: Relaxed Tapered|Pockets: 6|Sizes: 28–38|Colours: Olive, Black, Khaki',
 32500, 42000, 23, 38, 4.8, 267, 'in_stock',
 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&h=600&fit=crop', 1),

(1, 3, 3, 'Afro Street Wide-Leg Cargo — Sand', 'afro-street-wide-leg-cargo-sand', 'DS-CG-002',
 'Oversized wide-leg cargo pants with contrast stitching and oversized flap pockets. A statement piece for the bold dresser.',
 'Wide-leg silhouette|Contrast topstitching|Oversized flap pockets|Elasticated back waistband|Drawstring ankle',
 'Material: 97% Cotton, 3% Elastane|Fit: Wide Leg|Rise: Mid|Sizes: S–XXL',
 35000, 45000, 22, 25, 4.7, 134, 'in_stock',
 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=600&h=600&fit=crop', 1),

(1, 3, 5, 'Urban Heritage Slim Cargo — Black', 'urban-heritage-slim-cargo-black', 'DS-CG-003',
 'Streamlined slim-fit cargo with hidden zip pockets and a clean silhouette. Perfect for transitioning from office casual to evening out.',
 'Slim tapered fit|Hidden zip pockets|Clean minimal silhouette|Stretch waistband|Wrinkle-free finish',
 'Material: 65% Cotton, 35% Polyester|Fit: Slim Tapered|Sizes: 28–36',
 30000, NULL, 0, 42, 4.5, 89, 'in_stock',
 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&h=600&fit=crop', 0),

(1, 3, 2, 'Lagos Atelier Parachute Cargo — Stone', 'lagos-atelier-parachute-cargo-stone', 'DS-CG-004',
 'Lightweight parachute nylon cargos with a fluid drape and snap-button cargo pockets. Inspired by 90s utility wear, reimagined for today.',
 'Parachute nylon fabric|Fluid drape|Snap-button cargo pockets|Drawstring waist|90s-inspired design',
 'Material: 100% Nylon|Weight: Lightweight|Fit: Relaxed|Sizes: S–XL',
 38000, 48000, 21, 19, 4.6, 72, 'low_stock',
 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=600&h=600&fit=crop', 0),

(1, 3, 7, 'Prime Label Convertible Cargo — Grey', 'prime-label-convertible-cargo-grey', 'DS-CG-005',
 'Innovative convertible cargo pants with zip-off lower legs. Transform from full-length cargos to shorts in seconds — two styles in one.',
 'Zip-off convertible design|8-pocket configuration|Quick-dry fabric|UV protection UPF 30|Reinforced seat panel',
 'Material: 92% Nylon, 8% Spandex|Feature: Zip-off legs|Sizes: 30–38|Colours: Grey, Black',
 42000, 55000, 24, 15, 4.4, 38, 'low_stock',
 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=600&h=600&fit=crop', 0),

-- DENIM (17-20)
(1, 4, 5, 'Urban Heritage Raw Denim Jacket — Indigo', 'urban-heritage-raw-denim-jacket', 'DS-DN-001',
 'Unwashed 14oz selvedge denim jacket that molds to your body over time. Copper rivets, chain-stitched hem, and a classic Type III silhouette.',
 '14oz selvedge denim|Unwashed raw finish|Copper rivets|Chain-stitched hem|Type III trucker silhouette',
 'Material: 100% Cotton Selvedge Denim|Weight: 14oz|Fit: Regular|Sizes: S–XXL|Origin: Japanese denim',
 55000, 68000, 19, 24, 4.9, 156, 'in_stock',
 'https://images.unsplash.com/photo-1576995853173-a1910696a0a8?w=600&h=600&fit=crop', 1),

(1, 4, 1, 'David Originals Distressed Denim — Light Wash', 'david-originals-distressed-denim', 'DS-DN-002',
 'Premium stretch denim with hand-finished distressing and a comfortable mid-rise fit. Tapered leg with a clean stack at the ankle.',
 'Hand-finished distressing|Stretch comfort denim|Mid-rise fit|Tapered leg|Clean ankle stack',
 'Material: 98% Cotton, 2% Elastane|Fit: Slim Tapered|Rise: Mid|Sizes: 28–38|Wash: Light',
 38000, 48000, 21, 35, 4.6, 198, 'in_stock',
 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=600&h=600&fit=crop', 1),

(1, 4, 2, 'Lagos Atelier Wide Denim — Dark Rinse', 'lagos-atelier-wide-denim-dark', 'DS-DN-003',
 'Wide-leg denim jeans in a deep dark rinse. High-waisted with a flattering wide silhouette that pairs perfectly with cropped tops and oversized tees.',
 'Wide-leg silhouette|High-waisted|Deep dark rinse|Contrast stitching|Comfort stretch waistband',
 'Material: 99% Cotton, 1% Elastane|Fit: Wide Leg|Rise: High|Sizes: 26–36',
 36000, NULL, 0, 28, 4.7, 87, 'in_stock',
 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&h=600&fit=crop', 0),

(1, 4, 3, 'Afro Street Denim Vest — Washed Black', 'afro-street-denim-vest-washed', 'DS-DN-004',
 'Sleeveless denim vest with custom enamel pins and a cropped cut. Layer over hoodies or tees for an edgy streetwear statement.',
 'Sleeveless cropped cut|Custom enamel pin set|Washed black finish|Adjustable back tabs|Raw-edge armholes',
 'Material: 100% Cotton Denim|Fit: Cropped|Sizes: S–XL|Includes: 3 enamel pins',
 28000, 35000, 20, 31, 4.5, 64, 'in_stock',
 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&h=600&fit=crop', 0),

-- SNEAKERS (21-26)
(1, 5, 4, 'Naija Kicks Air Runner — White/Gold', 'naija-kicks-air-runner-white-gold', 'DS-SN-001',
 'Premium lifestyle sneaker with full-grain leather upper, gold accent detailing, and a responsive EVA midsole. The flagship silhouette of Naija Kicks.',
 'Full-grain leather upper|Gold accent detailing|Responsive EVA midsole|Rubber outsole with traction pattern|Premium woven laces',
 'Upper: Full-grain leather|Midsole: EVA foam|Outsole: Rubber|Sizes: 40–46|Colours: White/Gold, Black/Gold',
 72000, 95000, 24, 18, 4.9, 423, 'in_stock',
 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&h=600&fit=crop', 1),

(1, 5, 4, 'Naija Kicks Street Classic — Triple Black', 'naija-kicks-street-classic-triple-black', 'DS-SN-002',
 'All-black suede and mesh construction with a gum sole. A versatile daily driver that pairs with everything in your rotation.',
 'Suede and mesh upper|Gum rubber sole|All-black colourway|Memory foam insole|Padded collar and tongue',
 'Upper: Suede/Mesh|Sole: Gum rubber|Sizes: 39–45|Insole: Memory foam',
 58000, 75000, 23, 32, 4.7, 289, 'in_stock',
 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&h=600&fit=crop', 1),

(1, 5, 6, 'Code District Tech Sneaker — Grey/Neon', 'code-district-tech-sneaker-grey', 'DS-SN-003',
 'Knit upper with neon accent threading and a lightweight Phylon sole. Built for the urban explorer who values comfort and tech aesthetics.',
 'Knit upper construction|Neon accent threading|Phylon lightweight sole|Reflective heel tab|Breathable mesh panels',
 'Upper: Engineered knit|Sole: Phylon|Weight: 280g (size 42)|Sizes: 40–45',
 65000, 82000, 21, 14, 4.6, 167, 'low_stock',
 'https://images.unsplash.com/photo-1608231387042-66d1773070a6?w=600&h=600&fit=crop', 1),

(1, 5, 7, 'Prime Label Court Low — Cream/Leather', 'prime-label-court-low-cream', 'DS-SN-004',
 'Minimalist court sneaker in premium vegetable-tanned leather. Clean lines, cupsole construction, and a timeless silhouette.',
 'Vegetable-tanned leather|Cupsole construction|Minimal branding|Cork-lined footbed|Timeless court silhouette',
 'Upper: Veg-tan leather|Construction: Cupsole|Sizes: 40–46|Lining: Cork footbed',
 68000, NULL, 0, 20, 4.8, 98, 'in_stock',
 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&h=600&fit=crop', 0),

(1, 5, 4, 'Naija Kicks High-Top Legend — Red/White', 'naija-kicks-high-top-legend-red', 'DS-SN-005',
 'Iconic high-top silhouette with premium nubuck upper and a vintage basketball-inspired design. Padded high collar for ankle support and style.',
 'Premium nubuck upper|High-top padded collar|Vintage basketball design|Perforated toe box|Rubber toe cap',
 'Upper: Nubuck leather|Style: High-top|Sizes: 40–45|Colours: Red/White, Black/White',
 75000, 98000, 23, 12, 4.8, 234, 'low_stock',
 'https://images.unsplash.com/photo-1600269452121-4f2417e55c28?w=600&h=600&fit=crop', 1),

(1, 5, 5, 'Urban Heritage Retro Runner — Multi', 'urban-heritage-retro-runner-multi', 'DS-SN-006',
 'Multi-panel retro runner with mixed material construction — suede, mesh, and nylon. Chunky midsole for that 90s dad-shoe aesthetic.',
 'Multi-material panels|Suede, mesh, nylon mix|Chunky 90s midsole|Woven heel pull tab|Colour-blocked design',
 'Upper: Suede/Mesh/Nylon|Midsole: Chunky EVA|Sizes: 39–44|Style: Retro runner',
 62000, 78000, 21, 16, 4.5, 145, 'in_stock',
 'https://images.unsplash.com/photo-1605348532765-675e3be62e39?w=600&h=600&fit=crop', 0),

-- CAPS (27-30)
(1, 6, 1, 'David Originals Structured Cap — Black', 'david-originals-structured-cap-black', 'DS-CAP-001',
 'Six-panel structured cap with embroidered logo, adjustable snapback closure, and a curved brim. The everyday essential.',
 'Six-panel structured build|Embroidered front logo|Adjustable snapback|Curved brim|Breathable eyelets',
 'Material: 100% Cotton Twill|Closure: Snapback|Sizes: One Size|Colours: Black, Navy, Olive',
 12500, 16000, 22, 85, 4.6, 178, 'in_stock',
 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&h=600&fit=crop', 0),

(1, 6, 3, 'Afro Street Dad Hat — Embroidered', 'afro-street-dad-hat-embroidered', 'DS-CAP-002',
 'Unstructured dad hat with tonal embroidered African map motif. Soft washed cotton with a relaxed, broken-in feel from day one.',
 'Unstructured dad hat|Embroidered African map|Tonal embroidery|Washed soft cotton|Metal buckle closure',
 'Material: 100% Washed Cotton|Style: Dad Hat|Closure: Metal buckle|Colours: Black, Tan, Forest',
 15000, 19000, 21, 62, 4.7, 92, 'in_stock',
 'https://images.unsplash.com/photo-1575428652377-a2d80e2277fc?w=600&h=600&fit=crop', 0),

(1, 6, 5, 'Urban Heritage 5-Panel Camp Cap — Olive', 'urban-heritage-5panel-camp-olive', 'DS-CAP-003',
 'Five-panel camp cap in water-resistant nylon with a short flat brim. Lightweight and packable — ideal for outdoor adventures.',
 'Five-panel camp style|Water-resistant nylon|Short flat brim|Packable design|Adjustable webbing strap',
 'Material: Nylon ripstop|Water Resistance: DWR|Style: 5-Panel Camp|Colours: Olive, Black, Rust',
 18000, NULL, 0, 47, 4.5, 56, 'in_stock',
 'https://images.unsplash.com/photo-1514327605112-a3a028e615e9?w=600&h=600&fit=crop', 0),

(1, 6, 7, 'Prime Label Wool Blend Cap — Charcoal', 'prime-label-wool-blend-cap-charcoal', 'DS-CAP-004',
 'Premium wool blend cap with a leather strap closure and laser-etched logo. Elevated headwear for the discerning dresser.',
 'Wool blend construction|Leather strap closure|Laser-etched logo|Structured crown|Satin lining',
 'Material: 80% Wool, 20% Polyester|Closure: Leather strap|Sizes: One Size|Colours: Charcoal, Burgundy',
 22000, 28000, 21, 30, 4.8, 41, 'in_stock',
 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=600&h=600&fit=crop', 0),

-- ACCESSORIES (31-35)
(1, 7, 8, 'Metro Craft Leather Crossbody — Tan', 'metro-craft-leather-crossbody-tan', 'DS-AC-001',
 'Handcrafted full-grain leather crossbody bag with brass hardware and adjustable strap. Fits phone, wallet, and essentials with a slim profile.',
 'Full-grain leather|Brass hardware|Adjustable crossbody strap|Slim profile|Interior card slots',
 'Material: Full-grain leather|Dimensions: 22x15x5cm|Hardware: Solid brass|Colours: Tan, Black',
 45000, 58000, 22, 22, 4.9, 134, 'in_stock',
 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=600&fit=crop', 1),

(1, 7, 8, 'Metro Craft Canvas Tote — Natural', 'metro-craft-canvas-tote-natural', 'DS-AC-002',
 'Heavy-duty 18oz canvas tote with leather handles and an interior zip pocket. Spacious enough for market runs or weekend getaways.',
 '18oz heavy canvas|Leather handle wraps|Interior zip pocket|Reinforced bottom panel|Machine washable',
 'Material: 18oz Canvas + Leather|Dimensions: 40x35x12cm|Handles: Leather wrapped|Colours: Natural, Black',
 28000, 35000, 20, 38, 4.6, 87, 'in_stock',
 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=600&h=600&fit=crop', 0),

(1, 7, 6, 'Code District Tech Sling Bag — Black', 'code-district-tech-sling-black', 'DS-AC-003',
 'Water-resistant tech sling with padded laptop compartment (fits 13"), cable organizer, and quick-access front pocket.',
 'Water-resistant exterior|Padded 13" laptop slot|Cable organizer pocket|Quick-access front zip|Adjustable strap',
 'Material: 600D Polyester|Laptop: Up to 13"|Water Resistance: IPX4|Colours: Black, Grey',
 32000, 40000, 20, 29, 4.5, 63, 'in_stock',
 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&h=600&fit=crop', 0),

(1, 7, 7, 'Prime Label Leather Belt — Brown', 'prime-label-leather-belt-brown', 'DS-AC-004',
 'Italian leather belt with a brushed nickel buckle and hand-finished edges. A timeless accessory that elevates any outfit.',
 'Italian leather|Brushed nickel buckle|Hand-finished edges|Full-grain construction|Gift box included',
 'Material: Italian Full-grain Leather|Width: 3.5cm|Sizes: 30–42|Colours: Brown, Black',
 18500, NULL, 0, 55, 4.7, 48, 'in_stock',
 'https://images.unsplash.com/photo-1624222247344-550fb60583fd?w=600&h=600&fit=crop', 0),

(1, 7, 2, 'Lagos Atelier Beaded Bracelet Set — Multi', 'lagos-atelier-beaded-bracelet-set', 'DS-AC-005',
 'Handmade set of three beaded bracelets featuring traditional Nigerian glass beads and brass accents. Each set is unique.',
 'Handmade in Lagos|Traditional glass beads|Brass accent beads|Set of three|Adjustable elastic fit',
 'Material: Glass beads, Brass|Set: 3 bracelets|Origin: Handmade Lagos|One size fits most',
 8500, 12000, 29, 95, 4.8, 212, 'in_stock',
 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600&h=600&fit=crop', 1);

-- Product Images (secondary images for featured products)
INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1578587018453-892b5fccb824?w=600&h=600&fit=crop', 'Premium Fleece Hoodie back view', 1),
(1, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&h=600&fit=crop', 'Premium Fleece Hoodie detail', 2),
(21, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&h=600&fit=crop', 'Naija Kicks Air Runner side view', 1),
(21, 'https://images.unsplash.com/photo-1608231387042-66d1773070a6?w=600&h=600&fit=crop', 'Naija Kicks Air Runner sole detail', 2),
(6, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600&h=600&fit=crop', 'Oversized Tee styled look', 1),
(12, 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=600&h=600&fit=crop', 'Tactical Cargo styled outfit', 1),
(17, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&h=600&fit=crop', 'Raw Denim Jacket detail', 1),
(31, 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=600&h=600&fit=crop', 'Leather Crossbody interior', 1);
