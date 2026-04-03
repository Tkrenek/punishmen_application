# API — Presentery a akce

Pokutovník používá Nette MVP — URL jsou mapovány na Presenter:Action.
Router: `/<presenter>/<action>[/<id>]`

---

## DashboardPresenter — `/`

| Akce | URL | Metoda | Popis |
|---|---|---|---|
| renderDefault | `/` nebo `/dashboard` | GET | Přehled statistik a grafů |

### renderDefault()
**Template proměnné:**
- `$totalBalance` — float, zůstatek fondu
- `$totalPenaltiesSum` — float, celková suma všech pokut
- `$topOffenders` — array, top 5 uživatelů s nejvíce pokutami
- `$penaltiesByMonth` — array, data pro Chart.js (pokuty po měsících)

---

## PenaltyPresenter — `/penalty`

### renderDefault() — Výpis pokut

**URL:** `GET /penalty[?user_id=&penalty_type_id=&is_paid=&date_from=&date_to=&page=]`

**Parametry:**
| Parametr | Typ | Popis |
|---|---|---|
| user_id | ?string | Filtr uživatele (prázdný = vše) |
| penalty_type_id | ?string | Filtr typu pokuty |
| is_paid | ?string | `''` = vše, `'0'` = nezaplaceno, `'1'` = zaplaceno |
| date_from | ?string | Datum od (Y-m-d) |
| date_to | ?string | Datum do (Y-m-d) |
| page | int | Číslo stránky, default 1 |

**Template proměnné:**
- `$penalties` — Selection (stránkovaná)
- `$filters` — array aktívních filtrů
- `$totalAmount` — float, suma vyfiltrovaných pokut
- `$unpaidCount` — int, počet nezaplacených ve výběru
- `$paginator` — Paginator (25 pokut/stránka) — navigace: první «« / poslední »» + předchozí/další + čísla stránek ±2
- `$users` — seznam uživatelů pro select
- `$penaltyTypes` — seznam typů pro select

---

### actionMarkPaid() — Označit jako zaplaceno

**URL:** `GET /penalty/mark-paid/<id>[?user_id=&penalty_type_id=&is_paid=&date_from=&date_to=]`

**Parametry:**
| Parametr | Typ | Popis |
|---|---|---|
| id | int | ID pokuty (required) |
| user_id | ?string | Aktuální filtr — zachová se při redirectu |
| penalty_type_id | ?string | Aktuální filtr |
| is_paid | ?string | Aktuální filtr |
| date_from | ?string | Aktuální filtr |
| date_to | ?string | Aktuální filtr |

Označí pokutu jako zaplacenu a přesměruje zpět na výpis **se zachovanými filtry**.

---

### actionMarkUnpaid() — Označit jako nezaplaceno

**URL:** `GET /penalty/mark-unpaid/<id>[?filtry]`

Stejné parametry jako `actionMarkPaid`. Označí pokutu jako nezaplacenou, zachová filtry.

---

### actionMarkAllPaid() — Hromadné označení jako zaplacené

**URL:** `GET /penalty/mark-all-paid[?user_id=&penalty_type_id=&is_paid=&date_from=&date_to=]`

Označí **všechny pokuty odpovídající filtrům** jako zaplacené (ne jen aktuální stránku).
Vrátí flash message s počtem označených pokut.

---

### actionDelete() — Smazat pokutu

**URL:** `GET /penalty/delete/<id>`

Trvalé smazání záznamu pokuty z DB.

---

### renderAdd() — Přidat pokutu

**URL:** `GET /penalty/add`

Formulář pro přidání nové pokuty.

---

### renderEdit() — Editovat pokutu

**URL:** `GET /penalty/edit/<id>`

Formulář pro úpravu existující pokuty.

---

## UserPresenter — `/user`

### renderDefault()

**URL:** `GET /user`

Výpis všech uživatelů (aktivních i neaktivních) s počty pokut.

---

### renderEdit()

**URL:** `GET /user/edit/<id>`

Editace uživatele. Template proměnná `$editedUser` (ne `$user` — konflikt s Latte Security\User).

---

### actionDelete() — Deaktivace uživatele (soft delete)

**URL:** `GET /user/delete/<id>[?back=edit]`

| Parametr | Popis |
|---|---|
| id | ID uživatele |
| back | `edit` → redirect zpět na editaci; jinak → seznam uživatelů |

Nastaví `is_active = 0`. Historické pokuty zůstávají zachovány.

---

### actionRestore() — Obnovení uživatele

**URL:** `GET /user/restore/<id>[?back=edit]`

Nastaví `is_active = 1`. Stejná logika `?back` jako u `actionDelete`.

---

## PenaltyTypePresenter — `/penalty-type`

| Akce | URL | Popis |
|---|---|---|
| renderDefault | `GET /penalty-type` | Výpis typů pokut |
| renderEdit | `GET /penalty-type/edit/<id>` | Editace typu |
| actionDelete | `GET /penalty-type/delete/<id>` | Soft delete (is_active=0) |

Formulář: `name` (required), `default_amount` (required, decimal), `is_active` (checkbox)

---

## FundPresenter — `/fund`

| Akce | URL | Popis |
|---|---|---|
| renderDefault | `GET /fund` | Výpis transakcí + celkový zůstatek/výdaje/bonusy |
| renderAdd | `GET /fund/add` | Přidání transakce |
| renderEdit | `GET /fund/edit/<id>` | Editace transakce |
| actionDelete | `GET /fund/delete/<id>` | Smazání transakce |

**Template proměnné renderDefault:**
- `$transactions` — seznam fund_transactions
- `$totalWithdrawals` — float, celkové výdaje
- `$totalBonuses` — float, celkové bonusy
- `$balance` — float, výsledný zůstatek fondu

**Formulář:**
- `entry_date` — date, required
- `amount` — decimal ≥ 0.01, required
- `description` — text, required
- `transaction_type` — select: withdrawal/bonus, required
- `user_id` — select uživatelů, volitelné

---

## CSRF ochrana

Všechny formuláře jsou chráněny CSRF tokenem přes `$form->addProtection()`.