-- LipePool — MySQL 8.0.16+ (InnoDB, utf8mb4)
-- Migration inicial. Os horários liberados permanecem alinhados ao fluxo legado;
-- a grade e duração do serviço devem ser confirmadas pelo responsável.

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(24) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL DEFAULT 'customer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin')),
    CONSTRAINT chk_users_active CHECK (is_active IN (0, 1)),
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Endereço/CPF são isolados do registro de autenticação e podem ser coletados
-- somente quando a operação realmente precisar deles.
CREATE TABLE customer_profiles (
    user_id BIGINT UNSIGNED NOT NULL,
    cpf CHAR(11) NULL,
    postal_code CHAR(8) NULL,
    district VARCHAR(100) NULL,
    street VARCHAR(180) NULL,
    street_number VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT uq_customer_profiles_cpf UNIQUE (cpf),
    CONSTRAINT fk_customer_profiles_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_services_name UNIQUE (name),
    CONSTRAINT chk_services_duration CHECK (duration_minutes IS NULL OR duration_minutes > 0),
    INDEX idx_services_active_name (is_active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes VARCHAR(1000) NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'requested',
    -- Em MySQL, valores NULL repetidos são permitidos em índice UNIQUE.
    -- Assim, reservas solicitadas ocupam o horário; canceladas/concluídas
    -- preservam histórico e liberam-no. A unicidade é aplicada pelo banco,
    -- mesmo em requisições concorrentes.
    active_slot TINYINT GENERATED ALWAYS AS (
        CASE WHEN status = 'requested' THEN 1 ELSE NULL END
    ) STORED,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_appointments_customer FOREIGN KEY (customer_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id)
        REFERENCES services (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_appointments_status CHECK (status IN ('requested', 'completed', 'cancelled')),
    CONSTRAINT chk_appointments_time CHECK (appointment_time IN ('08:00:00', '10:00:00', '13:00:00', '15:00:00', '17:00:00', '19:00:00')),
    CONSTRAINT uq_appointments_active_slot UNIQUE (appointment_date, appointment_time, active_slot),
    INDEX idx_appointments_customer_date (customer_id, appointment_date, appointment_time),
    INDEX idx_appointments_status_date (status, appointment_date, appointment_time),
    INDEX idx_appointments_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
