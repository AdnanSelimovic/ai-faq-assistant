<?php

namespace App\Services;

use Illuminate\Http\Request;

class AskModeResolver
{
    public const COOKIE_NAME = 'kb_ask_mode';
    public const MODE_EXTRACTIVE = 'extractive';
    public const MODE_LLM = 'llm';

    /**
     * @return array<int, string>
     */
    public function allowedModes(): array
    {
        return [
            self::MODE_EXTRACTIVE,
            self::MODE_LLM,
        ];
    }

    public function resolve(Request $request): string
    {
        $mode = $this->normalize($request->cookie(self::COOKIE_NAME));
        if ($mode && $this->isAllowed($mode)) {
            return $mode;
        }

        $default = $this->normalize(config('ask.default_mode', self::MODE_EXTRACTIVE));
        return $this->isAllowed($default) ? $default : self::MODE_EXTRACTIVE;
    }

    public function isAllowed(?string $mode): bool
    {
        return $mode !== null && in_array($mode, $this->allowedModes(), true);
    }

    private function normalize(?string $mode): ?string
    {
        if ($mode === null) {
            return null;
        }

        $mode = strtolower(trim($mode));

        return $mode !== '' ? $mode : null;
    }
}
