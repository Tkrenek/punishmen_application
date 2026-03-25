---
description: >
  Databázový agent pro Punishment Application.
  Zodpovídá za MySQL schéma, migrace a DB konfiguraci v Nette.
tools:
  - create_file
  - replace_string_in_file
  - run_in_terminal
---

# DB Agent — Databáze (MySQL / MariaDB)

## Stack
- MySQL 8 / MariaDB 10.6+
- Nette Database Explorer (wrapper nad PDO)
- Migrace: ruční SQL soubory v `db/migrations/`

## Schéma

### Tabulka `users`
```sql
CREATE TABLE users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  initials   VARCHAR(10) NOT NULL UNIQUE,
  name       VARCHAR(100) NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabulka `penalty_types`
```sql
CREATE TABLE penalty_types (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(200) NOT NULL,
  default_amount DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabulka `penalties`
```sql
CREATE TABLE penalties (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  penalty_type_id INT UNSIGNED NOT NULL,
  amount          DECIMAL(10,2) NOT NULL DEFAULT 20.00,
  penalty_date    DATE NOT NULL,
  is_paid         TINYINT(1) NOT NULL DEFAULT 0,
  note            TEXT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (penalty_type_id) REFERENCES penalty_types(id)
);
```

### Tabulka `fund_transactions`
```sql
CREATE TABLE fund_transactions (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NULL,
  entry_date       DATE NOT NULL,
  amount           DECIMAL(10,2) NOT NULL,
  description      TEXT NOT NULL,
  transaction_type ENUM('withdrawal', 'bonus') NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Migrace
Soubory: `db/migrations/NNN_popis.sql`
- `001_initial_schema.sql` — vytvoření všech tabulek
- `002_seed_users.sql` — naplnění číselníku uživatelů
- `003_seed_penalty_types.sql` — naplnění číselníku typů pokut

## Nette konfigurace

### config/common.neon
```neon
database:
    dsn: '%database.dsn%'
    user: '%database.user%'
    password: '%database.password%'
    options:
        lazy: true
```

### config/local.neon (gitignored)
```neon
parameters:
    database:
        dsn: 'mysql:host=localhost;dbname=punishment_app;charset=utf8mb4'
        user: root
        password: ''
```

## Pravidla
- Vždy `charset=utf8mb4` (podpora emoji a češtiny)
- INDEX na `penalties.user_id`, `penalties.penalty_date`, `penalties.is_paid`
- Soft delete pro uživatele (`is_active`), nikdy fyzický DELETE
- `fund_transactions.user_id` je nullable (kolektivní záznamy bez uživatele)
- Nikdy vkládat credentials do verzovaných souborů

## Spuštění migrací
```bash
mysql -u root -p punishment_app < db/migrations/001_initial_schema.sql
mysql -u root -p punishment_app < db/migrations/002_seed_users.sql
mysql -u root -p punishment_app < db/migrations/003_seed_penalty_types.sql
```
