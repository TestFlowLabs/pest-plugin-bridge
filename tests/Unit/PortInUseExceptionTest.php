<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Exceptions\PortInUseException;

describe('PortInUseException', function (): void {
    test('contains port number in message', function (): void {
        $exception = new PortInUseException(5173, 'http://localhost:5173');

        expect($exception->getMessage())->toContain('Port 5173 is already in use');
    });

    test('contains URL in message', function (): void {
        $exception = new PortInUseException(3000, 'http://localhost:3000');

        expect($exception->getMessage())->toContain('http://localhost:3000');
    });

    test('contains helpful options', function (): void {
        $exception = new PortInUseException(5173, 'http://localhost:5173');

        expect($exception->getMessage())
            ->toContain('lsof -ti:5173')
            ->toContain('reuseExistingServer()')
            ->toContain('--strictPort');
    });

    test('extends RuntimeException', function (): void {
        $exception = new PortInUseException(5173, 'http://localhost:5173');

        expect($exception)->toBeInstanceOf(RuntimeException::class);
    });
});
