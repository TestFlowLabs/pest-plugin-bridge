<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\FrontendManager;
use TestFlowLabs\PestPluginBridge\FrontendDefinition;

beforeEach(function (): void {
    FrontendManager::reset();
});

afterEach(function (): void {
    FrontendManager::reset();
});

describe('FrontendManager', function (): void {
    describe('register', function (): void {
        test('registers definition without serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            FrontendManager::register($definition);

            // No servers created for definitions without serve commands
            expect(FrontendManager::hasServers())->toBeFalse();
        });

        test('registers definition with serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::register($definition);

            expect(FrontendManager::hasServers())->toBeTrue();
        });

        test('registers multiple definitions', function (): void {
            $definition1 = new FrontendDefinition('http://localhost:3000');
            $definition1->serve('npm run dev');

            $definition2 = new FrontendDefinition('http://localhost:5173', 'admin');
            $definition2->serve('npm run dev');

            FrontendManager::register($definition1);
            FrontendManager::register($definition2);

            expect(FrontendManager::hasServers())->toBeTrue();
        });
    });

    describe('hasServers', function (): void {
        test('returns false when no servers registered', function (): void {
            expect(FrontendManager::hasServers())->toBeFalse();
        });

        test('returns true when servers registered', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::register($definition);

            expect(FrontendManager::hasServers())->toBeTrue();
        });

        test('returns false after reset', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::register($definition);
            FrontendManager::reset();

            expect(FrontendManager::hasServers())->toBeFalse();
        });
    });

    describe('reset', function (): void {
        test('clears registered servers', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::register($definition);
            FrontendManager::reset();

            expect(FrontendManager::hasServers())->toBeFalse();
        });

        test('can be called multiple times safely', function (): void {
            FrontendManager::reset();
            FrontendManager::reset();

            expect(FrontendManager::hasServers())->toBeFalse();
        });
    });

    describe('startAll', function (): void {
        test('is idempotent', function (): void {
            // Calling startAll multiple times should be safe
            FrontendManager::startAll();
            FrontendManager::startAll();

            expect(FrontendManager::hasServers())->toBeFalse();
        });

        test('resets started state after reset', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            FrontendManager::register($definition);

            FrontendManager::startAll();
            FrontendManager::reset();

            // After reset, hasServers should be false
            expect(FrontendManager::hasServers())->toBeFalse();
        });
    });
});
