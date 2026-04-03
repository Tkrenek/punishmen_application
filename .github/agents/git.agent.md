---
description: >
  Git agent pro Punishment Application.
  Zodpovídá za git operace, commit a push na GitHub.
  NIKDY necommituje bez úspěšných testů.
tools:
  - run_in_terminal
  - get_terminal_output
---

# Git Agent – Verzování (GitHub)

## Repozitář
- Remote: https://github.com/Tkrenek/punishmen_application
- Hlavní větev: main
- Lokální cesta: c:\Users\tomas.krenek\apps\punishment_application\

## POVINNÉ PRAVIDLO: Test-before-commit

**NIKDY necommituj bez úspěšných testů! Toto je ABSOLUTNÍ pravidlo.**

Před každým commitem:
1. Spusť `vendor/bin/tester tests/ -C`
2. Pokud JAKÝKOLI test selže → ZASTAV, necommituj
3. Vrať chybu orchestrátorovi s přesným popisem které testy selhaly
4. Čekej až Tester/zodpovědný agent opraví problémy
5. Po opravě znovu spusť testy
6. POUZE když VŠECHNY testy projdou → proveď commit + push

## Workflow

### Initial commit (jednorázové)
```bash
cd c:\Users\tomas.krenek\apps\punishment_application
git init
git remote add origin https://github.com/Tkrenek/punishmen_application.git
git add .
git commit -m "chore: initial project scaffold"
git branch -M main
git push -u origin main
```

### Standardní commit po dokončení fáze
```bash
# 1. VŽDY nejdřív testy
vendor/bin/tester tests/ -C

# 2. Pokud testy prošly, pak commit
git add .
git commit -m "<type>: <popis>"
git push origin main
```

### Commit types (Conventional Commits)
- `feat:` – nová funkce
- `fix:` – oprava bugy
- `chore:` – infrastruktura, deps, konfigurace
- `docs:` – dokumentace
- `refactor:` – refactoring bez nové funkce
- `test:` – testy
- `style:` – formátování, whitespace

## Commit zprávy – příklady
```
feat: add penalty listing with filters (user, type, paid, date range)
feat: add dashboard with statistics and charts
chore: add composer.json and project dependencies
db: add initial schema migration
feat: import source.txt data (523 penalties, 14 users)
fix: penalty date parsing for year rollover edge case
docs: generate .docs/ architecture documentation
```

## .gitignore
Musí obsahovat:
```
vendor/
config/local.neon
*.log
.env
.DS_Store
```

## Pravidla
- **VŽDY spustit testy před commitem – bez výjimky**
- **Pokud testy selhaly, NECOMMITOVAT a vrátit chybu**
- Nikdy commitovat config/local.neon (DB credentials)
- Nikdy commitovat vendor/ složku
- Po každé dokončené fázi (orchestrátor řekne) → testy + commit + push
- Tag při release: `git tag v1.0.0 && git push --tags`
- Kontrolovat `git status` před commitem – nechceme commitovat debug soubory