<?php

return [
    'default_mode' => env('ASK_ANSWER_MODE_DEFAULT', 'extractive'),
    'embedding_driver' => env('ASK_EMBEDDING_DRIVER', 'local'),
    'embedding_model' => env('ASK_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'embedding_dimensions' => env('ASK_EMBEDDING_DIMENSIONS', 256),
    'embedding_batch_size' => env('ASK_EMBEDDING_BATCH_SIZE', 50),
    'retrieval_mode' => env('ASK_RETRIEVAL_MODE', 'hybrid'),
    'hybrid_vector_weight' => env('ASK_HYBRID_VECTOR_WEIGHT', 0.7),
    'vector_min_score' => env('ASK_VECTOR_MIN_SCORE', 0.08),
    'vector_candidate_limit' => env('ASK_VECTOR_CANDIDATE_LIMIT', 300),
    'max_context_chunks' => env('ASK_MAX_CONTEXT_CHUNKS', 5),
    'max_context_chars' => env('ASK_MAX_CONTEXT_CHARS', 4000),
    'max_history_messages' => env('ASK_MAX_HISTORY_MESSAGES', 8),
    'max_history_chars' => env('ASK_MAX_HISTORY_CHARS', 2000),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_store' => env('OPENAI_STORE', false),
    'openai_max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS', 400),
];
