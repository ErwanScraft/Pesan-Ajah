-- =========================================================
-- PESAN AJAH
-- Database Schema
-- MySQL / MariaDB
-- =========================================================

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;


-- =========================================================
-- 1. USERS
-- Akun administrator dan pemilik warung
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,

    role ENUM('admin', 'warung')
        NOT NULL DEFAULT 'warung',

    is_active TINYINT(1)
        NOT NULL DEFAULT 1,

    last_login_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_users_username (username),

    KEY idx_users_role (role),
    KEY idx_users_active (is_active)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 2. STORES
-- Profil dan pengaturan warung
-- =========================================================

CREATE TABLE IF NOT EXISTS stores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,

    description TEXT DEFAULT NULL,

    owner_name VARCHAR(150) DEFAULT NULL,

    phone VARCHAR(25) NOT NULL,

    address VARCHAR(255) DEFAULT NULL,

    logo VARCHAR(255) DEFAULT NULL,

    is_open TINYINT(1)
        NOT NULL DEFAULT 1,

    is_active TINYINT(1)
        NOT NULL DEFAULT 1,

    free_delivery TINYINT(1)
        NOT NULL DEFAULT 0,

    delivery_fee INT UNSIGNED
        NOT NULL DEFAULT 1000,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_stores_user_id (user_id),
    UNIQUE KEY uq_stores_slug (slug),

    KEY idx_stores_active_open (
        is_active,
        is_open
    ),

    CONSTRAINT fk_stores_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 3. PRODUCTS
-- Produk yang dijual oleh setiap warung
-- =========================================================

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    store_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(150) NOT NULL,

    description TEXT DEFAULT NULL,

    category VARCHAR(100) DEFAULT NULL,

    price INT UNSIGNED NOT NULL DEFAULT 0,

    unit VARCHAR(50)
        NOT NULL DEFAULT 'pcs',

    image VARCHAR(255) DEFAULT NULL,
    
    tag VARCHAR(40) DEFAULT NULL,

    is_available TINYINT(1)
        NOT NULL DEFAULT 1,

    sort_order INT NOT NULL DEFAULT 0,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_products_store (store_id),

    KEY idx_products_store_available (
        store_id,
        is_available
    ),

    KEY idx_products_category (category),

    KEY idx_products_sort (
        store_id,
        sort_order
    ),

    CONSTRAINT fk_products_store
        FOREIGN KEY (store_id)
        REFERENCES stores (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 4. BUYERS
-- Data pembeli yang diambil dari checkout
-- Pembeli tidak perlu login
-- =========================================================

CREATE TABLE IF NOT EXISTS buyers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(100) NOT NULL,

    phone VARCHAR(25) NOT NULL,

    delivery_address VARCHAR(255) DEFAULT NULL,

    delivery_maps_url VARCHAR(500) DEFAULT NULL,

    delivery_latitude DECIMAL(10, 7) DEFAULT NULL,

    delivery_longitude DECIMAL(10, 7) DEFAULT NULL,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_buyers_phone (phone),

    KEY idx_buyers_name (name)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 5. ORDERS
-- Data utama pesanan pembeli
-- =========================================================

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    store_id BIGINT UNSIGNED NOT NULL,

    buyer_id BIGINT UNSIGNED DEFAULT NULL,

    order_number VARCHAR(50) NOT NULL,

    buyer_name VARCHAR(100) NOT NULL,

    buyer_phone VARCHAR(25) NOT NULL,

    delivery_method ENUM('delivery', 'pickup')
        NOT NULL DEFAULT 'delivery',

    delivery_address VARCHAR(255) DEFAULT NULL,

    delivery_fee INT UNSIGNED
        NOT NULL DEFAULT 0,

    subtotal INT UNSIGNED
        NOT NULL DEFAULT 0,

    total INT UNSIGNED
        NOT NULL DEFAULT 0,

    status ENUM(
        'pending',
        'confirmed',
        'processing',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    buyer_token_hash CHAR(64) NOT NULL,

    delivery_maps_url VARCHAR(500) DEFAULT NULL,

    delivery_latitude DECIMAL(10, 7) DEFAULT NULL,

    delivery_longitude DECIMAL(10, 7) DEFAULT NULL,

    notes TEXT DEFAULT NULL,

    confirmed_at DATETIME DEFAULT NULL,

    completed_at DATETIME DEFAULT NULL,

    cancelled_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_orders_order_number (order_number),

    UNIQUE KEY uq_orders_buyer_token_hash (
        buyer_token_hash
    ),

    KEY idx_orders_store_created (
        store_id,
        created_at
    ),

    KEY idx_orders_store_status (
        store_id,
        status
    ),

    KEY idx_orders_buyer_created (
        buyer_id,
        created_at
    ),

    KEY idx_orders_buyer_phone (
        buyer_phone
    ),

    KEY idx_orders_status_created (
        status,
        created_at
    ),

    CONSTRAINT fk_orders_store
        FOREIGN KEY (store_id)
        REFERENCES stores (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_orders_buyer
        FOREIGN KEY (buyer_id)
        REFERENCES buyers (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 6. ORDER ITEMS
-- Rincian produk setiap pesanan
-- Nama dan harga disimpan sebagai snapshot
-- agar riwayat pesanan tidak berubah saat produk diedit
-- =========================================================

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    order_id BIGINT UNSIGNED NOT NULL,

    product_id BIGINT UNSIGNED DEFAULT NULL,

    product_name VARCHAR(150) NOT NULL,

    quantity INT UNSIGNED NOT NULL DEFAULT 1,

    price INT UNSIGNED NOT NULL DEFAULT 0,

    subtotal INT UNSIGNED NOT NULL DEFAULT 0,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_order_items_order (order_id),

    KEY idx_order_items_product (product_id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- END OF PESAN AJAH DATABASE SCHEMA
-- =========================================================