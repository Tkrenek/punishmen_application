# Import dat — source.txt

## Spuštění

```bash
cd c:\Users\tomas.krenek\apps\punishment_application
php db/import/import_source.php
```

**Předpoklady:**
1. DB migrace jsou aplikovány (tabulky existují)
2. `config/local.neon` obsahuje správné DB credentials
3. `source.txt` je v kořeni projektu

## Formát source.txt

Tab-separated soubor, 5 sloupců:
```
datum    iniciála    částka    zaplaceno    poznámka
```

Příklady:
```
12.9    TP    20,00 Kč    1    chybějící výkazy vzhledem k docházce při kontrole
25.11   HK    (320,00 Kč) 1   Republika na přípitek
27.03   MH    - Kč        1   neomluvený pozdní příchod
8.9     -     785,00 Kč   1   počáteční stav
```

## Pravidla parsování

### Datum
- Formát: `D.M` nebo `DD.MM` (bez roku)
- Rok začíná **2023** (první záznamy = září 2023)
- Rok se inkrementuje o +1, pokud aktuální měsíc je o více než 3 nižší než předchozí měsíc (= přechod do nového roku)

### Částka
| Vstup | Výsledek |
|---|---|
| `20,00 Kč` | 20.00 |
| `(320,00 Kč)` | 320.00 (výdaj z fondu) |
| `- Kč` nebo `-` | 20.00 (výchozí) |
| `1 600,00 Kč` | 1600.00 (mezera = oddělovač tisíců) |

### Iniciály
| Iniciála | Výsledek |
|---|---|
| `TP`, `JU`, ... | standardní uživatel |
| `-` | fund_transaction (bonus), bez uživatele |
| `TEAM` | fund_transaction, bez uživatele |
| prázdné | fund_transaction, bez uživatele |

### Zaplaceno
- `1` → `is_paid = 1`
- prázdné nebo cokoliv jiného → `is_paid = 0`

### Typ záznamu (rozhodování)

| Podmínka | Výsledek |
|---|---|
| Závorky v částce `(...)` | `fund_transactions`, type=`withdrawal` |
| Iniciála `-`, `TEAM`, prázdná | `fund_transactions`, type=`bonus` |
| Poznámka = standardní typ pokuty | `penalties` |
| Nestandardní částka / popis | `fund_transactions`, type=`bonus` |

### Normalizace typů pokut
| Originální text | Normalizovaný |
|---|---|
| neomluvená účast na daily | neomluvená absence |
| neomluvený pozdní příchod | neomluvená absence |
| nepřeplánovaný červený sloupec | nepřeplánovaný červený sloupec při kontrole |

## Výstup

```
✓ Připojeno k databázi
  + Nový uživatel: TP
  ...
✓ Import dokončen:
  Pokuty:          523
  Fond transakce:  48
  Přeskočeno:      1
```

## Opakovaný import

Skript neobsahuje ochranu proti duplikátům — spouštět pouze jednou.
Pro resetování dat smazat záznamy:
```sql
TRUNCATE TABLE penalties;
TRUNCATE TABLE fund_transactions;
```
