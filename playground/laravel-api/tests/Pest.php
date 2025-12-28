<?php

declare(strict_types=1);

use Tests\TestCase;
use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Browser Tests Configuration
|--------------------------------------------------------------------------
|
| Browser tests use Laravel's TestCase to bootstrap the application,
| enabling pest-plugin-browser's HTTP server. The BridgeTrait provides
| the bridge() method for testing external frontend applications.
|
| The frontend server is automatically started using ->serve().
| The Laravel API URL is auto-injected via NUXT_PUBLIC_API_BASE env var.
| Cleanup is automatic via the plugin's shutdown handler.
|
*/

uses(TestCase::class, BridgeTrait::class)
    ->beforeAll(fn () => Bridge::setDefault('http://localhost:3000')
        ->serve('npm run dev', cwd: dirname(__DIR__, 2).'/nuxt-app')
        ->readyWhen('Local:.*localhost:3000'))
    ->in('Browser');
