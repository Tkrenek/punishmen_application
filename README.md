# Pokutovník — Evidence pokut

PHP 8.2 / Nette 3.2 webová aplikace pro správu firemního systému pokut.

## Funkce
- Výpis pokut s filtry (uživatel, typ, zaplaceno/nezaplaceno, datum od–do)
- Přidávání nových pokut, označování jako zaplacené
- Správa uživatelů (číselník iniciál, soft delete)
- Správa typů pokut (číselník)
- Fond — evidenece výdajů z fondu a bonusů do kasy
- Dashboard se statistikami a Chart.js grafy

## Požadavky
- PHP 8.2+
- MySQL 8 / MariaDB 10.6+
- Composer

## Instalace

```bash
# 1. Nainstalovat závislosti
composer install

# 2. Připravit konfiguraci DB
cp config/local.neon.example config/local.neon
# Upravit config/local.neon - vyplnit DB credentials

# 3. Vytvořit databázi a schéma
mysql -u root -p < db/migrations/001_initial_schema.sql
mysql -u root -p punishment_app < db/migrations/002_seed_users.sql
mysql -u root -p punishment_app < db/migrations/003_seed_penalty_types.sql

# 4. Importovat historická data
php db/import/import_source.php

# 5. Spustit vývojový server
php -S localhost:8080 -t public/
```

Aplikace poté běží na http://localhost:8080

## Struktura projektu

```
app/              — PHP aplikace (Nette)
  Bootstrap.php   — inicializace frameworku
  Model/          — Repository třídy + StatisticsModel
  Presenters/     — Nette presentery (MVC controllers)
  templates/      — Latte šablony
config/           — NEON konfigurace
db/
  migrations/     — SQL migrace
  import/         — import script pro source.txt
public/           — web root (index.php, CSS, JS)
.copilot/         — instrukce pro AI agenty
.docs/            — dokumentace
tests/            — Nette Tester testy
```

## Dokumentace

Viz `.docs/`:
- [Architektura](.docs/architecture.md)
- [Databázové schéma](.docs/database.md)
- [Import dat](.docs/import.md)
- [Agenti](.docs/agents.md)
- [API / Presentery](.docs/api.md)

## GitHub

Repozitář: https://github.com/Tkrenek/punishmen_application
