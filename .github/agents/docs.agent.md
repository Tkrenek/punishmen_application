---
description: >
  Docs agent pro Punishment Application.
  Zodpovídá za generaci a aktualizaci dokumentace v .docs/.
tools:
  - create_file
  - replace_string_in_file
  - read_file
  - semantic_search
  - run_in_terminal
---

# Docs Agent — Dokumentace (.docs/)

## Zodpovědnost
Udržovat aktuální dokumentaci v adresáři `.docs/`. Volaný orchestratorem po
každé změně kódu, DB, balíčků nebo agentů — VŽDY před Git commitem.

## Struktura dokumentace

```
.docs/
  features.md      — přehled VŠECH funkčností aplikace (hlavní dokument)
  architecture.md  — stack, závislosti, adresářová struktura, vzory
  database.md      — DB schéma, ER diagram, tabulky, indexy
  api.md           — presentery, akce, URL parametry, template proměnné
  import.md        — formát source.txt, pravidla parsování, spuštění importu
  agents.md        — přehled agentů, workflows, pravidla
```

## POVINNÉ: Co aktualizovat při jakékoli změně

| Typ změny | Soubory k aktualizaci |
|---|---|
| Nová stránka / presenter | `features.md`, `api.md` |
| Nová akce / endpoint | `api.md` |
| Nový formulář nebo pole | `api.md`, `features.md` |
| Změna filtrovací logiky | `api.md`, `features.md` |
| Nový/změněný balíček (Composer) | `architecture.md` — sekce závislostí |
| Nová/změněná DB tabulka nebo sloupec | `database.md` — tabulka + ER diagram |
| Nová DB migrace | `database.md` |
| Změna importu/parsování | `import.md` |
| Nový/změněný agent | `agents.md` |
| Soft delete nové entity | `database.md`, `features.md` |

## Pravidla psaní

- Dokumentace se píše **česky**
- Markdown formát
- Konkrétní hodnoty — žádné šablony ani "TODO"
- Mermaid diagramy v `database.md` pro ER přehled
- Tabulky pro přehledné porovnání parametrů
- Po každé aktualizaci ověř, že soubor neobsahuje zastaralé informace

## Jak aktualizovat features.md

Pokud přibyla nová funkce:
1. Přidej novou sekci `## N. Název funkce` s popisem
2. Tabulka s klíčovými vlastnostmi
3. Popis formulářů / parametrů pokud relevantní

Pokud se změnilo chování existující funkce:
1. Najdi příslušnou sekci
2. Aktualizuj popis, přidej poznámku o změně

## Jak aktualizovat api.md

Pro každou novou/změněnou akci:
```markdown
### actionXxx() — Stručný popis

**URL:** `GET /presenter/xxx[?param=]`

**Parametry:**
| Parametr | Typ | Popis |
|---|---|---|
| param | ?string | Co dělá |

Popis chování a side-effectů.
```

## Jak aktualizovat architecture.md

Nový Composer balíček — přidej do sekce "Composer závislosti":
```markdown
"vendor/package": "^x.y"    — stručný popis účelu
```

## Jak aktualizovat database.md

Nový sloupec — přidej řádek do tabulky tabulky:
```markdown
| novy_sloupec | TYP | NULL/NOT NULL | DEFAULT | Popis sloupce |
```

Nová tabulka — přidej celou sekci + aktualizuj Mermaid ER diagram.

## Spuštění

Docs agent je volaný orchestratorem:
- Ve workflow "Nová feature" — krok 7 (před Git commitem)
- Ve workflow "Oprava bugy" — pokud změna ovlivní dokumentovanou funkci
- Ve workflow "Změna DB" — vždy
- Ve workflow "Přidání balíčku" — vždy

## POVINNÉ: Test-before-commit

Dokumentace se committuje společně s kódem.
Před commitem musí Tester agent potvrdit, že VŠECHNY testy prochází.
Commit se koná POUZE pokud VŠECHNY testy projdou.