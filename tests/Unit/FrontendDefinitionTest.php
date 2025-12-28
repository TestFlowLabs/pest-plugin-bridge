<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\FrontendDefinition;

beforeEach(function (): void {
    Bridge::reset();
});

afterEach(function (): void {
    Bridge::reset();
});

describe('FrontendDefinition', function (): void {
    describe('constructor', function (): void {
        test('creates definition with URL only', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->url)->toBe('http://localhost:3000');
            expect($definition->name)->toBeNull();
        });

        test('creates definition with URL and name', function (): void {
            $definition = new FrontendDefinition('http://localhost:5173', 'admin');

            expect($definition->url)->toBe('http://localhost:5173');
            expect($definition->name)->toBe('admin');
        });
    });

    describe('serve', function (): void {
        test('sets serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            expect($definition->hasServeCommand())->toBeTrue();
            expect($definition->getServeCommand())->toBe('npm run dev');
        });

        test('sets serve command with working directory', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev', '../frontend');

            expect($definition->getServeCommand())->toBe('npm run dev');
            expect($definition->getWorkingDirectory())->toBe('../frontend');
        });

        test('sets serve command with named cwd parameter', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev', cwd: '/path/to/frontend');

            expect($definition->getWorkingDirectory())->toBe('/path/to/frontend');
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $result     = $definition->serve('npm run dev');

            expect($result)->toBe($definition);
        });
    });

    describe('readyWhen', function (): void {
        test('sets custom ready pattern', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->readyWhen('VITE.*ready');

            expect($definition->getReadyPattern())->toBe('VITE.*ready');
        });

        test('has default ready pattern that covers common frameworks', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            // Default pattern covers: Nuxt, Vite, Next.js, CRA, Angular
            expect($definition->getReadyPattern())->toBe('ready|localhost|started|listening|compiled|http://|https://');
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $result     = $definition->readyWhen('custom-pattern');

            expect($result)->toBe($definition);
        });
    });

    describe('hasServeCommand', function (): void {
        test('returns false when no serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->hasServeCommand())->toBeFalse();
        });

        test('returns true when serve command is set', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run dev');

            expect($definition->hasServeCommand())->toBeTrue();
        });
    });

    describe('warmup', function (): void {
        test('sets warmup delay in milliseconds', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->warmup(3000);

            expect($definition->getWarmupDelayMs())->toBe(3000);
        });

        test('has default warmup delay of zero', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->getWarmupDelayMs())->toBe(0);
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $result     = $definition->warmup(5000);

            expect($result)->toBe($definition);
        });
    });

    describe('envFile', function (): void {
        test('sets env file path', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->envFile('/path/to/.env.test');

            expect($definition->getEnvFilePath())->toBe('/path/to/.env.test');
        });

        test('has default env file path of null', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->getEnvFilePath())->toBeNull();
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $result     = $definition->envFile('/path/to/.env');

            expect($result)->toBe($definition);
        });
    });

    describe('env', function (): void {
        test('sets custom environment variables', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->env([
                'VITE_API_URL'      => '/api/',
                'VITE_ADMIN_API'    => '/api/admin/',
                'VITE_RETAILER_API' => '/api/retailer/',
            ]);

            expect($definition->getCustomEnvVars())->toBe([
                'VITE_API_URL'      => '/api/',
                'VITE_ADMIN_API'    => '/api/admin/',
                'VITE_RETAILER_API' => '/api/retailer/',
            ]);
        });

        test('has default empty custom env vars', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->getCustomEnvVars())->toBe([]);
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $result     = $definition->env(['VITE_API' => '/api/']);

            expect($result)->toBe($definition);
        });
    });

    describe('fluent chaining', function (): void {
        test('supports full fluent chain', function (): void {
            $definition = (new FrontendDefinition('http://localhost:3000', 'main'))
                ->serve('npm run dev', cwd: '../frontend')
                ->readyWhen('VITE.*ready');

            expect($definition->url)->toBe('http://localhost:3000');
            expect($definition->name)->toBe('main');
            expect($definition->getServeCommand())->toBe('npm run dev');
            expect($definition->getWorkingDirectory())->toBe('../frontend');
            expect($definition->getReadyPattern())->toBe('VITE.*ready');
        });

        test('supports full fluent chain with all options', function (): void {
            $definition = (new FrontendDefinition('http://localhost:5173', 'admin'))
                ->serve('npm run dev', cwd: '../frontend')
                ->readyWhen('VITE.*ready')
                ->warmup(3000)
                ->envFile('/path/to/.env.test')
                ->env([
                    'VITE_BACKEND_URL' => '/',
                    'VITE_ADMIN_API'   => '/v1/admin/',
                ]);

            expect($definition->url)->toBe('http://localhost:5173');
            expect($definition->name)->toBe('admin');
            expect($definition->getServeCommand())->toBe('npm run dev');
            expect($definition->getWorkingDirectory())->toBe('../frontend');
            expect($definition->getReadyPattern())->toBe('VITE.*ready');
            expect($definition->getWarmupDelayMs())->toBe(3000);
            expect($definition->getEnvFilePath())->toBe('/path/to/.env.test');
            expect($definition->getCustomEnvVars())->toBe([
                'VITE_BACKEND_URL' => '/',
                'VITE_ADMIN_API'   => '/v1/admin/',
            ]);
        });
    });

    describe('child', function (): void {
        test('registers child frontend with Bridge', function (): void {
            $definition = new FrontendDefinition('http://localhost:3001', 'admin');
            $definition->child('/analytics', 'analytics');

            expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
        });

        test('returns self for fluent chaining', function (): void {
            $definition = new FrontendDefinition('http://localhost:3001', 'admin');
            $result     = $definition->child('/analytics', 'analytics');

            expect($result)->toBe($definition);
        });

        test('supports multiple children', function (): void {
            $definition = new FrontendDefinition('http://localhost:3001', 'admin');
            $definition
                ->child('/analytics', 'analytics')
                ->child('/reports', 'reports')
                ->child('/settings', 'settings');

            expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
            expect(Bridge::url('reports'))->toBe('http://localhost:3001/reports');
            expect(Bridge::url('settings'))->toBe('http://localhost:3001/settings');
        });

        test('can be combined with other fluent methods', function (): void {
            $definition = (new FrontendDefinition('http://localhost:3001', 'admin'))
                ->child('/analytics', 'analytics')
                ->serve('npm run dev', cwd: '../admin')
                ->readyWhen('VITE.*ready')
                ->warmup(2000);

            expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
            expect($definition->getServeCommand())->toBe('npm run dev');
            expect($definition->getWorkingDirectory())->toBe('../admin');
            expect($definition->getReadyPattern())->toBe('VITE.*ready');
            expect($definition->getWarmupDelayMs())->toBe(2000);
        });
    });

    describe('method overwriting', function (): void {
        test('serve() overwrites previous serve command', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->serve('npm run old');
            $definition->serve('npm run new', cwd: '/new/path');

            expect($definition->getServeCommand())->toBe('npm run new');
            expect($definition->getWorkingDirectory())->toBe('/new/path');
        });

        test('readyWhen() overwrites previous pattern', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->readyWhen('old-pattern');
            $definition->readyWhen('new-pattern');

            expect($definition->getReadyPattern())->toBe('new-pattern');
        });

        test('env() overwrites previous env vars', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->env(['OLD' => 'value']);
            $definition->env(['NEW' => 'value']);

            expect($definition->getCustomEnvVars())->toBe(['NEW' => 'value']);
        });

        test('warmup() overwrites previous delay', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $definition->warmup(1000);
            $definition->warmup(5000);

            expect($definition->getWarmupDelayMs())->toBe(5000);
        });
    });
});
