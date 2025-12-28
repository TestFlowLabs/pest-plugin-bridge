<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\FrontendDefinition;

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

        test('has default ready pattern', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            expect($definition->getReadyPattern())->toBe('ready|localhost|started|listening');
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
    });
});
