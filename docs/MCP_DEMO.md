# MCP Demo Evidence Guide

Use this checklist to capture MCP evidence for submission/defense.

## 1. Setup

1. Run Laravel backend:
   - `php artisan serve`
2. Generate API token:
   - `php artisan app:issue-api-token --name=mcp`
3. Configure MCP adapter env:
   - `API_BASE_URL`
   - `API_TOKEN`
4. Run adapter (or let MCP host launch it):
   - `npm run mcp:serve`

## 2. Register in MCP host

Register this server command in your MCP host client:

- command: `node`
- args: `["/absolute/path/to/ai-faq-assistant/mcp/server.js"]`
- env:
  - `API_BASE_URL=http://127.0.0.1:8000`
  - `API_TOKEN=<token>`

## 3. Demo sequence (tool calls)

1. `kb_list_documents`
2. `kb_search` with query `support hours`
3. `kb_ask` with question `What are support hours?`
4. Optional full lifecycle:
   - `kb_create_document`
   - `kb_index_document`
   - `kb_ask` on inserted content

## 4. Evidence to capture

1. Screenshot: MCP server connected in host.
2. Screenshot: successful `kb_list_documents` output.
3. Screenshot: successful `kb_ask` output.
4. Screenshot: optional create/index workflow.
5. Include exact command/config snippet used.

## 5. Security note (include in report)

This MCP adapter uses Bearer token authentication against protected Laravel API routes (`auth:sanctum`).
