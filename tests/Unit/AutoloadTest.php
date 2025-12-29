<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\BridgeTrait;

describe('Autoload', function (): void {
    describe('BridgeTrait registration', function (): void {
        test('BridgeTrait has bridge method', function (): void {
            expect(trait_exists(BridgeTrait::class))->toBeTrue();
            expect(method_exists(BridgeTrait::class, 'bridge'))->toBeTrue();
        });

        test('BridgeTrait has generateBrowserMockScript method', function (): void {
            expect(method_exists(BridgeTrait::class, 'generateBrowserMockScript'))->toBeTrue();
        });
    });

    describe('plugin integration', function (): void {
        test('Autoload.php can be parsed without errors', function (): void {
            $autoloadPath = __DIR__.'/../../src/Autoload.php';

            expect(file_exists($autoloadPath))->toBeTrue();

            // Use php -l to check syntax without executing
            $output = [];
            $result = 0;
            exec('php -l '.escapeshellarg($autoloadPath).' 2>&1', $output, $result);

            expect($result)->toBe(0);
        });
    });
});
