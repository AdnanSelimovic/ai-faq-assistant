<?php

namespace App\Http\Controllers;

use App\Services\AskModeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AskPreferenceController extends Controller
{
    public function store(Request $request, AskModeResolver $resolver): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', Rule::in($resolver->allowedModes())],
        ]);

        $minutes = 60 * 24 * 30;

        $cookie = cookie(
            AskModeResolver::COOKIE_NAME,
            $validated['mode'],
            $minutes,
            null,
            null,
            $request->isSecure(),
            true,
            false,
            'Lax'
        );

        return response()->json([
            'ok' => true,
            'mode' => $validated['mode'],
        ])->cookie($cookie);
    }
}
