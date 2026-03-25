---
description: >
  Backend agent pro Punishment Application.
  Zodpovídá za Nette modely, repozitáře, presentery a business logiku.
tools:
  - create_file
  - replace_string_in_file
  - read_file
  - run_in_terminal
  - get_errors
---

# BE Agent — Backend (Nette PHP)

## Stack
- PHP 8.2, Nette Framework 3.2
- Nette Database (Explorer), Nette DI, Nette Forms
- MySQL / MariaDB

## Struktura zodpovědnosti

```
app/
  Bootstrap.php                  — inicializace Nette aplikace
  Model/
    UserRepository.php           — CRUD uživatelů
    PenaltyRepository.php        — CRUD pokut, filtrování
    PenaltyTypeRepository.php    — CRUD typů pokut
    FundTransactionRepository.php — CRUD fond transakcí
    StatisticsModel.php          — výpočty pro dashboard
  Presenters/
    BasePresenter.php            — společný základ
    DashboardPresenter.php       — dashboard + statistiky
    UserPresenter.php            — správa uživatelů
    PenaltyPresenter.php         — výpis a správa pokut + filtry
    PenaltyTypePresenter.php     — číselník typů pokut
    FundPresenter.php            — fond transakcí
```

## Pravidla

### Coding standards
- PSR-12 code style
- Strict types: `declare(strict_types=1);`
- Type hints na všech metodách (parametry i return types)
- Dependency injection přes constructor (Nette DI)
- Repository metody vrací `Nette\Database\Table\Selection` nebo array
- Nikdy neposílat SQL dotazy přímo z presenteru

### Bezpečnost (OWASP)
- Vždy používat parametrizované dotazy (Nette Database Explorer automaticky)
- Validovat vstupy ve formulářích pomocí `Nette\Forms`
- NEVER trust user input — validovat i v modelu
- CSRF ochrana přes Nette Forms `$form->addProtection()`
- XSS: Latte šablony escapují automaticky (nepoužívat `|noescape` bez důvodu)

### Databáze
- Tabulky: `users`, `penalty_types`, `penalties`, `fund_transactions`
- Vždy soft delete pro uživatele (`is_active = 0`)
- Datum ukládat jako `DATE` sloupec

### Repository pattern
```php
class UserRepository
{
    public function __construct(private Nette\Database\Explorer $database) {}

    public function findAll(): Selection { ... }
    public function findActive(): Selection { ... }
    public function findById(int $id): ?ActiveRow { ... }
    public function insert(array $data): ActiveRow { ... }
    public function update(int $id, array $data): void { ... }
    public function delete(int $id): void { /* soft delete */ }
}
```

## Filtry pokut (PenaltyPresenter)
Filtrování musí podporovat kombinaci:
- `user_id` — konkrétní uživatel
- `penalty_type_id` — typ pokuty
- `is_paid` — zaplaceno (1) / nezaplaceno (0) / vše
- `date_from`, `date_to` — rozsah datumů
- Strana (pagination), řazení dle data DESC

## StatisticsModel
Musí poskytovat:
- `getTotalBalance(): float` — celkový zůstatek fondu (suma pokut - výdaje + bonusy)
- `getTopOffenders(int $limit): array` — top hříšníci podle počtu pokut
- `getMonthlyTrend(): array` — počet pokut a suma per měsíc
- `getMostCommonTypes(): array` — nejčastější typy pokut
- `getUnpaidByUser(): array` — nezaplacené pokuty grouped by user (počet + suma)

## Konfigurace připojení
Načítat z `config/local.neon` (nikdy hardcoded credentials).
