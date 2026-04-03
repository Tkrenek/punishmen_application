# Funkčnosti aplikace — Přehled

## 1. Dashboard (`/`)

Přehledová stránka se souhrnnou statistikou a grafy.

### Zobrazované údaje
| Ukazatel | Popis |
|---|---|
| Zůstatek fondu | Zaplacené pokuty + bonusy – výdaje |
| Celková suma pokut | Suma všech pokut (zaplacených i nezaplacených) |
| Top 5 hříšníků | Bar chart (Chart.js) — uživatelé s nejvíce pokutami |
| Pokuty po měsících | Line chart (Chart.js) — vývoj počtu pokut v čase |

---

## 2. Evidence pokut (`/penalty`)

Hlavní modul aplikace pro správu pokut.

### Filtry
- **Uživatel** — filtr podle iniciál
- **Typ pokuty** — filtr podle kategorie
- **Stav zaplacení** — Vše / Nezaplaceno / Zaplaceno
- **Datum od–do** — rozsah data udělení pokuty

> Filtry se **zachovávají** při všech akcích (označit zaplaceno/nezaplaceno). Po kliknutí na akci se stránka vrátí se stejnými filtry.

### Výpis
- **Stránkování**: 25 pokut na stránku
- **Celková suma** vyfiltrovaných pokut (zobrazena pod tabulkou)
- **Počet nezaplacených** pokut ve výpisu (zobrazeno vedle tlačítka hromadné platby)

### Akce na jednotlivé pokutě
| Akce | Popis |
|---|---|
| ✓ Označit zaplaceno | `actionMarkPaid` — zachová aktivní filtry |
| ↺ Označit nezaplaceno | `actionMarkUnpaid` — zachová aktivní filtry |
| 🗑 Smazat | `actionDelete` — trvalé smazání |

### Hromadné označení jako zaplacené
- Tlačítko **„Zaplatit vyfiltrované (N)"** v záhlaví tabulky
- Označí jako zaplacené **všechny pokuty odpovídající aktuálním filtrům** (nejen aktuální stránku)
- `actionMarkAllPaid` — zpracuje celý výběr najednou

### Formulář přidání/editace pokuty
| Pole | Typ | Povinné |
|---|---|---|
| Uživatel | select | ✓ |
| Typ pokuty | select | ✓ |
| Datum | date | ✓ |
| Částka (Kč) | number ≥ 0.01 | ✓ |
| Zaplaceno | checkbox | — |
| Poznámka | textarea (max 500) | — |

---

## 3. Správa uživatelů (`/user`)

### Výpis
- Zobrazuje všechny uživatele (aktivní i neaktivní)
- U každého uživatele zobrazuje počet pokut

### Editace uživatele
| Pole | Popis |
|---|---|
| Iniciály | Unikátní zkratka (max 10 znaků) |
| Jméno | Celé jméno (volitelné) |

### Aktivace / deaktivace
- **Deaktivace** (soft delete) — `actionDelete` — uživatel zůstane v DB i pokutách, jen se označí jako neaktivní, nevybírá se do nových pokut
- **Obnovení** — `actionRestore` — vrátí uživatele do aktivního stavu
- Obě akce dostupné přímo z editační stránky uživatele
- Parametr `?back=edit` zajistí redirect zpět na editaci (ne na seznam)

---

## 4. Správa typů pokut (`/penalty-type`)

### Výpis
- Všechny typy pokut (aktivní i neaktivní)

### Formulář
| Pole | Popis |
|---|---|
| Název | Popis typu pokuty |
| Výchozí částka (Kč) | Předvyplní se při tvorbě nové pokuty |
| Aktivní | Checkbox — soft delete |

Aktuální typy pokut (po sloučení duplicit):
1. chybějící výkazy vzhledem k docházce při kontrole
2. neaktualizovaná planning tabulka při kontrole
3. nepřeplánování úkolů pro česání do deadlinu
4. neomluvený pozdní příchod
5. nepřeplánovaný červený sloupec

---

## 5. Fond (`/fund`)

Evidence finančních toků fondů pokut.

### Typy transakcí
| Typ | Popis | Vliv na zůstatek |
|---|---|---|
| `withdrawal` | Výdaj z fondu | − |
| `bonus` | Bonus / příjem do fondu | + |

### Zůstatek fondu
```
Zůstatek = Σ zaplacené pokuty + Σ bonusy − Σ výdaje
```

### Formulář
| Pole | Popis |
|---|---|
| Datum | Datum transakce |
| Částka | Kladná hodnota v Kč |
| Popis | Textový popis |
| Typ | withdrawal / bonus |
| Uživatel | Volitelné přiřazení k uživateli |

---

## 6. Import dat (`/db/import/import_source.php`)

Jednorázový import historických dat ze souboru `source.txt`.

### Importovaná data (produkční stav)
- 14 uživatelů
- 5 typů pokut (po manuálním sloučení duplicit)
- 631 pokut
- 22 fund transakcí

---

## 7. Technické vlastnosti

| Vlastnost | Hodnota |
|---|---|
| PHP | 8.2 |
| Framework | Nette 3.2.9 |
| Databáze | MySQL 8 / MariaDB 10.6+ |
| Frontend | Bootstrap 5, Chart.js |
| Šablony | Latte 3 (strict types) |
| Testování | Nette Tester 2.6, 12 testů |
| Bez autentizace | Interní nástroj, bez přihlášení |