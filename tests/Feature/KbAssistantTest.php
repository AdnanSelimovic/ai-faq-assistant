<?php

namespace Tests\Feature;

use App\Models\KbDocument;
use App\Models\Message;
use App\Models\User;
use App\Services\DocumentTextExtractionResult;
use App\Services\DocumentTextExtractorInterface;
use App\Services\KbIndexer;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
