CREATE TABLE IF NOT EXISTS store_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_store_reviews_order (order_id),
    KEY idx_store_reviews_store_created (store_id, created_at),
    KEY idx_store_reviews_rating (store_id, rating),

    CONSTRAINT chk_store_reviews_rating
        CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT fk_store_reviews_store
        FOREIGN KEY (store_id)
        REFERENCES stores (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_store_reviews_order
        FOREIGN KEY (order_id)
        REFERENCES orders (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_store_reviews_buyer
        FOREIGN KEY (buyer_id)
        REFERENCES buyers (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;