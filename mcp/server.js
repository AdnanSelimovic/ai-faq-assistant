import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";

const baseUrl = (process.env.API_BASE_URL || "http://127.0.0.1:8000").replace(/\/+$/, "");
const apiToken = process.env.API_TOKEN || "";
const defaultLimit = Number.parseInt(process.env.MCP_DEFAULT_LIMIT || "5", 10);

if (!apiToken) {
  console.error("MCP adapter warning: API_TOKEN is not set. Tool calls will fail with 401.");
}

const server = new McpServer({
  name: "ai-faq-assistant-mcp",
  version: "1.0.0",
});

function responseAsToolResult(data) {
  return {
    content: [
      {
        type: "text",
        text: JSON.stringify(data, null, 2),
      },
    ],
  };
}

async function apiRequest(path, method = "GET", body = null, extraHeaders = {}) {
  const headers = {
    Accept: "application/json",
    Authorization: `Bearer ${apiToken}`,
    ...extraHeaders,
  };

  if (body !== null && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const response = await fetch(`${baseUrl}${path}`, {
    method,
    headers,
    body: body !== null ? JSON.stringify(body) : undefined,
  });

  const payload = await response.json().catch(() => ({
    message: "Response was not valid JSON.",
  }));

  if (!response.ok) {
    const error = payload?.message || payload?.error || `HTTP ${response.status}`;
    throw new Error(`API request failed (${method} ${path}): ${error}`);
  }

  return payload;
}

server.tool(
  "kb_list_documents",
  "List knowledge base documents with optional status and age filters.",
  {
    status: z.enum(["draft", "indexed", "error"]).optional(),
    older_than_days: z.number().int().positive().optional(),
  },
  async ({ status, older_than_days }) => {
    const params = new URLSearchParams();
    if (status) params.set("status", status);
    if (older_than_days) params.set("older_than_days", String(older_than_days));
    const query = params.toString();

    const data = await apiRequest(`/api/kb/documents${query ? `?${query}` : ""}`, "GET");
    return responseAsToolResult(data);
  }
);

server.tool(
  "kb_create_document",
  "Create a new KB document from raw text.",
  {
    title: z.string().min(1).max(255),
    source_type: z.string().min(1).max(50),
    raw_text: z.string().min(1),
    source_ref: z.string().max(2048).optional(),
    idempotency_key: z.string().max(100).optional(),
  },
  async ({ title, source_type, raw_text, source_ref, idempotency_key }) => {
    const headers = {};
    if (idempotency_key) {
      headers["Idempotency-Key"] = idempotency_key;
    }

    const data = await apiRequest(
      "/api/kb/documents",
      "POST",
      {
        title,
        source_type,
        source_ref: source_ref || null,
        raw_text,
      },
      headers
    );
    return responseAsToolResult(data);
  }
);

server.tool(
  "kb_index_document",
  "Run indexing on an existing KB document.",
  {
    document_id: z.number().int().positive(),
  },
  async ({ document_id }) => {
    const data = await apiRequest(`/api/kb/documents/${document_id}/index`, "POST");
    return responseAsToolResult(data);
  }
);

server.tool(
  "kb_search",
  "Search knowledge base chunks using project retrieval logic.",
  {
    query: z.string().min(1),
    limit: z.number().int().positive().max(20).optional(),
  },
  async ({ query, limit }) => {
    const data = await apiRequest("/api/kb/search", "POST", {
      query,
      limit: limit || defaultLimit,
    });
    return responseAsToolResult(data);
  }
);

server.tool(
  "kb_ask",
  "Ask a question against the knowledge base assistant.",
  {
    question: z.string().min(1).max(2000),
    conversation_id: z.number().int().positive().optional(),
  },
  async ({ question, conversation_id }) => {
    const payload = { question };
    if (conversation_id) {
      payload.conversation_id = conversation_id;
    }

    const data = await apiRequest("/api/ask", "POST", payload);
    return responseAsToolResult(data);
  }
);

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((error) => {
  console.error("MCP adapter failed to start:", error);
  process.exit(1);
});

