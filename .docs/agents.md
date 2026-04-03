# AI Agenti — Přehled

Projekt používá AI agenty (GitHub Copilot) pro automatizovaný vývoj.
Instrukce jsou v `.github/agents/`.

---

## Přehled agentů

| Agent | Soubor | Zodpovídá za |
|---|---|---|
| **Orchestrator** | orchestrator.agent.md | Koordinace, rozkládá úkoly na podúkoly |
| **BE** | be.agent.md | Nette modely, repozitáře, presentery, business logika |
| **FE** | fe.agent.md | Latte šablony, Bootstrap 5 UI, formuláře, Chart.js |
| **DB** | db.agent.md | MySQL migrace, schéma, Nette DI konfigurace |
| **Importer** | importer.agent.md | Parsování a import source.txt do DB |
| **Code Reviewer** | code-reviewer.agent.md | Review kódu, OWASP Top 10 bezpečnost |
| **Tester** | tester.agent.md | Nette Tester unit + integrační testy |
| **Git** | git.agent.md | Commit, push na GitHub, správa větví |
| **Docs** | docs.agent.md | Generace a aktualizace dokumentace v .docs/ |
| **App Runner** | app-runner.agent.md | Spuštění aplikace na localhost:8080 |

---

## Workflow: Nová feature

```
1. Orchestrator: rozlož feature na podúkoly
2. → DB Agent: nové migrace (pokud potřeba)
3. → BE Agent: modely, repozitáře, presentery
4. → FE Agent: šablony, formuláře, UI
5. → Code Reviewer: review všech změn
6. → Tester: spustit VŠECHNY testy
7. → Docs Agent: aktualizovat .docs/features.md + api.md (případně architecture.md nebo database.md)
8. → Git Agent: commit + push (POUZE pokud všechny testy prošly)
```

## Workflow: Oprava bugy

```
1. Orchestrator: analyzuj bug, urči postižený subsystém
2. → BE/FE/DB Agent: oprava
3. → Code Reviewer: review opravy
4. → Tester: spustit VŠECHNY testy
5. → Git Agent: commit s popisem bugfixu
```

## Workflow: Změna DB schématu

```
1. → DB Agent: nová migrace + aktualizace common.neon services
2. → BE Agent: aktualizace Repository tříd
3. → Tester: testy
4. → Docs Agent: aktualizovat .docs/database.md
5. → Git Agent: commit
```

## Workflow: Přidání balíčku (Composer)

```
1. → BE Agent: composer require + implementace
2. → Code Reviewer: review
3. → Tester: testy
4. → Docs Agent: aktualizovat .docs/architecture.md (sekce závislostí)
5. → Git Agent: commit
```

---

## Povinné pravidlo: Test-before-commit

**KAŽDÝ commit MUSÍ být předcházen:**
1. Tester agent spustí `C:\php82\php.exe vendor/bin/tester tests/ -C`
2. Všechny testy musí projít (aktuálně 12 testů)
3. Pokud jakýkoli test selže → commit se nekoná, zodpovědný agent opraví kód
4. Cycle oprava → testy → dokud neprojdou všechny

---

## Povinné pravidlo: Docs-with-changes

**Po každé změně která přidává/mění/ruší funkčnost:**

| Typ změny | Co aktualizovat |
|---|---|
| Nová funkce / stránka | `.docs/features.md`, `.docs/api.md` |
| Nový/změněný balíček | `.docs/architecture.md` |
| Změna DB schématu | `.docs/database.md` |
| Změna import logiky | `.docs/import.md` |
| Nový/změněný agent | `.docs/agents.md` |

Dokumentaci aktualizuje **Docs agent** — vždy zavolán orchestratorem před Git agentem.

---

## Spuštění aplikace

```bash
# Windows
C:\php82\php.exe -S localhost:8080 -t public

# Ověření
curl http://localhost:8080
```

App Runner agent ověří odpověď a vrátí stav.

---

## Repozitář

GitHub: https://github.com/Tkrenek/punishmen_application
Hlavní větev: `main`