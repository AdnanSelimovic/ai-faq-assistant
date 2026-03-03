# MCP Adapter (AI FAQ Assistant)

This folder contains a local MCP server adapter that exposes this project's Laravel API as MCP tools.

## Tools exposed

- `kb_list_documents`
- `kb_create_document`
- `kb_index_document`
- `kb_search`
- `kb_ask`

## Prerequisites

1. Laravel app running (e.g. `php artisan serve`).
2. API token generated from this project:
   - `php artisan app:issue-api-token --name=mcp`
3. Node.js 20+.

## Environment

Copy `mcp/.env.example` values into your MCP client environment:

- `API_BASE_URL` (example: `http://127.0.0.1:8000`)
- `API_TOKEN` (Sanctum bearer token)
- `MCP_DEFAULT_LIMIT` (optional default limit for search)

## Run directly (local check)

```bash
npm run mcp:serve
```

The server uses STDIO transport, so it is intended to be launched by an MCP host client (ChatGPT Developer Mode, Claude Desktop, Cursor, etc.).

## Notes

- All tool calls require a valid API token.
- The adapter returns raw JSON payloads from your backend formatted as MCP text content.
