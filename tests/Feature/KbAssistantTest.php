<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\KbDocument;
use App\Models\Message;
use App\Models\User;
use App\Services\AskModeResolver;
use App\Services\DocumentTextExtractionResult;
use App\Services\DocumentTextExtractorInterface;
use App\Services\KbIndexer;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KbAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function setSingleUserEmail(string $email): void
    {
        putenv("SINGLE_USER_EMAIL={$email}");
        $_ENV['SINGLE_USER_EMAIL'] = $email;
        $_SERVER['SINGLE_USER_EMAIL'] = $email;
    }

    public function test_allowed_email_can_login_and_see_dashboard(): void
    {
        $this->setSingleUserEmail('allowed@example.com');

        $response = $this->post('/login', [
            'email' => 'allowed@example.com',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->get('/dashboard')->assertOk();
    }

    public function test_wrong_email_cannot_login(): void
    {
        $this->setSingleUserEmail('allowed@example.com');

        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@example.com',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_can_create_kb_document(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/kb/documents', [
            'title' => 'Support FAQ',
            'source_type' => 'faq',
            'source_ref' => 'internal/support',
            'raw_text' => 'Q: Test? A: Answer.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_documents', [
            'title' => 'Support FAQ',
        ]);
    }

    public function test_can_create_kb_document_from_upload(): void
    {
        $user = User::factory()->create();

        Storage::fake('local');
        $this->app->bind(DocumentTextExtractorInterface::class, function () {
            return new class implements DocumentTextExtractorInterface {
                public function extract(UploadedFile $file): DocumentTextExtractionResult
                {
                    return new DocumentTextExtractionResult('Extracted upload text.');
                }
            };
        });

        $response = $this->actingAs($user)->post('/kb/documents', [
            'upload' => UploadedFile::fake()->create('Support Guide.pdf', 12, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $document = KbDocument::firstOrFail();
        $this->assertSame('upload', $document->source_type);
        $this->assertSame('Support Guide', $document->title);
        $this->assertSame('Extracted upload text.', $document->meta['raw_text']);
        $this->assertSame('Support Guide.pdf', $document->meta['original_filename']);
        $this->assertSame(strlen('Extracted upload text.'), $document->meta['size_bytes']);
        $this->assertNull($document->source_ref);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_raw_text_preferred_over_upload(): void
    {
        $user = User::factory()->create();

        Storage::fake('local');
        $this->app->bind(DocumentTextExtractorInterface::class, function () {
            return new class implements DocumentTextExtractorInterface {
                public function extract(UploadedFile $file): DocumentTextExtractionResult
                {
                    throw new \RuntimeException('Extractor should not be called when raw text is provided.');
                }
            };
        });

        $response = $this->actingAs($user)->post('/kb/documents', [
            'title' => 'Manual Entry',
            'source_type' => 'faq',
            'raw_text' => 'Manual text wins.',
            'upload' => UploadedFile::fake()->create('Ignored.pdf', 12, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $document = KbDocument::firstOrFail();
        $this->assertSame('faq', $document->source_type);
        $this->assertSame('Manual text wins.', $document->meta['raw_text']);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_can_index_document_created_from_upload(): void
    {
        $user = User::factory()->create();

        Storage::fake('local');
        $this->app->bind(DocumentTextExtractorInterface::class, function () {
            return new class implements DocumentTextExtractorInterface {
                public function extract(UploadedFile $file): DocumentTextExtractionResult
                {
                    return new DocumentTextExtractionResult(str_repeat('Chunked content. ', 120));
                }
            };
        });

        $response = $this->actingAs($user)->post('/kb/documents', [
            'upload' => UploadedFile::fake()->create('Index.pdf', 12, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $document = KbDocument::firstOrFail();

        $this->actingAs($user)->post("/kb/documents/{$document->id}/index")
            ->assertRedirect();

        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    public function test_can_index_document_and_chunks_exist(): void
    {
        $user = User::factory()->create();
        $document = KbDocument::create([
            'title' => 'Index Test',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => str_repeat('Chunk content. ', 200),
            ],
        ]);

        $response = $this->actingAs($user)->post("/kb/documents/{$document->id}/index");

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_documents', [
            'id' => $document->id,
            'status' => 'indexed',
        ]);
        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    public function test_can_update_kb_document(): void
    {
        $user = User::factory()->create();
        $document = KbDocument::create([
            'title' => 'Original Title',
            'source_type' => 'faq',
            'source_ref' => 'internal/original',
            'meta' => [
                'raw_text' => 'Original text.',
            ],
        ]);

        $response = $this->actingAs($user)->patch("/kb/documents/{$document->id}", [
            'title' => 'Updated Title',
            'source_type' => 'docs',
            'source_ref' => 'internal/updated',
            'raw_text' => 'Updated text.',
        ]);

        $response->assertRedirect();
        $document->refresh();
        $this->assertSame('Updated Title', $document->title);
        $this->assertSame('docs', $document->source_type);
        $this->assertSame('internal/updated', $document->source_ref);
        $this->assertSame('Updated text.', $document->meta['raw_text']);
    }

    public function test_can_delete_kb_document(): void
    {
        $user = User::factory()->create();
        $document = KbDocument::create([
            'title' => 'Delete Me',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Delete text.',
            ],
        ]);

        $response = $this->actingAs($user)->delete("/kb/documents/{$document->id}");

        $response->assertRedirect('/kb/documents');
        $this->assertDatabaseMissing('kb_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_can_ask_and_get_json_response(): void
    {
        $user = User::factory()->create();

        $document = KbDocument::create([
            'title' => 'Ask Test',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Support hours are 9am to 6pm weekdays.',
            ],
        ]);

        $indexer = app(KbIndexer::class);
        $indexer->index($document);

        $response = $this->actingAs($user)->postJson('/ask', [
            'question' => 'Support hours',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'answer',
            'chunks',
        ]);
        $this->assertNotEmpty($response->json('chunks'));
        $this->assertStringContainsString('Sources:', $response->json('answer'));

        $this->assertDatabaseCount('messages', 2);
        $this->assertDatabaseHas('messages', [
            'role' => 'user',
            'content' => 'Support hours',
        ]);
        $assistantMessage = Message::where('role', 'assistant')->latest()->first();
        $this->assertNotNull($assistantMessage);
        $this->assertNotEmpty($assistantMessage->retrieved_chunk_ids);
    }

    public function test_dashboard_lists_conversations_and_chat_page_shows_messages(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'title' => 'Support chat',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);

        $dashboard = $this->actingAs($user)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Support chat');

        $chat = $this->actingAs($user)->get("/chats/{$conversation->id}");
        $chat->assertOk();
        $chat->assertSee('Hello');
    }

    public function test_chat_ask_route_stores_messages_in_selected_conversation(): void
    {
        $user = User::factory()->create();
        $conversationA = Conversation::create(['title' => 'Chat A']);
        $conversationB = Conversation::create(['title' => 'Chat B']);

        $document = KbDocument::create([
            'title' => 'Ask Test',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Support hours are 9am to 6pm weekdays.',
            ],
        ]);
        app(KbIndexer::class)->index($document);

        $response = $this->actingAs($user)->postJson("/chats/{$conversationA->id}/ask", [
            'question' => 'Support hours',
        ]);

        $response->assertOk();
        $this->assertSame(2, Message::where('conversation_id', $conversationA->id)->count());
        $this->assertSame(0, Message::where('conversation_id', $conversationB->id)->count());
    }

    public function test_first_user_message_auto_titles_new_chat(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['title' => 'New chat']);
        $document = KbDocument::create([
            'title' => 'Support FAQ',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Support is available weekdays from 9am to 6pm.',
            ],
        ]);
        app(KbIndexer::class)->index($document);

        $this->actingAs($user)->postJson("/chats/{$conversation->id}/ask", [
            'question' => 'Tell me the support schedule for weekdays please',
        ])->assertOk();

        $conversation->refresh();
        $this->assertSame('Tell me the support schedule for weekdays please', $conversation->title);
    }

    public function test_ask_response_contains_source_jump_link_data(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['title' => 'Source Links']);
        $document = KbDocument::create([
            'title' => 'Billing FAQ',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Invoices are generated on the first business day of each month.',
            ],
        ]);
        app(KbIndexer::class)->index($document);

        $response = $this->actingAs($user)->postJson("/chats/{$conversation->id}/ask", [
            'question' => 'When are invoices generated?',
        ]);

        $response->assertOk();
        $chunks = $response->json('chunks');
        $this->assertNotEmpty($chunks);
        $this->assertSame($document->id, $chunks[0]['document_id'] ?? null);
        $this->assertStringContainsString('/kb/documents/' . $document->id . '#chunk-', (string) ($chunks[0]['document_url'] ?? ''));
    }

    public function test_llm_mode_uses_openai_response(): void
    {
        $user = User::factory()->create();

        $document = KbDocument::create([
            'title' => 'Ask LLM',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Support hours are 9am to 6pm weekdays.',
            ],
        ]);

        $indexer = app(KbIndexer::class);
        $indexer->index($document);

        config()->set('ask.openai_api_key', 'test-key');
        config()->set('ask.openai_model', 'test-model');
        config()->set('ask.default_mode', AskModeResolver::MODE_LLM);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => "LLM answer.\nSources: #1",
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/ask', [
            'question' => 'Support hours',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('LLM answer.', $response->json('answer'));

        $assistantMessage = Message::where('role', 'assistant')->latest()->first();
        $this->assertSame('test-model', $assistantMessage->model);
        $this->assertNotNull($assistantMessage->latency_ms);
    }

    public function test_can_set_ask_mode_preference_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/preferences/ask-mode', [
            'mode' => AskModeResolver::MODE_LLM,
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'mode' => AskModeResolver::MODE_LLM,
        ]);
        $response->assertCookie(AskModeResolver::COOKIE_NAME, AskModeResolver::MODE_LLM);
    }

    public function test_llm_mode_includes_prior_chat_history_in_prompt(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'title' => 'History Test',
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'How late is support available?',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Support is available 9am-6pm on weekdays.',
        ]);

        $document = KbDocument::create([
            'title' => 'Support Hours',
            'source_type' => 'faq',
            'source_ref' => null,
            'meta' => [
                'raw_text' => 'Standard support is available Monday through Friday, 9:00 AM to 6:00 PM.',
            ],
        ]);
        app(KbIndexer::class)->index($document);

        config()->set('ask.openai_api_key', 'test-key');
        config()->set('ask.openai_model', 'test-model');
        config()->set('ask.default_mode', AskModeResolver::MODE_LLM);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => "Your previous question was about support availability.\nSources: #1",
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/chats/{$conversation->id}/ask", [
            'question' => 'What question did I ask last time?',
        ]);

        $response->assertOk();
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ((string) $request->url() !== 'https://api.openai.com/v1/responses') {
                return false;
            }

            $payload = $request->data();
            $input = (string) ($payload['input'] ?? '');

            return str_contains($input, 'CHAT HISTORY:')
                && str_contains($input, 'user: How late is support available?')
                && str_contains($input, 'assistant: Support is available 9am-6pm on weekdays.');
        });
    }
}
