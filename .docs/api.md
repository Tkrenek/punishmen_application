# URL Endpoints — přehled

Aplikace nepoužívá REST API — všechny endpointy jsou Nette presenter akce (HTTP GET/POST).

## Dashboard

| Metoda | URL | Akce |
|---|---|---|
| GET | `/` | Dashboard se statistikami |
| GET | `/dashboard` | = `/` |

## Uživatelé

| Metoda | URL | Akce |
|---|---|---|
| GET | `/user` | Seznam uživatelů |
| GET | `/user/add` | Formulář pro přidání uživatele |
| POST | `/user/add` | Uložení nového uživatele |
| GET | `/user/edit/<id>` | Formulář pro editaci |
| POST | `/user/edit/<id>` | Uložení změn |
| POST | `/user/delete/<id>` | Soft delete uživatele |
| POST | `/user/restore/<id>` | Obnovení smazaného uživatele |

## Pokuty

| Metoda | URL | Akce |
|---|---|---|
| GET | `/penalty` | Seznam pokut (se filtry) |
| GET | `/penalty?user_id=X` | Pokuty konkrétního uživatele |
| GET | `/penalty?penalty_type_id=X` | Pokuty daného typu |
| GET | `/penalty?is_paid=0` | Nezaplacené pokuty |
| GET | `/penalty?date_from=YYYY-MM-DD` | Pokuty od data |
| GET | `/penalty?date_to=YYYY-MM-DD` | Pokuty do data |
| GET | `/penalty?page=2` | Stránkování (25 záznamů/strana) |
| GET | `/penalty/add` | Formulář pro přidání pokuty |
| POST | `/penalty/add` | Uložení nové pokuty |
| POST | `/penalty/markPaid/<id>` | Označit jako zaplaceno |
| POST | `/penalty/markUnpaid/<id>` | Označit jako nezaplaceno |
| POST | `/penalty/delete/<id>` | Smazání pokuty |

### Kombinované filtry
Filtry lze kombinovat:
```
/penalty?user_id=3&is_paid=0&date_from=2024-01-01
```

## Typy pokut

| Metoda | URL | Akce |
|---|---|---|
| GET | `/penaltytype` | Seznam typů pokut |
| GET | `/penaltytype/add` | Formulář pro přidání |
| POST | `/penaltytype/add` | Uložení nového typu |
| GET | `/penaltytype/edit/<id>` | Formulář pro editaci |
| POST | `/penaltytype/edit/<id>` | Uložení změn |
| POST | `/penaltytype/delete/<id>` | Soft delete typu |

## Fond (fund_transactions)

| Metoda | URL | Akce |
|---|---|---|
| GET | `/fund` | Přehled transakcí, zůstatek, formulář |
| POST | `/fund/add` | Přidání transakce (výdaj nebo bonus) |
| POST | `/fund/delete/<id>` | Smazání transakce |

## Výchozí typy pokut (seed data)

| ID | Název | Výchozí částka |
|---|---|---|
| 1 | neomluvená absence | 20.00 Kč |
| 2 | nepřeplánovaný červený sloupec při kontrole | 20.00 Kč |
| 3 | nestandardní pokuta | 20.00 Kč |
| 4 | bonus do kasy | 0.00 Kč |

## Chybové stránky

| HTTP kód | URL |
|---|---|
| 404 | automaticky (Nette ErrorPresenter) |
| 500 | automaticky |

## Flash zprávy

Flash zprávy jsou zobrazeny v layoutu po každou action POST operaci:
- `success` — zelená (BS5 `alert-success`)
- `error` — červená (BS5 `alert-danger`)
- `warning` — žlutá (BS5 `alert-warning`)
