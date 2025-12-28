<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Laravel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use TestFlowLabs\PestPluginBridge\Bridge;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enable HTTP faking in browser tests.
 *
 * This middleware reads fake HTTP configuration written by Bridge::fake()
 * and registers it with Laravel's Http facade. This enables faking external
 * API calls (like Stripe, SendGrid) in browser tests where the test process
 * and server process are separate.
 *
 * Installation:
 * 1. Add this middleware to your app/Http/Kernel.php or bootstrap/app.php
 * 2. Only enable in testing environment
 *
 * Example (Laravel 11+ with bootstrap/app.php):
 * ```php
 * ->withMiddleware(function (Middleware $middleware) {
 *     if (app()->environment('testing')) {
 *         $middleware->prepend(BridgeHttpFakeMiddleware::class);
 *     }
 * })
 * ```
 *
 * Example (Laravel 10 with Kernel.php):
 * ```php
 * protected $middleware = [
 *     // ... other middleware
 *     \TestFlowLabs\PestPluginBridge\Laravel\BridgeHttpFakeMiddleware::class,
 * ];
 * ```
 */
class BridgeHttpFakeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Bridge::hasFakes()) {
            return $next($request);
        }

        $fakes = Bridge::getFakes();

        $httpFakes = [];
        foreach ($fakes as $pattern => $config) {
            $httpFakes[$pattern] = Http::response(
                $config['body'] ?? [],
                $config['status'] ?? 200,
                $config['headers'] ?? []
            );
        }

        Http::fake($httpFakes);

        return $next($request);
    }
}
