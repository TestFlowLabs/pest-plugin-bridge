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

describe('Bridge::setDefault', function (): void {
    test('sets a valid URL', function (): void {
        Bridge::setDefault('http://localhost:3000');

        expect(Bridge::url())->toBe('http://localhost:3000');
    });

    test('returns FrontendDefinition', function (): void {
        $definition = Bridge::setDefault('http://localhost:3000');

        expect($definition)->toBeInstanceOf(FrontendDefinition::class);
        expect($definition->url)->toBe('http://localhost:3000');
        expect($definition->name)->toBeNull();
    });

    test('returned FrontendDefinition supports fluent chaining', function (): void {
        $definition = Bridge::setDefault('http://localhost:3000')
            ->serve('npm run dev', cwd: '../frontend');

        expect($definition->hasServeCommand())->toBeTrue();
        expect($definition->getServeCommand())->toBe('npm run dev');
        expect($definition->getWorkingDirectory())->toBe('../frontend');
    });

    test('sets a valid URL with port', function (): void {
        Bridge::setDefault('http://frontend.test:8080');

        expect(Bridge::url())->toBe('http://frontend.test:8080');
    });

    test('sets a valid HTTPS URL', function (): void {
        Bridge::setDefault('https://app.example.com');

        expect(Bridge::url())->toBe('https://app.example.com');
    });

    test('throws exception for invalid URL', function (): void {
        Bridge::setDefault('not-a-valid-url');
    })->throws(InvalidArgumentException::class, 'Invalid URL: not-a-valid-url');

    test('throws exception for empty URL', function (): void {
        Bridge::setDefault('');
    })->throws(InvalidArgumentException::class);
});

describe('Bridge::frontend', function (): void {
    test('adds a named frontend', function (): void {
        Bridge::frontend('admin', 'http://localhost:5173');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
    });

    test('returns FrontendDefinition', function (): void {
        $definition = Bridge::frontend('admin', 'http://localhost:5173');

        expect($definition)->toBeInstanceOf(FrontendDefinition::class);
        expect($definition->url)->toBe('http://localhost:5173');
        expect($definition->name)->toBe('admin');
    });

    test('returned FrontendDefinition supports fluent chaining', function (): void {
        $definition = Bridge::frontend('admin', 'http://localhost:5173')
            ->serve('npm run dev', cwd: '../admin-panel')
            ->readyWhen('VITE.*ready');

        expect($definition->hasServeCommand())->toBeTrue();
        expect($definition->getServeCommand())->toBe('npm run dev');
        expect($definition->getWorkingDirectory())->toBe('../admin-panel');
        expect($definition->getReadyPattern())->toBe('VITE.*ready');
    });

    test('adds multiple named frontends', function (): void {
        Bridge::frontend('admin', 'http://localhost:5173');
        Bridge::frontend('mobile', 'http://localhost:5174');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
        expect(Bridge::url('mobile'))->toBe('http://localhost:5174');
    });

    test('throws exception for empty name', function (): void {
        Bridge::frontend('', 'http://localhost:3000');
    })->throws(InvalidArgumentException::class, 'Frontend name cannot be empty');

    test('throws exception for invalid URL', function (): void {
        Bridge::frontend('admin', 'not-a-valid-url');
    })->throws(InvalidArgumentException::class);
});

describe('Bridge::url', function (): void {
    test('returns default URL when no name provided', function (): void {
        Bridge::setDefault('http://localhost:3000');

        expect(Bridge::url())->toBe('http://localhost:3000');
    });

    test('returns named frontend URL', function (): void {
        Bridge::frontend('admin', 'http://localhost:5173');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
    });

    test('throws exception when default not configured', function (): void {
        Bridge::url();
    })->throws(InvalidArgumentException::class, 'Default frontend not configured');

    test('throws exception when named frontend not configured', function (): void {
        Bridge::url('unknown');
    })->throws(InvalidArgumentException::class, "Frontend 'unknown' not configured");
});

describe('Bridge::has', function (): void {
    test('returns true when default is set', function (): void {
        Bridge::setDefault('http://localhost:3000');

        expect(Bridge::has())->toBeTrue();
    });

    test('returns false when default not configured', function (): void {
        expect(Bridge::has())->toBeFalse();
    });

    test('returns true when named frontend exists', function (): void {
        Bridge::frontend('admin', 'http://localhost:5173');

        expect(Bridge::has('admin'))->toBeTrue();
    });

    test('returns false when named frontend does not exist', function (): void {
        expect(Bridge::has('unknown'))->toBeFalse();
    });
});

describe('Bridge::buildUrl', function (): void {
    beforeEach(function (): void {
        Bridge::setDefault('http://localhost:5173');
        Bridge::frontend('admin', 'http://localhost:5174');
    });

    test('builds URL with default frontend', function (): void {
        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });

    test('builds URL with named frontend', function (): void {
        expect(Bridge::buildUrl('/users', 'admin'))->toBe('http://localhost:5174/users');
    });

    test('builds URL with leading slash path', function (): void {
        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });

    test('builds URL without leading slash path', function (): void {
        expect(Bridge::buildUrl('about'))->toBe('http://localhost:5173/about');
    });

    test('handles root path', function (): void {
        expect(Bridge::buildUrl('/'))->toBe('http://localhost:5173/');
    });

    test('handles empty path', function (): void {
        expect(Bridge::buildUrl(''))->toBe('http://localhost:5173/');
    });

    test('handles nested path', function (): void {
        expect(Bridge::buildUrl('/users/profile/settings'))->toBe('http://localhost:5173/users/profile/settings');
    });

    test('handles base URL with trailing slash', function (): void {
        Bridge::setDefault('http://localhost:5173/');

        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });

    test('throws exception when default not configured', function (): void {
        Bridge::reset();

        Bridge::buildUrl('/dashboard');
    })->throws(InvalidArgumentException::class, 'Default frontend not configured');

    test('throws exception when named frontend not configured', function (): void {
        Bridge::buildUrl('/dashboard', 'unknown');
    })->throws(InvalidArgumentException::class, "Frontend 'unknown' not configured");

    test('handles path with query string', function (): void {
        expect(Bridge::buildUrl('/search?q=test&page=1'))->toBe('http://localhost:5173/search?q=test&page=1');
    });

    test('handles path with fragment', function (): void {
        expect(Bridge::buildUrl('/docs#installation'))->toBe('http://localhost:5173/docs#installation');
    });

    test('handles path with query string and fragment', function (): void {
        expect(Bridge::buildUrl('/search?q=test#results'))->toBe('http://localhost:5173/search?q=test#results');
    });

    test('handles URL-encoded characters in path', function (): void {
        expect(Bridge::buildUrl('/files/my%20document.pdf'))->toBe('http://localhost:5173/files/my%20document.pdf');
    });
});

describe('Bridge::reset', function (): void {
    test('clears the default URL', function (): void {
        Bridge::setDefault('http://localhost:3000');
        Bridge::reset();

        expect(Bridge::has())->toBeFalse();
    });

    test('clears all named frontends', function (): void {
        Bridge::frontend('admin', 'http://localhost:5173');
        Bridge::frontend('mobile', 'http://localhost:5174');
        Bridge::reset();

        expect(Bridge::has('admin'))->toBeFalse();
        expect(Bridge::has('mobile'))->toBeFalse();
    });
});
