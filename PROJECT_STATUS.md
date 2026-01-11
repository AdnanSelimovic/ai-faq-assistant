# PROJECT HANDOFF SNAPSHOT

## Overview
This is a single-user Laravel knowledge-base/FAQ assistant POC with an authenticated admin UI, KB document indexing into chunks, a dashboard ?Ask? flow that retrieves chunks and returns a placeholder answer, plus a Sanctum-protected JSON API layer for automation (n8n/MCP). No OpenAI/embeddings yet.

## Working features (confirmed)
- n8n workflow documentation: docs/n8n-workflow.md (local webhook -> create/index/search).
- Auth flow (email-only gate): `/login` validates `SINGLE_USER_EMAIL`, creates/logs in a user, redirects to dashboard.
- KB admin flow: list, create, show documents; index action generates chunks and updates status.
- Indexing pipeline: chunking (~1000 chars, 120 overlap), sha256 hashes, transactional re-index (delete+insert), status/meta error updates.
- Ask flow: dashboard fetch -> `/ask` -> retrieval (FULLTEXT or LIKE fallback) -> placeholder answer; user + assistant messages stored.
- Rate limiting: `POST /login` (10/min per IP) and `POST /ask` (30/min per user/IP).
- JSON API: token-authenticated endpoints for create/index/list/search (n8n/MCP-ready), plus optional `/api/ask`.
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
- `POST /api/kb/documents` ? `Api\KbDocumentApiController@store` (auth:sanctum, ForceJsonResponse)
- `POST /api/kb/documents/{id}/index` ? `Api\KbDocumentApiController@index` (auth:sanctum, ForceJsonResponse)
- `GET /api/kb/documents` ? `Api\KbDocumentApiController@list` (auth:sanctum, ForceJsonResponse)
- `POST /api/kb/search` ? `Api\KbSearchApiController@search` (auth:sanctum, ForceJsonResponse)
- `POST /api/ask` ? `ChatController@ask` (auth:sanctum, throttle:ask, ForceJsonResponse)

## Database schema (custom tables)
- `kb_documents`: `id`, `title`, `source_type`, `source_ref`, `status`, `meta` (JSON), timestamps.
  - `meta->raw_text` stores full document text.
- `kb_chunks`: `id`, `document_id`, `chunk_index`, `content`, `content_hash`, `embedding` (null), `token_count` (null), timestamps.
  - FULLTEXT index on `content` (MySQL), unique(`document_id`,`chunk_index`).
- `conversations`: `id`, `title`, timestamps.
- `messages`: `id`, `conversation_id`, `role`, `content`, `retrieved_chunk_ids` (JSON), `model`, `from_cache`, `latency_ms`, timestamps.
- `api_idempotency_keys`: `id`, `user_id`, `route`, `key`, `request_hash`, `response_json` (JSON), `status_code`, timestamps; unique(`user_id`,`route`,`key`).
- `personal_access_tokens` (Sanctum): token storage for API auth.

Retrieval: `ChatController@ask` prefers `whereFullText` (natural language) with a scored `MATCH`; if empty/unavailable, falls back to LIKE search over sanitized terms.

## Key code entry points
- Controllers
  - `app/Http/Controllers/EmailLoginController.php`: email-only auth.
  - `app/Http/Controllers/KbDocumentController.php`: KB CRUD + indexing trigger.
  - `app/Http/Controllers/ChatController.php`: ask flow, retrieval, message storage, JSON response.
  - `app/Http/Controllers/Api/KbDocumentApiController.php`: JSON create/list/index with idempotency.
  - `app/Http/Controllers/Api/KbSearchApiController.php`: JSON search endpoint.
- Services
  - `app/Services/TextChunker.php`: chunking utility.
  - `app/Services/KbIndexer.php`: indexing pipeline for a `KbDocument`.
- Middleware
  - `app/Http/Middleware/ForceJsonResponse.php`: forces JSON responses for API routes.
- Seeders/Tests
  - `database/seeders/DemoKnowledgeBaseSeeder.php`: seeds 4 KB docs + indexes.
  - `tests/Feature/KbAssistantTest.php`: auth/KB/index/ask feature tests.
  - `tests/Feature/ApiTokenTest.php`: API token + idempotency tests.

## Demo data
- `DemoKnowledgeBaseSeeder` inserts 4 curated FAQ?style docs (support hours, billing, security, formatting) into `kb_documents` and indexes them via `KbIndexer`.
- Run: `php artisan db:seed` (or `php artisan migrate:fresh --seed`).

## Current limitations / TODO
- No embeddings or vector search; retrieval is FULLTEXT/LIKE only.
- Assistant response is placeholder text (no actual RAG generation).
- No multi-user roles/permissions; single-user only.
- No KB edit/delete UI or background jobs for indexing.
- Idempotency does not verify payload hash matches for repeated keys (can be added later).

## Next recommended steps (ordered)
1) Add embeddings pipeline and vector storage (enable real RAG retrieval).
2) Implement answer generation (LLM or templated responses) using retrieved chunks.
3) Add KB document edit/delete workflows and re-index on update.
4) Move indexing to queued jobs with progress/status tracking.
5) Expand tests for rate limiting, idempotency hash mismatch, and API error cases.

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
- Call `/api/kb/documents` with a token to verify JSON auth + idempotency.

