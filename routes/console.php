<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\KbDocument;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:issue-api-token {--name=api}', function () {
    $email = env('SINGLE_USER_EMAIL');
    if (!$email) {
        $this->error('SINGLE_USER_EMAIL is not set.');
        return 1;
    }

    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => 'Admin',
            'password' => Hash::make(Str::random(40)),
        ]
    );

    $token = $user->createToken($this->option('name'))->plainTextToken;
    $this->line($token);

    return 0;
})->purpose('Issue a Sanctum API token for the single-user account');

Artisan::command('app:verify-idempotency', function () {
    $email = env('SINGLE_USER_EMAIL');
    if (!$email) {
        $this->error('SINGLE_USER_EMAIL is not set.');
        return 1;
    }

    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => 'Admin',
            'password' => Hash::make(Str::random(40)),
        ]
    );

    $token = $user->createToken('verify-idempotency')->plainTextToken;
    $idempotencyKey = 'verify-idem-' . Str::uuid()->toString();
    $sourceRef = 'verify-' . Str::uuid()->toString();

    $payload = [
        'title' => 'Idempotency Verify',
        'source_type' => 'n8n',
        'source_ref' => $sourceRef,
        'raw_text' => 'Support hours are 9-6 weekdays.',
    ];

    $kernel = app(Kernel::class);

    $makeRequest = function () use ($payload, $token, $idempotencyKey, $kernel) {
        $request = Request::create('/api/kb/documents', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_IDEMPOTENCY_KEY' => $idempotencyKey,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode($payload));
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setRequestFormat('json');

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    };

    $first = $makeRequest();
    $second = $makeRequest();

    $firstStatus = $first->getStatusCode();
    $secondStatus = $second->getStatusCode();

    $firstJson = json_decode($first->getContent(), true);
    $secondJson = json_decode($second->getContent(), true);

    $firstId = $firstJson['id'] ?? null;
    $secondId = $secondJson['id'] ?? null;

    $count = KbDocument::where('source_ref', $sourceRef)->count();

    $passes = $firstStatus === 201
        && $secondStatus === 200
        && $firstId !== null
        && $firstId === $secondId
        && $count === 1;

    $label = $passes ? 'PASS' : 'FAIL';

    $this->line(sprintf(
        '%s first=%d second=%d id=%s count=%d',
        $label,
        $firstStatus,
        $secondStatus,
        $firstId ?? 'null',
        $count
    ));

    return $passes ? 0 : 1;
})->purpose('Verify API idempotency without external HTTP');
