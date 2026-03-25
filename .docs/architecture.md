# Architektura — Pokutovník

## Stack

| Vrstva | Technologie |
|---|---|
| Backend | PHP 8.2, Nette Framework 3.2 |
| Databáze | MySQL 8 / MariaDB 10.6+ |
| Šablony | Latte 3 |
| Frontend | Bootstrap 5.3, Chart.js 4, Bootstrap Icons |
| DI kontejner | Nette DI |
| Formuláře | Nette Forms (s CSRF ochranou) |
| Testování | Nette Tester 2.5 |

## Architekturní vzory

### Repository Pattern
Každá databázová entita má vlastní Repository třídu v `app/Model/`.
Presentery přistupují k DB výhradně přes Repository — žádné raw SQL v presenterech.

### MVC (Model-View-Presenter)
Nette implementuje MVP (Model-View-Presenter) vzor:
- **Model** = Repository třídy + StatisticsModel
- **View** = Latte šablony v `app/templates/`
- **Presenter** = třídy v `app/Presenters/` (zodpovídají za HTTP request/response)

### Dependency Injection
Veškeré závislosti jsou injectovány přes konstruktor — Nette DI kontejner je generuje automaticky dle `config/common.neon`.

## Adresářová struktura

```
punishment_application/
├── app/
│   ├── Bootstrap.php              ← inicializace Nette kontejneru
│   ├── Model/
│   │   ├── UserRepository.php
│   │   ├── PenaltyRepository.php
│   │   ├── PenaltyTypeRepository.php
│   │   ├── FundTransactionRepository.php
│   │   └── StatisticsModel.php
│   ├── Presenters/
│   │   ├── BasePresenter.php
│   │   ├── DashboardPresenter.php
│   │   ├── UserPresenter.php
│   │   ├── PenaltyPresenter.php
│   │   ├── PenaltyTypePresenter.php
│   │   ├── FundPresenter.php
│   │   └── ErrorPresenter.php
│   └── templates/
│       ├── @layout.latte
│       ├── Dashboard/
│       ├── User/
│       ├── Penalty/
│       ├── PenaltyType/
│       ├── Fund/
│       └── Error/
├── config/
│   ├── common.neon                ← sdílená konfigurace
│   ├── local.neon                 ← lokální konfigurace (gitignored)
│   └── local.neon.example         ← šablona pro local.neon
├── db/
│   ├── migrations/                ← SQL migrace
│   └── import/                    ← import script
├── public/
│   ├── index.php                  ← web root
│   ├── css/app.css
│   └── js/app.js
├── tests/                         ← Nette Tester testy
├── .copilot/                      ← instrukce pro AI agenty
├── .docs/                         ← dokumentace
└── composer.json
```

## Bezpečnost

- **SQL Injection**: Nette Database Explorer používá parametrizované dotazy automaticky
- **XSS**: Latte escapuje výstup ve výchozím nastavení
- **CSRF**: Nette Forms mají `addProtection()` na všech formulářích
- **Credentials**: DB heslo pouze v `config/local.neon` (gitignored)
- **Soft delete**: Uživatelé se nikdy fyzicky nesmažou

## Tok požadavku

```
HTTP Request
  → public/index.php
  → Bootstrap.php (DI kontejner)
  → Router (URL → Presenter:action)
  → Presenter::action*()
  → Model/Repository (DB dotazy)
  → Presenter::renderDefault() (předání dat šabloně)
  → Latte šablona (@layout.latte + content blok)
  → HTTP Response
```
