# MCP Demo Runbook

Use this runbook to perform and explain the MCP part during submission/defense.

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

## 4. Security note (include in report)

This MCP adapter uses Bearer token authentication against protected Laravel API routes (`auth:sanctum`).

## 5. Defense demo script (text only)

Use this exact sequence live:

1. Start backend: `php artisan serve`
2. Ensure MCP server is running in your host (Claude Local MCP servers).
3. Call `kb_list_documents`.
4. Call `kb_search` with query `support hours`.
5. Call `kb_ask` with question `What are support hours?`.
6. Optional lifecycle:
   - `kb_create_document`
   - `kb_index_document`
   - `kb_ask` on the newly indexed content.

Expected outcome:

- MCP tools are callable from host.
- Tool responses come from protected Laravel API routes.
- Ask/search results are grounded in the project KB.
