# n8n Demo Runbook

This repository includes an import-ready n8n workflow:

- `docs/n8n/workflow.json`

## 1. Import

1. Open n8n (cloud or local).
2. Create a new workflow.
3. Use **Import from file** and select `docs/n8n/workflow.json`.

## 2. One-time edits

Open node **Set Demo Input** and edit only these two values:

1. `railway_base_url`:
   - Example: `https://your-app.up.railway.app`
2. `api_token`:
   - Sanctum token from `php artisan app:issue-api-token --name=n8n`

All other values are demo defaults and can stay as-is.

## 3. Run

1. Click **Execute workflow**.
2. Inspect node outputs in order:
   - `Create Document` -> should return document `id`
   - `Index Document` -> should return `status: indexed`
   - `Ask` -> should return `answer`, `chunks`, `retrieval`
   - `Set Output` -> condensed final summary

## 4. What this demonstrates

- n8n orchestration over your deployed app
- API auth via Bearer token
- KB document ingest + indexing + ask flow
- RAG-backed ask response through automation pipeline

## 5. Troubleshooting

1. `401 Unauthorized`:
   - token is invalid or missing
2. `Invalid character in header content ["authorization"]`:
   - token has hidden spaces/newlines; re-paste token and ensure no quotes
   - workflow now trims token automatically, but the value must still be plain text
3. `404`:
   - wrong `railway_base_url` or app not deployed
4. `422`:
   - malformed request body (check node expressions)
