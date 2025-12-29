<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\ServerMarker;

beforeEach(function (): void {
    // Clean up any test markers before each test
    $testPorts = [3000, 3001, 5173, 8080];
    foreach ($testPorts as $port) {
        ServerMarker::delete($port);
    }
});

afterEach(function (): void {
    // Clean up any test markers after each test
    $testPorts = [3000, 3001, 5173, 8080];
    foreach ($testPorts as $port) {
        ServerMarker::delete($port);
    }
});

describe('ServerMarker', function (): void {
    describe('write and read', function (): void {
        test('writes and reads marker file', function (): void {
            ServerMarker::write(3000, '/path/to/frontend', 'npm run dev', 12345);

            $marker = ServerMarker::read(3000);

            expect($marker)->not->toBeNull();
            expect($marker['port'])->toBe(3000);
            expect($marker['cwd'])->toBe('/path/to/frontend');
            expect($marker['command'])->toBe('npm run dev');
            expect($marker['pid'])->toBe(12345);
            expect($marker['started_at'])->toBeInt();
        });

        test('returns null for non-existent marker', function (): void {
            $marker = ServerMarker::read(9999);

            expect($marker)->toBeNull();
        });

        test('normalizes paths for comparison', function (): void {
            // Write with a path that can be normalized
            $cwd = sys_get_temp_dir();
            ServerMarker::write(3001, $cwd, 'npm run dev', 12345);

            $marker = ServerMarker::read(3001);

            expect($marker['cwd'])->toBe(realpath($cwd));
        });
    });

    describe('delete', function (): void {
        test('deletes existing marker file', function (): void {
            ServerMarker::write(5173, '/path', 'npm run dev', 123);

            expect(ServerMarker::read(5173))->not->toBeNull();

            ServerMarker::delete(5173);

            expect(ServerMarker::read(5173))->toBeNull();
        });

        test('does not error when deleting non-existent marker', function (): void {
            // Should not throw
            ServerMarker::delete(9999);

            expect(true)->toBeTrue();
        });
    });

    describe('verify', function (): void {
        test('returns none when no marker exists', function (): void {
            $result = ServerMarker::verify(9999, '/any/path');

            expect($result)->toBe('none');
        });

        test('returns mismatch when CWD differs', function (): void {
            ServerMarker::write(3000, '/path/to/project-a', 'npm run dev', getmypid());

            $result = ServerMarker::verify(3000, '/path/to/project-b');

            expect($result)->toBe('mismatch');
        });

        test('returns match when CWD matches and PID is alive', function (): void {
            // Use current process PID (guaranteed to be running)
            $pid = getmypid();
            $cwd = sys_get_temp_dir();

            ServerMarker::write(3000, $cwd, 'npm run dev', $pid);

            $result = ServerMarker::verify(3000, $cwd);

            expect($result)->toBe('match');
        });

        test('returns stale when CWD matches but PID is dead', function (): void {
            $cwd = sys_get_temp_dir();

            // Use a PID that's definitely not running (very high number)
            ServerMarker::write(3000, $cwd, 'npm run dev', 999999999);

            $result = ServerMarker::verify(3000, $cwd);

            expect($result)->toBe('stale');
        });

        test('cleans up stale marker file', function (): void {
            $cwd = sys_get_temp_dir();

            ServerMarker::write(3000, $cwd, 'npm run dev', 999999999);

            // Verify should detect stale and clean up
            ServerMarker::verify(3000, $cwd);

            // Marker should be deleted
            expect(ServerMarker::read(3000))->toBeNull();
        });
    });

    describe('getMarkerCwd', function (): void {
        test('returns CWD from marker file', function (): void {
            ServerMarker::write(3000, '/path/to/frontend', 'npm run dev', 12345);

            $cwd = ServerMarker::getMarkerCwd(3000);

            expect($cwd)->toBe('/path/to/frontend');
        });

        test('returns null for non-existent marker', function (): void {
            $cwd = ServerMarker::getMarkerCwd(9999);

            expect($cwd)->toBeNull();
        });
    });

    describe('getMarkerPath', function (): void {
        test('returns path in system temp directory', function (): void {
            $path = ServerMarker::getMarkerPath(5173);

            expect($path)->toStartWith(sys_get_temp_dir());
            expect($path)->toContain('bridge_server_5173');
            expect($path)->toEndWith('.json');
        });

        test('returns unique paths for different ports', function (): void {
            $path3000 = ServerMarker::getMarkerPath(3000);
            $path5173 = ServerMarker::getMarkerPath(5173);

            expect($path3000)->not->toBe($path5173);
        });
    });

    describe('corrupted marker handling', function (): void {
        test('handles corrupted JSON gracefully', function (): void {
            // Manually write corrupted marker file
            $path = ServerMarker::getMarkerPath(8080);
            file_put_contents($path, 'not valid json {{{');

            $marker = ServerMarker::read(8080);

            expect($marker)->toBeNull();
        });

        test('deletes corrupted marker file on read', function (): void {
            // Manually write corrupted marker file
            $path = ServerMarker::getMarkerPath(8080);
            file_put_contents($path, 'corrupted');

            ServerMarker::read(8080);

            expect(file_exists($path))->toBeFalse();
        });
    });
});
