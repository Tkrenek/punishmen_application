# Architektura aplikace — Pokutovník

## Technologický stack

| Vrstva | Technologie | Verze |
|---|---|---|
| Jazyk | PHP | 8.2 |
| Framework | Nette | 3.2.9 |
| Databáze | MySQL / MariaDB | 8 / 10.6+ |
| Frontend | Bootstrap | 5 |
| Grafy | Chart.js | CDN |
| Šablony | Latte | 3.0 |
| Ladění | Tracy | 2.10 |
| Testy | Nette Tester | 2.6 |
| Statická analýza | PHPStan | 1.10 |

## Composer závislosti

### Produkční
```json
"nette/application": "^3.2"    — MVP framework, Presenter, Router
"nette/bootstrap":   "^3.2"    — inicializace DI kontejneru
"nette/database":    "^3.2"    — Database Explorer (ActiveRow)
"nette/di":          "^3.1"    — Dependency Injection kontejner
"nette/forms":       "^3.2"    — formuláře s validací a CSRF ochranou
"nette/http":        "^3.2"    — HTTP request/response abstrakce
"nette/routing":     "^3.1"    — URL routing
"nette/security":    "^3.2"    — bezpečnost (nutné pro Nette DI)
"nette/utils":       "^4.0"    — utility třídy
"latte/latte":       "^3.0"    — šablonovací engine
"tracy/tracy":       "^2.10"   — debugger a profiler
```

### Dev
```json
"nette/tester":      "^2.5"    — testovací framework
"phpstan/phpstan":   "^1.10"   — statická analýza
```

## Adresářová struktura

```
punishment_application/
├── app/                          — PHP aplikace (autoload: App\)
│   ├── Bootstrap.php             — inicializace DI kontejneru
│   ├── Model/                    — datová vrstva
│   │   ├── UserRepository.php
│   │   ├── PenaltyRepository.php
│   │   ├── PenaltyTypeRepository.php
│   │   ├── FundTransactionRepository.php
│   │   └── StatisticsModel.php
│   ├── Presenters/               — řadiče (MVP pattern)
│   │   ├── BasePresenter.php
│   │   ├── DashboardPresenter.php
│   │   ├── PenaltyPresenter.php
│   │   ├── PenaltyTypePresenter.php
│   │   ├── UserPresenter.php
│   │   ├── FundPresenter.php
│   │   └── ErrorPresenter.php
│   └── templates/                — Latte šablony
│       ├── @layout.latte         — hlavní layout
│       ├── Dashboard/
│       ├── Penalty/
│       ├── PenaltyType/
│       ├── User/
│       ├── Fund/
│       └── Error/
├── config/
│   ├── common.neon               — sdílená konfigurace + DB + services
│   ├── local.neon                — lokální overrides (v .gitignore)
│   └── local.neon.example        — šablona pro local.neon
├── db/
│   ├── migrations/               — SQL migrace
│   │   ├── 001_initial_schema.sql
│   │   ├── 002_seed_users.sql
│   │   └── 003_seed_penalty_types.sql
│   └── import/
│       └── import_source.php     — jednorázový import source.txt
├── public/                       — web root
│   └── index.php                 — vstupní bod aplikace
├── tests/                        — Nette Tester testy (autoload: Tests\)
├── .github/agents/               — instrukce pro AI agenty
├── .docs/                        — tato dokumentace
├── log/                          — Tracy logy (v .gitignore)
├── temp/                         — cache a temp soubory (v .gitignore)
└── vendor/                       — Composer závislosti (v .gitignore)
```

## Architekturní vzory

### MVP (Model-View-Presenter)
Nette implementuje MVP pattern:
- **Model** — třídy v `app/Model/` — Repository pattern + StatisticsModel
- **View** — Latte šablony v `app/templates/`
- **Presenter** — třídy v `app/Presenters/` — zpracovávají požadavky, volají model, předávají data do šablon

### Repository pattern
Každá entita má svůj Repository:
```php
class PenaltyRepository {
    public function __construct(private readonly Explorer $database) {}
    public function findFiltered(array $filters): Selection { ... }
    public function markAllPaidFiltered(array $filters): int { ... }
}
```
Repository třídy jsou registrovány v DI kontejneru (`config/common.neon`) a injectovány do presenterů přes konstruktor.

### Dependency Injection
Celá aplikace používá Nette DI kontejner. Konfigurace v `config/common.neon`:
```neon
services:
    - App\Model\UserRepository
    - App\Model\PenaltyRepository
    - App\Model\PenaltyTypeRepository
    - App\Model\FundTransactionRepository
    - App\Model\StatisticsModel
```

### Latte strict types
Latte 3 automaticky injektuje proměnnou `$user` (Nette\Security\User) do každé šablony. **Nikdy nepoužívat `$user` jako vlastní proměnnou šablony** — používat `$editedUser`, `$u` apod.

### Soft delete
Tabulky `users` a `penalty_types` používají soft delete přes sloupec `is_active`. Fyzicky se záznamy nemažou — uchovávají se historická data pokut.

## Konfigurace prostředí

```neon
# config/common.neon — databázové připojení
database:
    dsn: 'mysql:host=127.0.0.1;dbname=punishment_app;charset=utf8mb4'
    user: root
    password: ''
    options:
        lazy: true
```

Testovací prostředí používá `punishment_app_test` databázi (nastaveno v `tests/bootstrap.php`).

## Bezpečnost

- CSRF ochrana na všech formulářích (`$form->addProtection(...)`)
- Prepared statements přes Nette Database Explorer (ochrana před SQL injection)
- Bez autentizace — interní nástroj v uzavřené síti
- XSS ochrana — Latte automaticky escapuje výstup