# Import dat ze source.txt

## Přehled

Jednorázový import historických dat z tabulkového souboru `source.txt` do MySQL databáze.
Skript: `db/import/import_source.php`

## Formát source.txt

Tab-separovaný textový soubor. Každý řádek = jeden záznam.

**Sloupce (tab-oddělené):**
```
datum    iniciály    částka    zaplaceno    poznámka/typ_pokuty
```

**Příklady:**
```
1.1.2024    TP    20 Kč    1    neomluvený pozdní příchod
15.3.2024   JU    20 Kč    0    chybějící výkazy
1.2.2024    TEAM  (500 Kč) 1    vánoční večírek
```

## Pravidla parsování

| Podmínka | Akce |
|---|---|
| Částka obsahuje `- Kč` | Skip — přeskočit řádek |
| Iniciály jsou `-` nebo prázdné | Skip — přeskočit řádek |
| Iniciály jsou `TEAM` | `user_id = NULL` (kolektivní transakce) |
| Částka 20 Kč | Pokuta (`penalties`) |
| Jiná částka bez závorek | Fund bonus (`fund_transactions`, type=bonus) |
| Částka v závorce `(X Kč)` | Fund withdrawal (`fund_transactions`, type=withdrawal) |
| Neznámý uživatel | Automaticky se vytvoří v tabulce `users` |
| Neznámý typ pokuty | Automaticky se vytvoří v `penalty_types` |

## Zpracování data

Formát vstupního data: `D.M.YYYY` (např. `1.3.2024`)
Převod: `date('Y-m-d', strtotime(...))` nebo manuální parsing

## Spuštění importu

```bash
# Windows
C:\php82\php.exe db/import/import_source.php

# Linux/Mac
php db/import/import_source.php
```

Skript předpokládá:
- Databáze `punishment_app` existuje a migrace jsou aplikovány
- `source.txt` je v kořeni projektu nebo v `db/import/`
- DB credentials v `config/local.neon`

## Výsledek importu (produkční data)

| Entita | Počet |
|---|---|
| Uživatelé | 14 |
| Typy pokut (před sloučením) | 7 |
| Typy pokut (po sloučení duplicit) | 5 |
| Pokuty | 631 |
| Fund transakce | 22 |

## Sloučení duplicitních typů pokut

Po importu byly manuálně sloučeny duplicitní typy přes SQL:
- `neomluvená účast na daily` (6 pokut) → přemigrováno do `neomluvený pozdní příchod`
- `nepřeplánovaný červený sloupec při kontrole` (98 pokut) → přemigrováno do `nepřeplánovaný červený sloupec`

```sql
UPDATE penalties SET penalty_type_id = 6 WHERE penalty_type_id = 4;
UPDATE penalty_types SET is_active = 0 WHERE id = 4;

UPDATE penalties SET penalty_type_id = 7 WHERE penalty_type_id = 2;
UPDATE penalty_types SET is_active = 0 WHERE id = 2;
```

## Opakovaný import

Před opakovaným importem je nutné smazat existující data:
```sql
DELETE FROM penalties;
DELETE FROM fund_transactions;
DELETE FROM users;
DELETE FROM penalty_types;
ALTER TABLE penalties AUTO_INCREMENT = 1;
-- atd.
```

**Pozor:** Import je destruktivní operace — vždy zálohuj DB před spuštěním.