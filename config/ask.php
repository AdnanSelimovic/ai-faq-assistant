<?php

return [
    'default_mode' => env('ASK_ANSWER_MODE_DEFAULT', 'extractive'),
    'max_context_chunks' => env('ASK_MAX_CONTEXT_CHUNKS', 5),
    'max_context_chars' => env('ASK_MAX_CONTEXT_CHARS', 4000),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_store' => env('OPENAI_STORE', false),
    'openai_max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS', 400),
];
