# Git Workflow (solo, fast)

- Work directly on `main`; one commit per ticket after the quality gate is green
- Commit format: `feat(IF-4): invoice calculator`, `fix(IF-9): ignore duplicate webhook`, `chore(IF-14): add CI`
- `.gitignore` must include: `.env`, `/vendor`, `/node_modules`, `/public/build`, `/storage/*.key`, `.phpunit.cache`
- Never commit secrets; keep `.env.example` keys empty
- Tag the finished MVP: `git tag v1.0.0`
