<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\FrontendDefinition;
use TestFlowLabs\PestPluginBridge\FrontendManager;

beforeEach(function (): void {
    FrontendManager::reset();
});

afterEach(function (): void {
    FrontendManager::reset();
});

describe('FrontendManager', function (): void {
    describe('instance', function (): void {
        test('returns singleton instance', function (): void {
            $instance1 = FrontendManager::instance();
            $instance2 = FrontendManager::instance();

            expect($instance1)->toBe($instance2);
        });

        test('returns new instance after reset', function (): void {
            $instance1 = FrontendManager::instance();
            FrontendManager::reset();
            $instance2 = FrontendManager::instance();

            expect($instance1)->not->toBe($instance2);
        });
    });

    describe('register', function (): void {
        test('registers definition without serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            FrontendManager::instance()->register($definition);

            // No servers created for definitions without serve commands
            expect(FrontendManager::instance()->hasServers())->toBeFalse();
        });

        test('registers definition with serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::instance()->register($definition);

            expect(FrontendManager::instance()->hasServers())->toBeTrue();
        });

        test('registers multiple definitions', function (): void {
            $definition1 = new FrontendDefinition('http://localhost:3000');
            $definition1->serve('npm run dev');

            $definition2 = new FrontendDefinition('http://localhost:5173', 'admin');
            $definition2->serve('npm run dev');

            FrontendManager::instance()->register($definition1);
            FrontendManager::instance()->register($definition2);

            expect(FrontendManager::instance()->hasServers())->toBeTrue();
        });
    });

    describe('hasServers', function (): void {
        test('returns false when no servers registered', function (): void {
            expect(FrontendManager::instance()->hasServers())->toBeFalse();
        });

        test('returns true when servers registered', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::instance()->register($definition);

            expect(FrontendManager::instance()->hasServers())->toBeTrue();
        });

        test('returns false after reset', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::instance()->register($definition);
            FrontendManager::reset();

            expect(FrontendManager::instance()->hasServers())->toBeFalse();
        });
    });

    describe('reset', function (): void {
        test('clears registered servers', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            FrontendManager::instance()->register($definition);
            FrontendManager::reset();

            expect(FrontendManager::instance()->hasServers())->toBeFalse();
        });

        test('resets singleton instance', function (): void {
            $instance = FrontendManager::instance();
            FrontendManager::reset();

            expect(FrontendManager::instance())->not->toBe($instance);
        });
    });
});
