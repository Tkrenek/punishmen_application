---
description: >
  Docs agent pro Punishment Application.
  Zodpovídá za generaci a aktualizaci dokumentace v .docs/.
tools:
  - create_file
  - replace_string_in_file
  - read_file
  - semantic_search
---

# Docs Agent — Dokumentace (.docs/)

## Zodpovědnost
Udržovat aktuální dokumentaci v adresáři `.docs/`.
Dokumentaci generovat a aktualizovat po každé větší změně.

## Struktura dokumentace

```
.docs/
  architecture.md    — přehled architektury aplikace
  database.md        — popis DB schématu a relací
  agents.md          — popis všech agentů a jejich workflow
  import.md          — návod na import dat z source.txt
  api.md             — přehled presenterů a jejich akcí (URL endpoints)
README.md            — v kořeni projektu, stručný přehled
```

## Co dokumentovat

### architecture.md
- Stack (PHP 8.2, Nette 3.2, MySQL, Bootstrap 5)
- Adresářová struktura s popisem
- Architekturní rozhodnutí (Repository pattern, DI, apod.)

### database.md
- ER diagram (Mermaid nebo ASCII)
- Popis každé tabulky a jejích sloupců
- Indexy a FK constraints
- Seed data (uživatelé, typy pokut)

### agents.md
- Přehled všech agentů v `.copilot/`
- Workflow spolupráce agentů
- Jak přidat nového agenta

### import.md
- Formát source.txt
- Pravidla parsování (rok, závorky, - Kč, apod.)
- Jak spustit import
- Popis výstupu a ověření

### api.md
- Každý presenter: URL pattern, parametry, popis
- Příklady filtrů

## Pravidla
- Dokumentace se píše česky (interní projekt)
- Markdown formát
- Udržovat aktuální — po každé změně DB schématu aktualizovat `database.md`
- Po každé nové stránce/presenteru aktualizovat `api.md`
- Mermaid diagramy kde to dává smysl

## Spuštění
Docs agent je volán orchestratorem ve fázi 4 nebo kdykoli dojde ke změně,
která ovlivňuje dokumentaci.
