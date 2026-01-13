# Agentic Coding Guidelines

| Item | Details |
| --- | --- |
| Supported CLI agents | Codex CLI, Aider, Cursor, other shell-based agents |
| Instruction files used | `AGENTS.md`, `PROJECT_STATUS.md`, `README.md`, `composer.json`, `package.json`, `phpunit.xml`, `railway/*.sh`, `Dockerfile` |
| Verification commands | `composer test`, `php artisan test`, `npm run build`, `php artisan migrate`, `php artisan migrate:fresh --seed` |
| Tooling/integrations discovered | Laravel 12, Composer, PHPUnit, Laravel Pint (dev dependency, no script), Vite, TailwindCSS, Railway init scripts, Dockerfile builder, Nixpacks config; CI workflows not configured |

## 1) Purpose and principles

This document defines how a CLI coding agent should work in this repository safely, predictably, and with minimal risk to production deployments. The repo is a Laravel 12 application with a single-user admin UI, a knowledge base workflow, and a Railway deployment path that relies on Dockerfile and Railway init scripts. The agent must prioritize correctness and avoid changing behavior unless explicitly requested. The goal is to make small, verifiable changes that keep local development and Railway production stable.

Key principles for this repo:

- Prefer small, incremental diffs that are easy to review and revert. If a change requires a wide refactor, plan first and get explicit approval.
- Keep application logic stable. Documentation and configuration changes are allowed, but any behavioral change must be explicitly requested and scoped.
- Use repository-native scripts and commands. Do not invent scripts that do not exist in `composer.json` or `package.json`.
- Always anchor work in existing documentation, especially `PROJECT_STATUS.md`, which is the authoritative snapshot of current functionality and workflow.
- Respect environment differences: local development uses `.env` and local tooling; Railway production uses environment variables and `railway/*.sh` scripts. Do not mix assumptions.

A successful agent contribution should be easy to understand and safe to deploy. If verification is not possible in the current environment, the agent must state what was skipped and why, and propose the minimal next step for the user to validate.


Because this repo is used for both local development and Railway deployment, keep branch intent clear. Use `main` for local or experimental work and a dedicated deployment branch (for example, `production`) for Railway changes when requested by the user. If branch naming is unclear, ask before creating or switching branches. Always avoid force-pushing unless explicitly requested.

## 2) Standard agent workflow: Explore -> Plan -> Implement -> Verify -> Review/PR

### Explore
Start by reading key files that define current behavior and constraints:

- `PROJECT_STATUS.md` for the current feature set, routes, and known limitations.
- `README.md` for the expected developer and Railway workflow.
- `composer.json` and `package.json` for scripts and tooling.
- `phpunit.xml` for test configuration.
- `railway/init-app.sh` and `railway/check-env.sh` for production boot rules.
- `Dockerfile` for the deployment build order.

When a request touches a subsystem, read the most relevant files instead of the entire codebase. Examples:

- Routes or auth: `routes/web.php`, `app/Http/Controllers/EmailLoginController.php`.
- Knowledge base or indexing: `app/Services/KbIndexer.php`, `app/Services/TextChunker.php`.
- API endpoints: `app/Http/Controllers/Api/*` and `routes/api.php`.

### Plan
Use a brief plan when a change is non-trivial or spans multiple files. Plans should be 2-4 steps and focus on safe sequencing (e.g., update config, then update docs, then verify). Do not plan the obvious tasks; prioritize clarity when risk is higher.

### Implement
Make edits in the smallest possible scope. Avoid unrelated cleanup and do not refactor broadly without explicit request. Keep diffs concentrated in the affected files. Use ASCII-only content unless the file already uses Unicode.


When implementing changes, keep commits scoped to one logical change whenever possible. If multiple concerns are involved (e.g., code plus docs), separate them only if the user requests or if it improves reviewability. Maintain the existing repository structure and avoid moving files unless the task requires it.

### Verify
Run the smallest verification command that proves the change is correct:

- For backend logic or routes: `composer test` or `php artisan test`.
- For UI or assets: `npm run build` (or `npm run dev` for local smoke checks).
- For database schema changes: `php artisan migrate` or `php artisan migrate:fresh --seed` when seed data is expected.

If verification fails or is not possible, record the failure and propose the next action. Do not mask failures.

### Review/PR
Summarize changes in plain language. Provide file-level context for why the change was made. If a PR is requested, ensure it includes only the intended changes and does not contain unrelated formatting or dependency updates.

## 3) Context packaging

The agent should keep context minimal but sufficient. Provide only the necessary files and snippets to reason about the change, and avoid flooding the prompt with large or unrelated files.

Recommended initial context for most tasks:

- `PROJECT_STATUS.md`: current features, routes, data model, and known limitations.
- `README.md`: onboarding, Railway deployment, and environment variables.
- `composer.json` and `package.json`: scripts and tooling.
- `phpunit.xml`: test configuration.

Then add only what is relevant:

- `routes/web.php` or `routes/api.php` for routing changes.
- The specific controller/service involved in the task.
- `railway/*.sh` or `Dockerfile` for deployment issues.
- `config/*.php` when environment variables or framework settings are involved.

When sharing context in prompts, prefer:

- Small code excerpts that include the lines to be changed.
- The specific command outputs that show the failure.
- A short summary of constraints (e.g., Railway runs behind a TLS-terminating proxy).

Avoid:

- Dumping entire minified assets or vendor files.
- Copying large sections of unrelated documentation.
- Repeating content already present in this document.


For environment-sensitive issues, prefer reading `config/*.php` and `railway/check-env.sh` to understand required variables and defaults. Do not read or modify `vendor/` files; treat them as immutable dependencies. If a change touches authentication, also scan `config/auth.php` and `config/sanctum.php` to avoid accidental security regressions.

## 4) Change policy

### Allowed changes

- Targeted fixes in application logic when requested.
- Configuration changes related to environment, deployment, or build stability.
- Documentation improvements, including Railway setup and runbook updates.
- Test additions or updates that directly validate the change.

### Disallowed or restricted changes

- Sweeping refactors across unrelated directories without explicit approval.
- Dependency updates or version changes unless requested or required for the fix.
- Changes that modify production behavior without a request and verification plan.
- Editing secrets or committing real credentials.

### Dependency changes

This repo uses Composer and npm. Do not modify `composer.lock` or `package-lock.json` unless required by a requested dependency change. If a dependency update is needed, call it out and wait for approval.

### Environment files

Do not edit `.env` for production guidance. Use `.env.example` or `README.md` for documentation. Environment changes for Railway should be documented, not hardcoded into the repo.

### Migrations and data

If a change requires a database migration, include it explicitly and note the need to run `php artisan migrate` locally and in Railway. If seed data is required for local validation, use the existing seeders described in `PROJECT_STATUS.md` (e.g., `DemoKnowledgeBaseSeeder`) and document the command used.


### Deployment-specific changes

Changes that affect Railway, Docker, or startup scripts must be scoped and documented. Do not modify `Dockerfile`, `railway/init-app.sh`, or `railway/check-env.sh` unless the task is explicitly about build or deployment behavior. When these files are changed, document the new expected order of operations and the reason the change is required.

## 5) Verification gates

Verification should be proportional to the change. If a change is purely documentation, tests are optional. If a change affects backend logic, run backend tests. If it affects frontend assets, run `npm run build`. If it affects deployments, review the relevant scripts and Dockerfile ordering.

Available verification commands in this repo:

- `composer test`
  - Runs `php artisan test` after clearing config as defined in `composer.json`.
  - Preferred for backend change verification.
- `php artisan test`
  - Equivalent to the test runner without composer wrapper.
  - Good for quick local checks.
- `npm run build`
  - Builds Vite assets for production. Run if Blade or JS changes affect assets.
- `php artisan migrate`
  - Applies migrations to the current database. Use when schema changes are part of the change.
- `php artisan migrate:fresh --seed`
  - Rebuilds the database and seeds demo data. Use for full local validation of KB flows.


- `composer dev`
  - Runs the multi-process local development setup (Artisan server, queue listener, logs, and Vite dev server).
  - Use only for full local development, not required for CI-style verification.
- `npm run dev`
  - Starts the Vite dev server for local UI iteration. Not a verification step.

Not configured in this repo:

- CI workflows in `.github/workflows` are not present.
- Automated lint or format scripts are not defined. `laravel/pint` is installed as a dev dependency, but no script is configured. Use `vendor/bin/pint` only if explicitly requested.
- No static analysis tools (PHPStan, Psalm) are configured.

Verification guidance by change type:

- Routes/controllers/services: `composer test`.
- Blade views or JS: `npm run build` (and optionally `composer test` if behavior changed).
- Deployment scripts or Dockerfile: no local test is guaranteed; review changes and document expected effects. If possible, build locally in an environment that matches Railway.
- Docs only: no verification required; ensure formatting is clean.

If a command fails due to missing tooling (e.g., npm, Composer, PHP extensions), record the failure and do not proceed with a substitute command that is not in the repo.

## 6) PR/Review checklist (Definition of Done)

Use this checklist before finalizing a change or opening a PR:

- The change matches the request and does not alter unrelated behavior.
- The smallest possible diff was used.
- Tests or verification commands were run as appropriate, or it is clearly stated why they were skipped.
- No secrets or credentials are included in commits or docs.
- Documentation is updated if the change affects usage, deployment, or configuration.
- Database migrations (if any) are included and noted with required commands.
- Railway scripts are consistent with the new behavior (if deployment-related changes are made).
- The change does not introduce unsupported scripts or tools.

If a PR is required, include:

- A short, factual description of the change.
- The exact commands used for verification.
- Any manual steps required to validate the change in Railway.

## 7) Troubleshooting playbook

### Composer install or artisan script failures

Symptoms:

- `Could not open input file: artisan` during `composer install` in Docker builds.

Response:

- Ensure `composer install` is executed with `--no-scripts` before the repo is copied or before `artisan` is available.
- Run `php artisan package:discover --ansi` after `COPY . .` when the app files exist.
- Keep the Dockerfile build order consistent: copy `composer.json`/`composer.lock`, install dependencies, then copy full repo.

### Mixed content or HTTPS issues on Railway

Symptoms:

- `https://` page renders with `http://` asset links or form actions.

Response:

- Confirm `APP_URL` and `ASSET_URL` are set to the Railway HTTPS domain in the web service variables.
- Redeploy to ensure config cache includes updated values.
- If proxy headers are required, ensure trusted proxies are configured using Symfony header bitmask without unsupported constants.

### Test failures

Symptoms:

- `php artisan test` fails in CI-like environments or locally.

Response:

- Review `phpunit.xml` to confirm environment variables used by tests (SQLite in-memory is configured).
- Ensure migrations are up to date and that any new migrations are reflected in tests.
- Run `composer test` to use the repo's test flow (config clear + test).

### Vite build failures

Symptoms:

- `npm run build` fails or assets are missing in production.

Response:

- Ensure `npm ci --include=dev` is used in Docker builds so Vite and Tailwind dev dependencies are available.
- Confirm `public/build/manifest.json` is generated after build.

### Railway init failures

Symptoms:

- `railway/init-app.sh` exits due to missing env vars.

Response:

- Use `railway/check-env.sh` to verify required variables are set.
- Ensure `APP_KEY`, `APP_URL`, `SINGLE_USER_EMAIL`, and DB variables are configured.
- For non-SQLite, provide `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

### Unknown tool or script referenced

Symptoms:

- A prompt or plan includes commands that are not in the repo.

Response:

- Remove or mark them as "Not configured".
- Prefer existing scripts in `composer.json` and `package.json`.
- Ask for clarification if a new tool is expected to be added.


### Config cache and environment drift

Symptoms:

- Behavior differs between local and Railway after a variable change.

Response:

- Ensure Railway variables are updated and a redeploy occurs.
- Use `php artisan optimize` in Railway (already in `railway/init-app.sh`). For local troubleshooting, `php artisan config:clear` can help if values appear stale.

## Assumptions and limits

- This guide assumes the codebase matches the current `PROJECT_STATUS.md` and README content.
- It assumes Railway is the primary production environment and local development is performed via PHP Artisan and Vite.
- If these assumptions are wrong, update this guide before applying new changes.
