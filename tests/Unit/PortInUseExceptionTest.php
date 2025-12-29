<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Exceptions\PortInUseException;

describe('PortInUseException', function (): void {
    describe('differentApplication', function (): void {
        test('contains port and both cwds in message', function (): void {
            $exception = PortInUseException::differentApplication(
                5173,
                'http://localhost:5173',
                '/path/to/other-project'
            );

            expect($exception->getMessage())
                ->toContain('Port 5173 is in use by a different application')
                ->toContain('http://localhost:5173')
                ->toContain('/path/to/other-project');
        });

        test('suggests stopping other application', function (): void {
            $exception = PortInUseException::differentApplication(
                3000,
                'http://localhost:3000',
                '/other/path'
            );

            expect($exception->getMessage())
                ->toContain('Stop the other application');
        });
    });

    describe('unknownProcess', function (): void {
        test('contains port and url in message', function (): void {
            $exception = PortInUseException::unknownProcess(5173, 'http://localhost:5173');

            expect($exception->getMessage())
                ->toContain('Port 5173 is in use by an unknown process')
                ->toContain('http://localhost:5173');
        });

        test('contains helpful commands', function (): void {
            $exception = PortInUseException::unknownProcess(5173, 'http://localhost:5173');

            expect($exception->getMessage())
                ->toContain('lsof -ti:5173')
                ->toContain('trustExistingServer()')
                ->toContain('--strictPort');
        });

        test('explains Bridge did not start the process', function (): void {
            $exception = PortInUseException::unknownProcess(3000, 'http://localhost:3000');

            expect($exception->getMessage())
                ->toContain("Bridge didn't start");
        });
    });

    describe('staleMarker', function (): void {
        test('contains port in message', function (): void {
            $exception = PortInUseException::staleMarker(5173, 'http://localhost:5173');

            expect($exception->getMessage())
                ->toContain('Port 5173 has a stale marker')
                ->toContain('http://localhost:5173');
        });

        test('explains server died and port was reused', function (): void {
            $exception = PortInUseException::staleMarker(3000, 'http://localhost:3000');

            expect($exception->getMessage())
                ->toContain('server died')
                ->toContain('something else is now using the port');
        });

        test('contains helpful kill command', function (): void {
            $exception = PortInUseException::staleMarker(5173, 'http://localhost:5173');

            expect($exception->getMessage())
                ->toContain('lsof -ti:5173');
        });
    });

    test('all factory methods extend RuntimeException', function (): void {
        expect(PortInUseException::differentApplication(5173, 'http://localhost:5173', '/path'))
            ->toBeInstanceOf(RuntimeException::class);

        expect(PortInUseException::unknownProcess(5173, 'http://localhost:5173'))
            ->toBeInstanceOf(RuntimeException::class);

        expect(PortInUseException::staleMarker(5173, 'http://localhost:5173'))
            ->toBeInstanceOf(RuntimeException::class);
    });
});
