---
description: >
  Tester agent pro Punishment Application.
  Zodpovídá za unit a integrační testy (Nette Tester).
tools:
  - create_file
  - replace_string_in_file
  - run_in_terminal
  - get_errors
---

# Tester Agent — Nette Tester

## Stack
- Nette Tester 2.5
- PHPUnit (volitelně pro přechod)
- Test databáze: oddělená MySQL DB `punishment_app_test`

## Struktura testů

```
tests/
  bootstrap.php              — inicializace test prostředí
  Unit/
    UserRepositoryTest.php
    PenaltyRepositoryTest.php
    StatisticsModelTest.php
    ImportParserTest.php     — unit testy parsování source.txt
  Integration/
    PenaltyFlowTest.php      — přidání pokuty, označení zaplaceno
    ImportIntegrationTest.php — kompletní import flow
```

## Co testovat

### Unit testy — ImportParser
- Parsování datumu bez roku (sekvenční rok)
- Parsování částky: `20,00 Kč` → 20.0
- Parsování závorek `(320,00 Kč)` → withdrawal
- `- Kč` → 20.0
- Mezery v číslech `1 600,00 Kč` → 1600.0
- Zatížení `is_paid`: `1` → true, prázdné → false

### Unit testy — StatisticsModel
- `getTotalBalance()`: suma pokut + bonusů - výdajů
- `getTopOffenders()`: správné řazení
- `getUnpaidByUser()`: filtruje zaplacené

### Integrační testy
- Přidat uživatele → najít v DB → soft delete → nenalezen v `findActive()`
- Přidat pokutu → nalezena ve výpisu → označit zaplaceno → `is_paid = 1`
- Import souboru → správný počet záznamů

## Spuštění testů
```bash
vendor/bin/tester tests/ -C
```

## Pravidla
- Každý test je izolovaný (DB transactions rollback po testu)
- Mock objekty pro DB v unit testech
- Žádné testy s hardcoded credentials (brát z env nebo test config)
- Test naming: `testCo_kdyz_expected()`
