---
description: >
  Import agent pro Punishment Application.
  Zodpovídá za parsování source.txt a import dat do MySQL databáze.
tools:
  - create_file
  - replace_string_in_file
  - run_in_terminal
  - read_file
---

# Importer Agent — Import source.txt

## Zodpovědnost
Naparsovat `source.txt` a naplnit databázi:
- Tabulka `users` — unikátní iniciály z dat
- Tabulka `penalty_types` — unikátní typy pokut
- Tabulka `penalties` — standardní pokuty (20 Kč nebo explicitní částka)
- Tabulka `fund_transactions` — speciální záznamy

## Formát source.txt
Tab-separated, 5 sloupců:
```
datum | iniciála | částka | zaplaceno | poznámka/typ pokuty
```

Příklady:
```
8.9     -       785,00 Kč   1   počáteční stav
12.9    TP      20,00 Kč    1   chybějící výkazy...
25.11   HK      (320,00 Kč) 1   Republika na přípitek
27.03   MH      - Kč        1   neomluvený pozdní příchod
```

## Pravidla parsování

### Datum
- Formát: `D.M` nebo `DD.MM` (bez roku)
- Rok začíná **2023** (září)
- Rok inkrementovat `+1` pokud nový měsíc < předchozí měsíc - 3
- Výsledek: `DATE` ve formátu `YYYY-MM-DD`

### Částka
- `XX,XX Kč` → float (nahradit `,` za `.`, odebrat ` Kč`)
- `(XX,XX Kč)` → speciální transakce (výdaj z fondu), částka je absolutní hodnota
- `- Kč` → **20.00** (výchozí hodnota pokuty)
- `X XXX,XX Kč` → mezera je oddělovač tisíců (odebrat před parsováním)

### Iniciála
- Standardní uživatel → reference na `users.id`
- `-` iniciála (řádek 2, počáteční stav) → speciální bonus do kasy
- `TEAM` → fund_transaction bez user_id (je to výdaj, závorka)
- Prázdná iniciála s závorkou (Bowling 11.12) → fund_transaction bez user_id

### Záznam je `fund_transaction` pokud:
1. Částka je v závorkách `(...)` → `transaction_type = 'withdrawal'`
2. Částka je nestandardní (≠ 20 Kč) A poznámka není typ pokuty → `transaction_type = 'bonus'`
3. Iniciála je `-` nebo `TEAM` nebo prázdná

### Standardní typy pokut (poznámka → penalty_type_id)
```
"chybějící výkazy..."           → typ 1
"nepřeplánovaný červený..."     → typ 2
"neaktualizovaná planning..."   → typ 3
"neomluvená účast na daily"     → typ 4 (= neomluvená absence)
"neomluvený pozdní příchod"     → typ 4 (= neomluvená absence)
```

### Zaplaceno
- `1` → `is_paid = 1`
- prázdné / cokoliv jiného → `is_paid = 0`

### Přeskočit
- Záhlaví (řádek 1): `face | Iniciála | Částka | ...`

## Script: `db/import/import_source.php`

```php
#!/usr/bin/env php
<?php
// Spuštění: php db/import/import_source.php
// Vyžaduje: config/local.neon s DB credentials
```

### Logika scriptu:
1. Načíst `config/local.neon` pro DB připojení
2. Otevřít `source.txt`, číst řádek po řádku
3. Pro každý řádek: parsovat, validovat, rozhodnout typ záznamu
4. Naplnit `users` a `penalty_types` při prvním výskytu (INSERT IGNORE)
5. Vkládat `penalties` nebo `fund_transactions`
6. Na konci vypsat statistiku: X pokut, Y fund transakcí, Z uživatelů

## Spuštění
```bash
cd c:\Users\tomas.krenek\apps\punishment_application
php db/import/import_source.php
```
