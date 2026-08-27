# ALAS AI Chat Support

**Status:** V1 implemented
**Source specification:** `MD_FILES/ALAS_AI_CHAT_SUPPORT.md`
**Queue specification:** `alas-ecom/ALAS_AI_CHAT_QUEUE_AND_ANTI_SPAM_PLAN.md`
**Last updated:** 2026-08-27

## Architecture

ALAS Management (Laravel) remains the authoritative API and business-rules boundary. Supabase PostgreSQL stores conversations, messages, knowledge, audit events, assignments, and AI runs. ALAS E-Commerce uses same-origin Next.js route handlers that proxy validated requests to Laravel; it never receives the support access token in browser JavaScript.

Guest ownership uses a random 64-character token stored only as a SHA-256 hash in PostgreSQL and as a secure, HTTP-only, same-site cookie in the storefront. A conversation UUID alone never grants access.

Supabase Realtime Broadcast sends signal-only events on a conversation-specific topic. Message content remains in PostgreSQL and clients refetch it through the authorized API. The storefront also performs a slow authoritative refetch while open so missed broadcasts and reconnects cannot lose messages. Realtime failure never blocks message persistence.

While a customer batch is `DEBOUNCING`, `QUEUED`, `PROCESSING`, or `TYPING_DELAY`, the storefront displays an accessible three-dot typing bubble. The pending state comes from the authoritative conversation API, survives refetch/reconnection, stops on reply/failure/takeover, and respects reduced-motion preferences.

## Conversation behavior

- Modes: `AI_ACTIVE`, `HUMAN_ACTIVE`, `AI_PAUSED`, `RESOLVED`.
- Status is stored independently as `OPEN`, `NEEDS_ATTENTION`, `RESOLVED`, or `ARCHIVED`.
- A manual admin reply atomically takes over before the reply is saved.
- Takeover, return-to-AI, resolution, AI replies, escalation, and failures create support audit events.
- Return to AI does not process old messages. Only a newly persisted customer message dispatches an AI run.
- Customer message `client_message_id`, AI batch `(conversation_id, last_message_id)`, and AI run `trigger_message_id` uniqueness prevent duplicate persistence and duplicate LLM calls.
- The AI service locks and rechecks `conversation.mode` immediately before inserting its message. A result generated during human takeover is recorded as `DISCARDED_TAKEOVER` and is never delivered.

## AI and knowledge

V1 uses the provider interface in `App\Contracts\AiProvider` with Gemini defaults:

```text
AI_PROVIDER=gemini
AI_MODEL=gemini-3.7-flash
AI_FALLBACK_MODEL=gemini-3.1-flash-lite
AI_MODELS=gemini-3.7-flash,gemini-3.6-flash,gemini-3.5-flash,gemini-3.5-flash-lite,gemini-3.1-flash-lite
EMBEDDING_MODEL=gemini-embedding-2
EMBEDDING_DIMENSION=1536
```

Knowledge is versioned and moves through `DRAFT` → `PROCESSING` → `ACTIVE` or `FAILED`. Updating creates a new version; the previous active version is archived only after the new version indexes successfully. Disabled knowledge is excluded from retrieval. PostgreSQL uses pgvector with an HNSW cosine index; SQLite tests use the JSON representation and application-side cosine calculation.

The model receives only recent conversation messages, relevant active knowledge, and minimal verified live-tool results. Live products/inventory/prices are queried from management tables. Order details are queried only when the support customer is linked to the authorized Laravel user. Missing, conflicting, or unverifiable business data is escalated instead of guessed. Knowledge is not required for safe conversational turns such as greetings, thanks, clarification, and general support guidance; Gemini may answer those naturally but is explicitly prohibited from introducing ALAS-specific facts without a verified source.

Chat generation is tuned for concise customer support: temperature `0.5`, at most `250` output tokens, Gemini thinking level `LOW`, 4–6 recent messages (configured at 6), an extractive earlier-conversation summary capped at approximately 150 tokens, and up to 3 retrieved knowledge chunks. Knowledge chunks target roughly 250–400 tokens. The application context target is approximately 1,500–2,500 tokens excluding the protected system instruction. The canonical system instruction is stored in `private function systemPrompt(): string.md` and loaded server-side.

Gemini provider/quota failures preserve the customer message, mark the AI run failed, move the conversation to `AI_PAUSED` / `NEEDS_ATTENTION`, and leave manual chat available. The global kill switch stops automatic AI while preserving customer/admin messaging.

Chat generation uses the ordered `AI_MODELS` pool. A model that returns quota exhaustion (`429`), transient server/capacity errors (`5xx`), DNS/connect failures, timeouts, an empty response, or model unavailability is placed into a bounded local cooldown and the next configured stable text model is attempted immediately. Authentication and malformed-request errors stop immediately because rotating models cannot correct them. Successful AI runs record the model that actually answered. If the whole pool is unavailable, the normal human fallback behavior applies. When no active knowledge chunks exist, retrieval skips the embedding request entirely.

## Queue, batching, and anti-spam

`support_ai_jobs` is the durable AI-turn ledger. Every customer message is saved before any AI scheduling occurs. The first message creates one `DEBOUNCING` batch; continuation messages update its last-message boundary and reset the three-second quiet window, capped by an eight-second maximum batch wait. The delayed Laravel transport job is dispatched only once for that batch. Early or duplicate transport executions re-read the authoritative batch and release themselves without calling Gemini.

Ready work is processed oldest-first. A database-backed cache lock permits one active generation per conversation, and numbered global locks cap simultaneous Gemini work across all queue workers. A generated response is stored temporarily as `TYPING_DELAY`, then a separate delayed publication job inserts and broadcasts the first regex-delimited sentence bubble. Remaining sentence bubbles are published individually at the configured two-second interval. The generation worker and global LLM slot are therefore released before all display delays, and takeover can cancel any remaining unpublished segments.

Customer messages remain intact even when normalized duplicates are removed from the batched prompt. Limits of 8 messages per 30 seconds and 20 per 5 minutes return a server-authored cooldown message without spending an LLM call. Oversized messages are rejected before persistence. The prompt builder uses 4–6 recent messages, an extractive summary of at most approximately 150 tokens, at most 3 knowledge chunks, a target input budget of 2,500 tokens, and a hard ordinary ceiling of 3,000 tokens.

Takeover and resolution atomically mark all `DEBOUNCING`, `QUEUED`, `PROCESSING`, and `TYPING_DELAY` batches `CANCELLED`. Mode and batch state are checked before execution, before the provider call, before response persistence, and before realtime broadcast. If generation cannot be cancelled, its result is recorded as discarded and never reaches the customer.

## Access and security

- All public writes pass through validated, rate-limited Laravel endpoints.
- Owner, Manager, and Staff may handle support conversations using existing ALAS roles.
- Owner and Manager may manage knowledge; only Owner may toggle global AI.
- Supabase service-role and Gemini keys are server-only.
- RLS is enabled on exposed support and knowledge tables. Browser clients do not directly read or mutate these tables; without explicit direct-client policies access is denied.
- Customer and retrieved knowledge text are treated as untrusted data and cannot override the protected system instruction.
- Provider prompts exclude passwords, tokens, payment credentials, full records, and unnecessary personal data.

## Operations

Run the Laravel queue continuously; customer messages are durable before AI work is queued. Configure:

```text
AI_API_KEY=
SUPABASE_URL=
SUPABASE_SERVICE_ROLE_KEY=

AI_CHAT_DEBOUNCE_MS=3000
AI_CHAT_MAX_BATCH_WAIT_MS=8000
AI_CHAT_CONVERSATION_CONCURRENCY=1
AI_CHAT_GLOBAL_CONCURRENCY=3
AI_CHAT_MAX_MESSAGE_CHARS=2000
AI_CHAT_RATE_SHORT_LIMIT=8
AI_CHAT_RATE_SHORT_WINDOW_SEC=30
AI_CHAT_RATE_LONG_LIMIT=20
AI_CHAT_RATE_LONG_WINDOW_SEC=300
AI_CHAT_DUPLICATE_WINDOW_SEC=10
AI_CHAT_BURST_LIMIT=5
AI_CHAT_BURST_WINDOW_SEC=5
AI_CHAT_MAX_OUTPUT_TOKENS=250
AI_CHAT_HISTORY_MESSAGES=6
AI_CHAT_RAG_MAX_CHUNKS=3
AI_CHAT_TARGET_INPUT_TOKENS=2500
AI_CHAT_HARD_INPUT_TOKENS=3000
AI_CHAT_TYPING_BASE_MS=800
AI_CHAT_TYPING_PER_CHAR_MS=15
AI_CHAT_TYPING_MAX_MS=5000
AI_CHAT_SEGMENT_DELAY_MS=2000
```

The storefront needs its existing `ALAS_MANAGEMENT_URL` plus browser-safe `NEXT_PUBLIC_SUPABASE_URL` and `NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY` for realtime signals. If the latter are absent, chat continues through authoritative refetching.

Apply project migrations before deploying either UI. Deploy Laravel/queue first, then the storefront, so the widget never targets missing support endpoints.

## Verification

Automated coverage includes opaque guest ownership, message idempotency, automatic takeover on admin reply, takeover auditing, AI-result discard during a generation race, active knowledge retrieval, and disabled-knowledge exclusion. Production smoke testing must additionally verify Supabase Broadcast, queue processing, Gemini quota behavior, and all three admin roles against the deployed environment.
