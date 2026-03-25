-- Punishment Application - Initial Schema
-- MySQL 8 / MariaDB 10.6+
-- Charset: utf8mb4 (podpora češtiny a emoji)

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS punishment_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE punishment_app;

-- -------------------------------------------------------
-- Uživatelé (číselník)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    initials   VARCHAR(10)  NOT NULL UNIQUE COMMENT 'Iniciály uživatele (TP, JU, ...)',
    name       VARCHAR(100) NULL     COMMENT 'Celé jméno (volitelné)',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Soft delete - 0 = neaktivní',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Typy pokut (číselník)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS penalty_types (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200)  NOT NULL COMMENT 'Název typu pokuty',
    default_amount DECIMAL(10,2) NOT NULL DEFAULT 20.00 COMMENT 'Výchozí výše pokuty v Kč',
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Pokuty
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS penalties (
    id              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED  NOT NULL,
    penalty_type_id INT UNSIGNED  NOT NULL,
    amount          DECIMAL(10,2) NOT NULL DEFAULT 20.00 COMMENT 'Výše pokuty v Kč',
    penalty_date    DATE          NOT NULL COMMENT 'Datum udělení pokuty',
    is_paid         TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '0 = nezaplaceno, 1 = zaplaceno',
    note            TEXT          NULL     COMMENT 'Poznámka (originální text z evidence)',
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_penalties_user
        FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_penalties_type
        FOREIGN KEY (penalty_type_id) REFERENCES penalty_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_penalties_user_id    ON penalties(user_id);
CREATE INDEX idx_penalties_date       ON penalties(penalty_date);
CREATE INDEX idx_penalties_is_paid    ON penalties(is_paid);
CREATE INDEX idx_penalties_type_id    ON penalties(penalty_type_id);

-- -------------------------------------------------------
-- Fond transakce (speciální záznamy - výdaje a bonusy)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS fund_transactions (
    id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED  NULL COMMENT 'NULL = kolektivní transakce bez konkrétního uživatele',
    entry_date       DATE          NOT NULL COMMENT 'Datum transakce',
    amount           DECIMAL(10,2) NOT NULL COMMENT 'Částka v Kč (vždy kladná)',
    description      TEXT          NOT NULL COMMENT 'Popis transakce',
    transaction_type ENUM('withdrawal', 'bonus') NOT NULL
        COMMENT 'withdrawal = výdaj z fondu, bonus = příjem do fondu',
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fund_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_fund_date ON fund_transactions(entry_date);
CREATE INDEX idx_fund_type ON fund_transactions(transaction_type);
