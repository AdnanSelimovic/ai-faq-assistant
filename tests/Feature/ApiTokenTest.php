<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_endpoints_require_token(): void
    {
        $response = $this->postJson('/api/kb/documents', [
            'title' => 'API Doc',
            'source_type' => 'faq',
            'source_ref' => 'api',
            'raw_text' => 'Test content',
        ]);

        $response->assertStatus(401);
        $this->assertNotEquals(302, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $searchResponse = $this->postJson('/api/kb/search', [
            'query' => 'support hours',
            'limit' => 5,
        ]);

        $searchResponse->assertStatus(401);
        $this->assertNotEquals(302, $searchResponse->status());
        $this->assertStringContainsString('application/json', (string) $searchResponse->headers->get('Content-Type'));
    }

    public function test_api_list_requires_token_returns_json(): void
    {
        $response = $this->get('/api/kb/documents');

        $response->assertStatus(401);
        $this->assertNotEquals(302, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function test_api_create_requires_token_returns_json_without_json_headers(): void
    {
        $response = $this->post('/api/kb/documents', [
            'title' => 'API Doc',
            'source_type' => 'faq',
            'source_ref' => 'api',
            'raw_text' => 'Test content',
        ]);

        $response->assertStatus(401);
        $this->assertNotEquals(302, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function test_can_create_index_and_search_with_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/kb/documents', [
                'title' => 'API Support FAQ',
                'source_type' => 'faq',
                'source_ref' => 'api/support',
                'raw_text' => 'Support hours are 9am to 6pm weekdays.',
            ]);

        $createResponse->assertStatus(201);
        $this->assertStringContainsString('application/json', (string) $createResponse->headers->get('Content-Type'));
        $documentId = $createResponse->json('id');
        $this->assertNotNull($documentId);

        $indexResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/kb/documents/{$documentId}/index");

        $indexResponse->assertStatus(200);
        $this->assertSame('indexed', $indexResponse->json('status'));

        $searchResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/kb/search', [
                'query' => 'support hours',
                'limit' => 5,
            ]);

        $searchResponse->assertStatus(200);
        $this->assertStringContainsString('application/json', (string) $searchResponse->headers->get('Content-Type'));
        $searchResponse->assertJsonStructure([
            'data' => [
                ['id', 'snippet', 'document_id', 'document_title'],
            ],
        ]);
    }

    public function test_idempotent_create_returns_same_document(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $payload = [
            'title' => 'Idempotent Doc',
            'source_type' => 'n8n',
            'source_ref' => 'demo',
            'raw_text' => 'Support hours are 9-6 weekdays.',
        ];

        $first = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Idempotency-Key', 'test-key-1')
            ->postJson('/api/kb/documents', $payload);

        $first->assertStatus(201);
        $docId = $first->json('id');

        $second = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Idempotency-Key', 'test-key-1')
            ->postJson('/api/kb/documents', $payload);

        $second->assertStatus(200);
        $this->assertSame($docId, $second->json('id'));
        $this->assertDatabaseCount('kb_documents', 1);
    }

    public function test_verify_idempotency_command_passes(): void
    {
        $exitCode = Artisan::call('app:verify-idempotency');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('PASS', Artisan::output());
    }
}
