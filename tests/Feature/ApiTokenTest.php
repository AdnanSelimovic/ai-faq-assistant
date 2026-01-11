<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $searchResponse->assertJsonStructure([
            'data' => [
                ['id', 'snippet', 'document_id', 'document_title'],
            ],
        ]);
    }
}
