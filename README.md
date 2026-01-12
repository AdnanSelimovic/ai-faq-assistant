<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Deploying on Railway

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

## n8n Workflow #1: Webhook ingest -> create doc -> index

Webhook input JSON:
```json
{
  "title": "Test Doc",
  "source_type": "n8n",
  "source_ref": "demo",
  "raw_text": "Support hours are 9-6 weekdays."
}
```

HTTP Request node (create):
- URL: `POST /api/kb/documents`
- Authorization: `Bearer <token>`
- Header: `Idempotency-Key: {{$json.idempotency_key}}`
- Body: JSON payload above

HTTP Request node (index):
- URL: `POST /api/kb/documents/{id}/index`
- Authorization: `Bearer <token>`
