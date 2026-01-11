<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Services\DocumentTextExtractor;
use App\Services\DocumentTextExtractorInterface;
use App\Services\DocumentTextExtractors\DocxTextExtractor;
use App\Services\DocumentTextExtractors\PdfTextExtractor;
use App\Services\DocumentTextExtractors\PptxTextExtractor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentTextExtractorInterface::class, function () {
            return new DocumentTextExtractor(
                new PdfTextExtractor(),
                new DocxTextExtractor(),
                new PptxTextExtractor(),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () {
                    return redirect()
                        ->back()
                        ->withErrors(['email' => 'Too many login attempts. Please try again shortly.'])
                        ->setStatusCode(429);
                });
        });

        RateLimiter::for('ask', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(30)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                    ], 429);
                });
        });
    }
}
