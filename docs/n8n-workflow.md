# n8n Workflow: Webhook -> Create -> Index -> Search -> Respond

This document describes the n8n workflow as exported in `docs/n8n-workflow-local.json`.

## Workflow overview
1) Webhook receives a JSON payload.
2) HTTP Request: Create document.
3) HTTP Request: Index document.
4) HTTP Request: Search.
5) Respond to webhook with the last node output (Search).

## Webhook payload schema
The workflow reads payload fields from `Webhook.json.body.*`.

Required fields:
- `idempotency_key` (string)
- `title` (string)
- `source_type` (string)
- `source_ref` (string)
- `raw_text` (string)
- `search_query` (string)

Example payload:
```json
{
  "idempotency_key": "n8n-demo-001",
  "title": "Support Hours",
  "source_type": "n8n",
  "source_ref": "webhook/support-hours",
  "raw_text": "Support hours are 9-6 weekdays.",
  "search_query": "support hours"
}
```

## HTTP Request nodes

### 1) Create document
- Method: `POST`
- URL: `http://host.docker.internal/api/kb/documents`
- Headers:
  - `Authorization: Bearer <token>`
  - `Content-Type: application/json`
  - `Idempotency-Key: {{$json.body.idempotency_key}}`
  - `Host: ai-faq-assistant.test`
- Body fields:
  - `title` from `Webhook.json.body.title`
  - `source_type` from `Webhook.json.body.source_type`
  - `source_ref` from `Webhook.json.body.source_ref`
  - `raw_text` from `Webhook.json.body.raw_text`

### 2) Index document
- Method: `POST`
- URL: `http://host.docker.internal/api/kb/documents/{{$node["Create document"].json["id"]}}/index`
- Headers:
  - `Authorization: Bearer <token>`
  - `Host: ai-faq-assistant.test`
- Body: none

### 3) Search
- Method: `POST`
- URL: `http://host.docker.internal/api/kb/search`
- Headers:
  - `Authorization: Bearer <token>`
  - `Content-Type: application/json`
  - `Host: ai-faq-assistant.test`
- Body fields:
  - `query` from `Webhook.json.body.search_query`
  - `limit` set to `5`

## Idempotency behavior
The Create document request sends `Idempotency-Key` from the webhook payload. Replaying the same `idempotency_key` should return the same document id with:
- `201` on first create
- `200` on replay for the same idempotency key

## Local-only assumptions
- `http://host.docker.internal` is used so the n8n container can reach the host machine.
- `Host: ai-faq-assistant.test` is required because the Laravel app is served via a local WAMP vhost.

## Production notes
- Update URLs to the real domain (no `host.docker.internal`).
- Remove the `Host` header if the public domain already routes correctly.
- Use the production webhook URL (not the test webhook URL).
- Activate the workflow so the production webhook is live.

## Screenshot checklist (n8n execution view)
- Webhook input payload with `idempotency_key`, `title`, `source_type`, `source_ref`, `raw_text`, `search_query`
- Create document request headers (Authorization redacted, Idempotency-Key, Host)
- Create document response showing status `201` (or `200` on replay) and `id`
- Index document request using the created `id`
- Index document response
- Search request payload and response
