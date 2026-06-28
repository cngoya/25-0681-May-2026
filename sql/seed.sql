-- ═══════════════════════════════════════════
--  BLOOM & PETAL HAVEN — Seed / reference data
--
--  Loads the catalogue, bouquets, promotions and contact departments that
--  the website already displays. Safe to re-run: uses INSERT IGNORE against
--  the UNIQUE keys, so existing rows are not duplicated.
--
--    mysql -u root -p < sql/seed.sql
-- ═══════════════════════════════════════════

USE bloom_petal;

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
