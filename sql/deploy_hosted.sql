-- Bloom & Petal Haven — import into your host's pre-created database.
-- (InfinityFree/cPanel create the DB for you; do NOT add CREATE DATABASE.)
-- In your host's phpMyAdmin: select your DB, open the SQL/Import tab, run this.

-- ═══════════════════════════════════════════
--  BLOOM & PETAL HAVEN — Full database schema
--  MySQL 8+ / MariaDB
--
--  Creates the database and every table the system needs. Safe to
--  re-run: all tables use CREATE TABLE IF NOT EXISTS, so existing data
--  (e.g. real sign-ups in `users`) is never dropped.
--
--  Load it:
--    mysql -u root -p < sql/schema.sql
--  Then load the catalogue/reference data:
--    mysql -u root -p < sql/seed.sql
-- ═══════════════════════════════════════════


-- ─── Customers / sign-ups ───
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(120)  NOT NULL,
    email       VARCHAR(190)  NOT NULL,
    phone       VARCHAR(20)   NOT NULL,
    gender      ENUM('female', 'male', 'other', 'prefer-not') NOT NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Product categories (Fresh Flowers, Indoor Plants, …) ───
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Purchasable items: catalogue products AND featured bouquets ───
-- `kind` distinguishes a plain catalogue product from a bouquet.
-- care_level applies to plants/products; main_flower / occasion /
-- delivery_speed describe bouquets. Unifying them keeps order_items simple.
CREATE TABLE IF NOT EXISTS products (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id    INT UNSIGNED DEFAULT NULL,
    name           VARCHAR(150) NOT NULL,
    kind           ENUM('product', 'bouquet') NOT NULL DEFAULT 'product',
    description    TEXT         DEFAULT NULL,
    main_flower    VARCHAR(80)  DEFAULT NULL,
    occasion       VARCHAR(80)  DEFAULT NULL,
    care_level     ENUM('very_easy', 'easy', 'moderate', 'hard') DEFAULT NULL,
    delivery_speed ENUM('same_day', 'next_day') DEFAULT NULL,
    price          DECIMAL(10,2) NOT NULL,
    availability   ENUM('in_stock', 'limited', 'out_of_stock') NOT NULL DEFAULT 'in_stock',
    image_url      VARCHAR(500) DEFAULT NULL,
    is_best_seller TINYINT(1)   NOT NULL DEFAULT 0,
    is_featured    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_name (name),
    KEY idx_products_category (category_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Promotions / discount codes (e.g. BLOOM20) ───
CREATE TABLE IF NOT EXISTS promotions (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(40)  NOT NULL,
    title            VARCHAR(150) NOT NULL,
    description      VARCHAR(255) DEFAULT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    starts_at        DATE         DEFAULT NULL,
    ends_at          DATE         DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_promotions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Orders (header) ───
-- user_id is nullable so guests can check out; customer details are also
-- snapshotted on the order in case the account is later changed/removed.
CREATE TABLE IF NOT EXISTS orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED DEFAULT NULL,
    customer_name    VARCHAR(120) NOT NULL,
    customer_email   VARCHAR(190) NOT NULL,
    customer_phone   VARCHAR(20)  NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    delivery_time    VARCHAR(80)  DEFAULT NULL,
    promo_code       VARCHAR(40)  DEFAULT NULL,
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_fee     DECIMAL(10,2) NOT NULL DEFAULT 0,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0,
    status           ENUM('pending', 'confirmed', 'delivered', 'cancelled')
                         NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_user (user_id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Order line items ───
-- product_name / unit_price are snapshots so the order stays accurate even
-- if the product is later renamed or repriced.
CREATE TABLE IF NOT EXISTS order_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(150) NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT UNSIGNED NOT NULL DEFAULT 1,
    line_total   DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Contact / enquiry form submissions ───
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    phone      VARCHAR(20)  DEFAULT NULL,
    department VARCHAR(80)  DEFAULT NULL,
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Contact departments (reference data shown on the Contact page) ───
CREATE TABLE IF NOT EXISTS departments (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(80)  NOT NULL,
    contact_person VARCHAR(120) DEFAULT NULL,
    phone          VARCHAR(30)  DEFAULT NULL,
    email          VARCHAR(190) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════
--  BLOOM & PETAL HAVEN — Seed / reference data
--
--  Loads the catalogue, bouquets, promotions and contact departments that
--  the website already displays. Safe to re-run: uses INSERT IGNORE against
--  the UNIQUE keys, so existing rows are not duplicated.
--
--    mysql -u root -p < sql/seed.sql
-- ═══════════════════════════════════════════


-- ─── Categories ───
INSERT IGNORE INTO categories (name, slug, description) VALUES
    ('Fresh Flowers',  'fresh-flowers',  'Handpicked fresh-cut flowers, delivered daily.'),
    ('Indoor Plants',  'indoor-plants',  'Easy-care greenery for homes and offices.'),
    ('Outdoor Plants', 'outdoor-plants', 'Shrubs, climbers and flowering trees for gardens.'),
    ('Herbs & Flowers','herbs-flowers',  'Fragrant herbs and flowering pots.'),
    ('Accessories',    'accessories',    'Pots, planters, fertilizers and gift wrapping.'),
    ('Bouquets',       'bouquets',       'Ready-made arrangements for every occasion.');

-- ─── Catalogue products (from the "Product Catalogue" table) ───
INSERT IGNORE INTO products
    (category_id, name, kind, care_level, price, availability, image_url, is_best_seller)
VALUES
    ((SELECT id FROM categories WHERE slug='fresh-flowers'),
        'Red Roses (Dozen)', 'product', 'easy',      2000.00, 'in_stock',
        'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=300', 0),
    ((SELECT id FROM categories WHERE slug='indoor-plants'),
        'Peace Lily', 'product', 'easy',             1400.00, 'in_stock',
        'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=300', 1),
    ((SELECT id FROM categories WHERE slug='indoor-plants'),
        'Aloe Vera Succulent', 'product', 'very_easy', 800.00, 'in_stock',
        'https://images.unsplash.com/photo-1509423350716-97f9360b4e09?w=300', 0),
    ((SELECT id FROM categories WHERE slug='outdoor-plants'),
        'Bougainvillea', 'product', 'moderate',      1900.00, 'limited',
        'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300', 0),
    ((SELECT id FROM categories WHERE slug='indoor-plants'),
        'Orchid Plant', 'product', 'moderate',       2800.00, 'in_stock',
        'https://images.unsplash.com/photo-1487070183336-b863922373d4?w=300', 0),
    ((SELECT id FROM categories WHERE slug='herbs-flowers'),
        'Lavender Pot', 'product', 'easy',           1300.00, 'in_stock',
        'https://images.unsplash.com/photo-1502780402662-acc01917738e?w=300', 0);

-- ─── Featured bouquets (from the "Featured Bouquets & Prices" table) ───
INSERT IGNORE INTO products
    (category_id, name, kind, main_flower, occasion, delivery_speed, price, availability, image_url, is_featured, is_best_seller)
VALUES
    ((SELECT id FROM categories WHERE slug='bouquets'),
        'Rose Romance', 'bouquet', 'Red Roses',      'Anniversary',     'same_day', 2500.00, 'in_stock',
        'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=300', 1, 1),
    ((SELECT id FROM categories WHERE slug='bouquets'),
        'Sunshine Mix', 'bouquet', 'Sunflowers',     'Birthday',        'same_day', 1800.00, 'in_stock',
        'https://images.unsplash.com/photo-1462530260150-162092dbf011?w=300', 1, 0),
    ((SELECT id FROM categories WHERE slug='bouquets'),
        'Lavender Calm', 'bouquet', 'Lavender',      'Get Well',        'next_day', 1500.00, 'in_stock',
        'https://images.unsplash.com/photo-1502780402662-acc01917738e?w=300', 1, 0),
    ((SELECT id FROM categories WHERE slug='bouquets'),
        'Tropical Bliss', 'bouquet', 'Orchids',      'Congratulations', 'same_day', 3200.00, 'in_stock',
        'https://images.unsplash.com/photo-1487070183336-b863922373d4?w=300', 1, 1),
    ((SELECT id FROM categories WHERE slug='bouquets'),
        'Garden Fresh', 'bouquet', 'Mixed Seasonal', 'Just Because',    'next_day', 1200.00, 'in_stock',
        'https://images.unsplash.com/photo-1558603668-6570496b66f8?w=300', 1, 0);

-- ─── Backfill images for any existing rows that pre-date the image columns ───
UPDATE products SET image_url='https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300' WHERE name='Bougainvillea'   AND image_url IS NULL;
UPDATE products SET image_url='https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=300' WHERE name='Rose Romance'    AND image_url IS NULL;
UPDATE products SET image_url='https://images.unsplash.com/photo-1462530260150-162092dbf011?w=300' WHERE name='Sunshine Mix'    AND image_url IS NULL;
UPDATE products SET image_url='https://images.unsplash.com/photo-1502780402662-acc01917738e?w=300' WHERE name='Lavender Calm'   AND image_url IS NULL;
UPDATE products SET image_url='https://images.unsplash.com/photo-1487070183336-b863922373d4?w=300' WHERE name='Tropical Bliss'  AND image_url IS NULL;
UPDATE products SET image_url='https://images.unsplash.com/photo-1558603668-6570496b66f8?w=300' WHERE name='Garden Fresh'    AND image_url IS NULL;

-- ─── Promotions (from the promo modal) ───
INSERT IGNORE INTO promotions
    (code, title, description, discount_percent, min_order_amount, is_active)
VALUES
    ('BLOOM20', 'Sign-up Welcome Offer', '20% off your first order.',              20.00,    0.00, 1),
    ('FREEDEL', 'Free Delivery',         'Free delivery on orders above KES 3,000.', 0.00, 3000.00, 1);

-- ─── Contact departments (from the "Get In Touch" table) ───
INSERT IGNORE INTO departments (name, contact_person, phone, email) VALUES
    ('Orders',             'Amina',      '0712 345 678', 'orders@bloompetal.co.ke'),
    ('Customer Care',      'David',      '0723 456 789', 'care@bloompetal.co.ke'),
    ('Deliveries',         'Grace',      '0734 567 890', 'delivery@bloompetal.co.ke'),
    ('Plant Care Advice',  'Michael',    '0745 678 901', 'care.advice@bloompetal.co.ke'),
    ('General Enquiries',  'Front Desk', '0756 789 012', 'info@bloompetal.co.ke');
