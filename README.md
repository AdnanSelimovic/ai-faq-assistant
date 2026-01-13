# AI FAQ Assistant

Single-user Laravel 12 app for managing a knowledge base and answering questions with extractive or LLM-backed responses.

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.4, Laravel 12 |
| **Frontend** | Blade, Vite, TailwindCSS |
| **Database** | MySQL |
| **LLM (optional)** | OpenAI Responses API |

## Features

- ✅ Email-only login (single-user)
- ✅ Knowledge base CRUD with indexing
- ✅ Ask mode toggle (extractive vs LLM)
- ✅ Clean dashboard UI
- ✅ Railway-ready deployment flow

## Project Structure

```
ai-faq-assistant/
├── app/                    # Controllers, services, models
├── database/               # Migrations and seeders
├── public/                 # Public assets and build output
├── railway/                # Railway init scripts
├── resources/              # Blade views, CSS, JS
├── routes/                 # Web and API routes
└── README.md
```

## Local Development

1) Install PHP 8.4, Composer, and Node.js 20+.
2) Copy `.env.example` to `.env` and set `APP_KEY`, `DB_*`, and `SINGLE_USER_EMAIL`.
3) Run migrations:
   - `php artisan migrate`
4) Install frontend deps and build:
   - `npm ci`
   - `npm run build`
5) Start the app:
   - `php artisan serve`

## Deploying on Railway

### Railway Manual Setup (UI steps)
1) In your Railway project, create/add a new Database service and choose MySQL (per Railway database docs).
2) Open the web service, go to Variables, and use the Raw Editor to paste the env block below. The MySQL service provides `MYSQL*` vars; reference them from the web service using Railway's `${{VAR_NAME}}` syntax.
3) Generate a unique `APP_KEY` for production (run `php artisan key:generate --show` locally or in a trusted environment) and paste it; avoid reusing keys across unrelated deployments when possible.
4) HTTPS gotcha: if the site is served via `https://` but you see `http://` asset URLs or form actions (e.g. `/logout`), set `APP_URL` and `ASSET_URL` to your Railway `https://` domain and redeploy.
5) To stop the app/saving resources, use Railway's scaling/sleep controls in the service settings (see Railway runtime/scaling docs for your plan).

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your railway domain>
ASSET_URL=https://<your railway domain>
APP_KEY=<generate and paste>
SINGLE_USER_EMAIL=<your email>

DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

# Optional but recommended
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Optional OpenAI settings
OPENAI_API_KEY=<your key>
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_OUTPUT_TOKENS=400
```

Smoke test:
- Visit `/` and `/login`.
- Sign in and confirm the dashboard loads with CSS.
- Click “Log out” and ensure it works.
- Confirm migrations completed (no migration errors on boot).

### Connect the repo
1) Create a new Railway project and connect this repo.
2) Use the Nixpacks builder (or Auto that resolves to Nixpacks).

### Add MySQL
1) Add the MySQL plugin in Railway.
2) Copy the MySQL connection values into your environment variables.

### Required environment variables
Set these in Railway before deploy:
- `APP_KEY` (generate locally with `php artisan key:generate --show`)
- `APP_URL` (your Railway public URL)
- `SINGLE_USER_EMAIL` (the only allowed login)
- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Railway MySQL plugin mapping:
- `MYSQLHOST` -> `DB_HOST`
- `MYSQLPORT` -> `DB_PORT`
- `MYSQLUSER` -> `DB_USERNAME`
- `MYSQLPASSWORD` -> `DB_PASSWORD`
- `MYSQLDATABASE` -> `DB_DATABASE`

### PHP requirements
- PHP 8.4 is required.
- Extensions: `gd`, `zip`.
- If `composer install` fails on Railway with missing extensions or PHP version, the `nixpacks.toml` in the repo forces PHP 8.4 and installs `gd` + `zip`.

### Build and start commands
- Build command:
  - `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
- Start command:
  - `php artisan serve --host=0.0.0.0 --port=$PORT`

### Railway settings
- Build command: `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
- Start command: `php artisan serve --host=0.0.0.0 --port=$PORT` (or keep the `Procfile`)
- Pre-deploy command: `bash railway/init-app.sh` (or `bash railway/check-env.sh && bash railway/init-app.sh`)

### Pre-deploy command
- `bash railway/init-app.sh`

### Script permissions
If the scripts fail with "permission denied", run:
`chmod +x railway/init-app.sh railway/check-env.sh`

### Smoke tests
1) Visit `/login` and sign in with `SINGLE_USER_EMAIL`.
2) Create a KB document, open it, and click "Run indexing".
3) Ask a question in the dashboard and confirm you get a response.

### Deployment checklist
- Connect Railway project
- Add MySQL plugin
- Copy env vars
- Run migrations
- Visit `/login` and test functionality
- Verify API endpoints

## KB document uploads

The admin "Create Document" screen supports optional file uploads (PDF, DOCX, PPTX). If raw text is pasted, it takes priority and the upload is ignored. The file is only used for text extraction and is not stored.

PDF extraction uses the `smalot/pdfparser` library and does not require external binaries.

## Ask answer modes

The dashboard Ask flow supports two modes:
- `extractive` (default): summarizes top retrieved chunks without external calls and appends a Sources line.
- `llm`: uses the OpenAI Responses API with retrieved chunks as context.

The selected mode is stored in a `kb_ask_mode` cookie (set via an AJAX POST to `/preferences/ask-mode`) and remembered across sessions.

Required env/config for LLM mode:
- `OPENAI_API_KEY`
- `OPENAI_MODEL` (default `gpt-4o-mini`)
- `OPENAI_STORE` (default false)
- `ASK_MAX_CONTEXT_CHUNKS` (default 5)
- `ASK_MAX_CONTEXT_CHARS` (default 4000)
