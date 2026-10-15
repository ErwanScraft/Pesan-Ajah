CREATE DATABASE IF NOT EXISTS pesan_ajah
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pesan_ajah;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(50) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    role ENUM('admin', 'warung') NOT NULL DEFAULT 'warung',

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id BIGINT UNSIGNED NOT NULL UNIQUE,

    name VARCHAR(80) NOT NULL,

    slug VARCHAR(80) NOT NULL UNIQUE,

    owner VARCHAR(80) NOT NULL,

    phone VARCHAR(25) DEFAULT NULL,

    address VARCHAR(180) NOT NULL,

    description VARCHAR(500) DEFAULT NULL,

    is_open TINYINT(1) NOT NULL DEFAULT 0,

    free_delivery TINYINT(1) NOT NULL DEFAULT 0,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_stores_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_stores_active (is_active),

    INDEX idx_stores_name (name)
);

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    store_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(120) NOT NULL,

    description TEXT DEFAULT NULL,

    category VARCHAR(80) DEFAULT NULL,

    price DECIMAL(12, 2) NOT NULL DEFAULT 0,

    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',

    image VARCHAR(255) DEFAULT NULL,

    is_available TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_store
        FOREIGN KEY (store_id)
        REFERENCES stores(id)
        ON DELETE CASCADE,

    INDEX idx_products_store (store_id),

    INDEX idx_products_available (is_available)
);

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    store_id BIGINT UNSIGNED NOT NULL,

    order_number VARCHAR(40) NOT NULL UNIQUE,

    buyer_name VARCHAR(100) NOT NULL,

    buyer_phone VARCHAR(25) NOT NULL,

    delivery_method ENUM('delivery', 'pickup') NOT NULL,

    delivery_address VARCHAR(255) DEFAULT NULL,

    delivery_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,

    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,

    total DECIMAL(12, 2) NOT NULL DEFAULT 0,

    status ENUM(
        'pending',
        'confirmed',
        'processing',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',
    
    buyer_token_hash CHAR(64) DEFAULT NULL,
    
    delivery_maps_url VARCHAR(500) DEFAULT NULL,
    
    delivery_latitude DECIMAL(10, 7) DEFAULT NULL,
    
    delivery_longitude DECIMAL(10, 7) DEFAULT NULL,
    
    uq_orders_buyer_token_hash (buyer_token_hash);

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_store
        FOREIGN KEY (store_id)
        REFERENCES stores(id)
        ON DELETE CASCADE,

    INDEX idx_orders_store_status (store_id, status),

    INDEX idx_orders_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    order_id BIGINT UNSIGNED NOT NULL,

    product_id BIGINT UNSIGNED DEFAULT NULL,

    product_name VARCHAR(120) NOT NULL,

    quantity INT UNSIGNED NOT NULL DEFAULT 1,

    price DECIMAL(12, 2) NOT NULL DEFAULT 0,

    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE SET NULL,

    INDEX idx_order_items_order (order_id)
);