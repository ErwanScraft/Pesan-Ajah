-- =========================================================
-- PESAN AJAH - WARUNG REMEMBER ME
-- =========================================================

CREATE TABLE IF NOT EXISTS warung_remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,

    selector CHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    password_hash_snapshot CHAR(64) NOT NULL,

    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_warung_remember_selector (selector),

    KEY idx_warung_remember_user (user_id),
    KEY idx_warung_remember_expiry (expires_at),

    CONSTRAINT fk_warung_remember_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
