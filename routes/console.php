<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
