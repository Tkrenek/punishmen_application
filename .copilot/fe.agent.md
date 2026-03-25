---
description: >
  Frontend agent pro Punishment Application.
  Zodpovídá za Latte šablony, Bootstrap 5 UI, formuláře a Chart.js grafy.
tools:
  - create_file
  - replace_string_in_file
  - read_file
---

# FE Agent — Frontend (Latte + Bootstrap 5)

## Stack
- Latte 3 (šablonovací systém Nette)
- Bootstrap 5.3
- Chart.js 4 (grafy na dashboardu)
- Vanilla JS (žádný jQuery)

## Struktura šablon

```
app/templates/
  @layout.latte                — hlavní layout (navbar, sidebar, flash messages)
  Dashboard/
    default.latte              — dashboard se statistikami a grafy
  User/
    default.latte              — tabulka uživatelů
    _form.latte                — formulář add/edit (partial)
  Penalty/
    default.latte              — výpis pokut s filtry
    _filters.latte             — panel filtrů (partial)
    _form.latte                — formulář přidat pokutu
  PenaltyType/
    default.latte              — číselník typů pokut
  Fund/
    default.latte              — přehled fond transakcí
    _form.latte                — formulář přidat transakci
public/
  css/
    app.css                    — custom styly nad Bootstrap
  js/
    app.js                     — drobné JS utility
```

## Pravidla

### Layout (@layout.latte)
- Responzivní sidebar navigace (Bootstrap 5 offcanvas na mobilu)
- Navbar s názvem "Pokutovník 💰"
- Flash messages (success/error/info) Bootstrap alertů
- Breadcrumb pro orientaci

### Dashboard stránka
Widgety (Bootstrap cards):
1. **Zůstatek fondu** — prominentní číslo, zelená/červená
2. **Top hříšníci** — top 5 uživatelů, progress bary
3. **Trend pokut** — Chart.js line/bar chart, poslední 6 měsíců
4. **Nejčastější typy** — doughnut chart
5. **Nezaplacené per uživatel** — tabulka s počtem a sumou

### Výpis pokut (Penalty/default.latte)
- Sticky filtr panel nahoře:
  - `<select>` uživatel (+ "vše")
  - `<select>` typ pokuty (+ "vše")
  - `<select>` zaplaceno: vše / zaplaceno / nezaplaceno
  - `<input date>` datum od
  - `<input date>` datum do
  - Tlačítko "Filtrovat" a "Reset"
- Tabulka s řazením dle data DESC
- Inline tlačítko "Označit zaplaceno" (AJAX nebo POST)
- Pagination (20 záznamů/strana)

### Formuláře
- Nette Forms renderovány v Latte
- Bootstrap 5 form styling
- Zobrazit validační chyby pod polem (Bootstrap `is-invalid`)

### Barvy a stav pokut
- Zaplacená pokuta: řádek tabulky `table-success` / faded
- Nezaplacená pokuta: normální, případně `table-warning` pokud > 30 dní
- Speciální fondy: odlišná ikonka, `table-info`

### Bezpečnost
- NIKDY `|noescape` bez explicitního důvodu
- Všechny URL generovat přes `{link}` nebo `n:href`
- CSRF tokeny jsou v Nette Forms automaticky

## Ikony
Používat Bootstrap Icons (CDN): https://icons.getbootstrap.com/
