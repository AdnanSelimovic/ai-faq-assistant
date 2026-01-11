<?php

namespace Tests\Feature;

use App\Models\KbDocument;
use App\Models\Message;
use App\Models\User;
use App\Services\KbIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertDatabaseCount('messages', 2);
        $this->assertDatabaseHas('messages', [
            'role' => 'user',
            'content' => 'Support hours',
        ]);
        $this->assertDatabaseHas('messages', [
            'role' => 'assistant',
        ]);
    }
}
