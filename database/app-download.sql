-- =========================================================
-- PESAN AJAH
-- App Download Statistics & Reviews
-- =========================================================

CREATE TABLE IF NOT EXISTS app_download_stats (
    id TINYINT UNSIGNED NOT NULL,
    total_downloads BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


INSERT INTO app_download_stats (
    id,
    total_downloads
) VALUES (
    1,
    0
)
ON DUPLICATE KEY UPDATE
    id = VALUES(id);


CREATE TABLE IF NOT EXISTS app_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_app_reviews_created_at (
        created_at,
        id
    ),

    INDEX idx_app_reviews_rating (
        rating
    ),

    CONSTRAINT chk_app_reviews_rating
        CHECK (rating BETWEEN 1 AND 5)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
