# PROJECT HANDOFF SNAPSHOT

## Overview
This is a single-user Laravel 12 knowledge?base + FAQ assistant POC. It provides an authenticated admin UI to create KB documents, index them into chunks, and a dashboard ?Ask a question? flow that retrieves matching chunks and returns a placeholder response (no OpenAI yet).

## Working features (confirmed)
- Auth flow (email-only gate): `/login` validates against `SINGLE_USER_EMAIL`, creates/logs in user, redirects to dashboard.
- KB admin flow: list, create, show documents; index action generates chunks and updates status.
- Indexing pipeline: chunking (~1000 chars, 120 overlap), sha256 hashes, transactional re-index (delete+insert), status/meta error updates.
- Ask flow: dashboard fetch -> `/ask` -> retrieval (FULLTEXT or LIKE fallback) -> placeholder answer; user + assistant messages stored.
- Rate limiting: `POST /login` (10/min per IP) and `POST /ask` (30/min per user/IP).
- UI: shared layouts/components for consistent Tailwind styling; dashboard/KB pages use layouts.

## Key routes
- `GET /login` ? `EmailLoginController@create` (guest)
- `POST /login` ? `EmailLoginController@store` (guest, throttle:login)
- `POST /logout` ? `EmailLoginController@destroy` (auth)
- `GET /dashboard` ? view (auth)
- `GET /kb/documents` ? `KbDocumentController@index` (auth)
- `GET /kb/documents/create` ? `KbDocumentController@create` (auth)
- `POST /kb/documents` ? `KbDocumentController@store` (auth)
- `GET /kb/documents/{id}` ? `KbDocumentController@show` (auth)
- `POST /kb/documents/{id}/index` ? `KbDocumentController@indexDocument` (auth)
- `POST /ask` ? `ChatController@ask` (auth, throttle:ask)

## Database schema (custom tables)
- `kb_documents`: `id`, `title`, `source_type`, `source_ref`, `status`, `meta` (JSON), timestamps.
  - `meta->raw_text` stores full document text.
- `kb_chunks`: `id`, `document_id`, `chunk_index`, `content`, `content_hash`, `embedding` (null), `token_count` (null), timestamps.
  - FULLTEXT index on `content` (migration `2026_01_03_000007_add_fulltext_to_kb_chunks`).
- `conversations`: `id`, `title`, timestamps.
- `messages`: `id`, `conversation_id`, `role`, `content`, `retrieved_chunk_ids` (JSON), `model`, `from_cache`, `latency_ms`, timestamps.

Retrieval: `ChatController@ask` prefers `whereFullText` (natural language) with a scored `MATCH`; if empty/unavailable, falls back to LIKE search over sanitized terms.

## Key code entry points
- Controllers
  - `app/Http/Controllers/EmailLoginController.php`: email-only auth.
  - `app/Http/Controllers/KbDocumentController.php`: KB CRUD + indexing trigger.
  - `app/Http/Controllers/ChatController.php`: ask flow, retrieval, message storage, JSON response.
- Services
  - `app/Services/TextChunker.php`: chunking utility.
  - `app/Services/KbIndexer.php`: indexing pipeline for a `KbDocument`.
- UI
  - Layouts: `resources/views/layouts/app.blade.php`, `resources/views/layouts/auth.blade.php`.
  - Components: `resources/views/components/{button,input,textarea,form-error}.blade.php`.
- Seeders/Tests
  - `database/seeders/DemoKnowledgeBaseSeeder.php`: seeds 4 KB docs + indexes.
  - `tests/Feature/KbAssistantTest.php`: auth/KB/index/ask feature tests.

## Demo data
- `DemoKnowledgeBaseSeeder` inserts 4 curated FAQ?style docs (support hours, billing, security, formatting) into `kb_documents` and indexes them via `KbIndexer`.
- Run: `php artisan db:seed` (or `php artisan migrate:fresh --seed`).

## Current limitations / TODO
- No embeddings or vector search; retrieval is FULLTEXT/LIKE only.
- Assistant response is placeholder text (no actual RAG generation).
- No multi-user roles/permissions; single-user only.
- No KB edit/delete UI or background jobs for indexing.
- Potential edge case: FULLTEXT returns zero rows for short/stop words; LIKE fallback helps but is simplistic.

## Next recommended steps (ordered)
1) Add embeddings pipeline and vector storage (enable real RAG retrieval).
2) Implement answer generation (LLM or templated responses) using retrieved chunks.
3) Add KB document edit/delete workflows and re-index on update.
4) Move indexing to queued jobs with progress/status tracking.
5) Expand tests for rate limiting + retrieval edge cases.

## How to run locally
```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

## How to verify quickly
- Login with `SINGLE_USER_EMAIL` and confirm you land on `/dashboard`.
- Create a KB document, open it, click ?Run indexing,? and verify chunks appear.
- On dashboard, ask a question and confirm you see an answer plus sources.

---

## Command snapshots (from this environment)

### php artisan route:list
```
GET|HEAD   / 
POST       ask  chat.ask ? ChatController@ask
GET|HEAD   dashboard  dashboard
GET|HEAD   kb/documents  kb.documents.index ? KbDocumentController@index
POST       kb/documents  kb.documents.store ? KbDocumentController@store
GET|HEAD   kb/documents/create  kb.documents.create ? KbDocumentController@create
GET|HEAD   kb/documents/{id}  kb.documents.show ? KbDocumentController@show
POST       kb/documents/{id}/index  kb.documents.index-document ? KbDocumentController@indexDocument
GET|HEAD   login  login ? EmailLoginController@create
POST       login  EmailLoginController@store
POST       logout  logout ? EmailLoginController@destroy
GET|HEAD   storage/{path}  storage.local
GET|HEAD   up
```

### php artisan migrate:status
```
0001_01_01_000000_create_users_table  Ran
0001_01_01_000001_create_cache_table  Ran
0001_01_01_000002_create_jobs_table  Ran
2026_01_03_000003_create_kb_documents_table  Ran
2026_01_03_000004_create_kb_chunks_table  Ran
2026_01_03_000005_create_conversations_table  Ran
2026_01_03_000006_create_messages_table  Ran
2026_01_03_000007_add_fulltext_to_kb_chunks  Ran
```

### php artisan test
```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\KbAssistantTest
Tests: 7 passed (23 assertions)
```

### git status
```
## main...origin/main
 M app/Http/Controllers/ChatController.php
 M app/Providers/AppServiceProvider.php
 M resources/views/components/button.blade.php
 M resources/views/dashboard.blade.php
 M routes/web.php
?? database/migrations/2026_01_03_000007_add_fulltext_to_kb_chunks.php
?? tests/Feature/KbAssistantTest.php
```

### git log -10 --oneline
```
e1be90a Add ask flow and demo KB seeder
6c5101f Refactor KB UI layouts and components
538346e Add KB admin and indexing
c53a349 Refine login and dashboard styling
0488895 Add initial knowledge base schema (documents, chunks, conversations)
db9e736 Initial setup
```
