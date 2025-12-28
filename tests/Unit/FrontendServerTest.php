<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\FrontendServer;
use TestFlowLabs\PestPluginBridge\FrontendDefinition;

describe('FrontendServer', function (): void {
    describe('constructor', function (): void {
        test('creates server from definition', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('echo "ready"');

            $server = new FrontendServer($definition);

            expect($server)->toBeInstanceOf(FrontendServer::class);
            expect($server->isRunning())->toBeFalse();
        });
    });

    describe('isRunning', function (): void {
        test('returns false when not started', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('echo "ready"');

            $server = new FrontendServer($definition);

            expect($server->isRunning())->toBeFalse();
        });

        test('returns false for definition without serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            $server = new FrontendServer($definition);

            expect($server->isRunning())->toBeFalse();
        });
    });

    describe('start', function (): void {
        test('does nothing when no serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            $server = new FrontendServer($definition);
            $server->start();

            expect($server->isRunning())->toBeFalse();
        });

        // Note: Full start() testing requires pest-plugin-browser's ServerManager
        // to be initialized, which happens during browser test execution.
        // Integration tests in playground verify the full lifecycle.
    });

    describe('stop', function (): void {
        test('does nothing when not started', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('echo "ready"');

            $server = new FrontendServer($definition);
            $server->stop();

            expect($server->isRunning())->toBeFalse();
        });

        test('can be called multiple times safely', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('echo "ready"');

            $server = new FrontendServer($definition);
            $server->stop();
            $server->stop();
            $server->stop();

            expect($server->isRunning())->toBeFalse();
        });
    });

    describe('definition properties', function (): void {
        test('uses definition serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev', cwd: '/path/to/frontend');

            $server = new FrontendServer($definition);

            // Server is created but not started
            expect($server->isRunning())->toBeFalse();
        });

        test('uses definition ready pattern', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev')->readyWhen('VITE.*ready');

            $server = new FrontendServer($definition);

            expect($server->isRunning())->toBeFalse();
        });
    });
});
