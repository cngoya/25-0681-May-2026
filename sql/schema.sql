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

CREATE DATABASE IF NOT EXISTS bloom_petal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bloom_petal;

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
