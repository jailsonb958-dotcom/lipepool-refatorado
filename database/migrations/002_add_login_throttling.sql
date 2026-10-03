-- Limita tentativas por hash de e-mail + IP. Não armazena o e-mail nem o IP em claro.
CREATE TABLE login_throttles (
    throttle_key CHAR(64) NOT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    locked_until DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (throttle_key),
    INDEX idx_login_throttles_locked_until (locked_until),
    INDEX idx_login_throttles_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
