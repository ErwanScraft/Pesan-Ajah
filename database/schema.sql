-- =========================================================
-- PESAN AJAH
-- Database Schema
--
-- Database : pesan_ajah
-- Engine   : InnoDB
-- Charset  : utf8mb4
--
-- Catatan:
-- Pilih database Pesan Ajah terlebih dahulu di phpMyAdmin.
-- Script ini tidak membuat database karena pada cPanel,
-- pengguna database biasanya tidak memiliki izin CREATE DATABASE.
-- =========================================================


-- =========================================================
-- TABLE: users
-- Pengguna admin dan pemilik warung
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    username VARCHAR(50) NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    role ENUM('admin', 'warung')
        NOT NULL DEFAULT 'warung',

    is_active TINYINT(1)
        NOT NULL DEFAULT 1,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_users_username (username),

    KEY idx_users_role_active (role, is_active)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- TABLE: stores
-- Data warung yang terdaftar
-- =========================================================

CREATE TABLE IF NOT EXISTS stores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(80) NOT NULL,

    slug VARCHAR(80) NOT NULL,

    owner VARCHAR(80) NOT NULL,

    phone VARCHAR(25) DEFAULT NULL,

    address VARCHAR(180) NOT NULL,

    description VARCHAR(500) DEFAULT NULL,

    is_open TINYINT(1)
        NOT NULL DEFAULT 0,

    free_delivery TINYINT(1)
        NOT NULL DEFAULT 0,

    is_active TINYINT(1)
        NOT NULL DEFAULT 1,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_stores_user_id (user_id),

    UNIQUE KEY uq_stores_slug (slug),

    KEY idx_stores_active (is_active),

    KEY idx_stores_name (name),

    CONSTRAINT fk_stores_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- TABLE: products
-- Produk milik setiap warung
-- =========================================================

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    store_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(120) NOT NULL,

    description TEXT DEFAULT NULL,

    category VARCHAR(80) DEFAULT NULL,

    price DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    unit VARCHAR(30)
        NOT NULL DEFAULT 'pcs',

    image VARCHAR(255) DEFAULT NULL,

    is_available TINYINT(1)
        NOT NULL DEFAULT 1,

    created_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_products_store (store_id),

    KEY idx_products_available (is_available),

    KEY idx_products_store_available (
        store_id,
        is_available
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
-- TABLE: orders
-- Data pesanan pembeli
-- =========================================================

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    store_id BIGINT UNSIGNED NOT NULL,

    order_number VARCHAR(40) NOT NULL,

    buyer_name VARCHAR(100) NOT NULL,

    buyer_phone VARCHAR(25) NOT NULL,

    delivery_method ENUM('delivery', 'pickup')
        NOT NULL,

    delivery_address VARCHAR(255) DEFAULT NULL,

    delivery_fee DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    subtotal DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    total DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    status ENUM(
        'pending',
        'confirmed',
        'processing',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    buyer_token_hash CHAR(64)
        DEFAULT NULL,

    delivery_maps_url VARCHAR(500)
        DEFAULT NULL,

    delivery_latitude DECIMAL(10, 7)
        DEFAULT NULL,

    delivery_longitude DECIMAL(10, 7)
        DEFAULT NULL,

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

    KEY idx_orders_store_status (
        store_id,
        status
    ),

    KEY idx_orders_created_at (
        created_at
    ),

    KEY idx_orders_store_created (
        store_id,
        created_at
    ),

    CONSTRAINT fk_orders_store
        FOREIGN KEY (store_id)
        REFERENCES stores (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- TABLE: order_items
-- Rincian produk dalam setiap pesanan
-- =========================================================

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    order_id BIGINT UNSIGNED NOT NULL,

    product_id BIGINT UNSIGNED DEFAULT NULL,

    product_name VARCHAR(120) NOT NULL,

    quantity INT UNSIGNED
        NOT NULL DEFAULT 1,

    price DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

    subtotal DECIMAL(12, 2)
        NOT NULL DEFAULT 0.00,

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


-- =========================================================
-- END OF SCHEMA
-- =========================================================