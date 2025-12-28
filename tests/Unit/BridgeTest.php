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

describe('Bridge::url', function (): void {
    test('returns default URL when no name provided', function (): void {
        Bridge::add('http://localhost:3000');

        expect(Bridge::url())->toBe('http://localhost:3000');
    });

    test('returns named frontend URL', function (): void {
        Bridge::add('http://localhost:5173', 'admin');

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
        Bridge::add('http://localhost:3000');

        expect(Bridge::has())->toBeTrue();
    });

    test('returns false when default not configured', function (): void {
        expect(Bridge::has())->toBeFalse();
    });

    test('returns true when named frontend exists', function (): void {
        Bridge::add('http://localhost:5173', 'admin');

        expect(Bridge::has('admin'))->toBeTrue();
    });

    test('returns false when named frontend does not exist', function (): void {
        expect(Bridge::has('unknown'))->toBeFalse();
    });
});

describe('Bridge::buildUrl', function (): void {
    beforeEach(function (): void {
        Bridge::add('http://localhost:5173');
        Bridge::add('http://localhost:5174', 'admin');
    });

    test('builds URL with default frontend', function (): void {
        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });

    test('builds URL with named frontend', function (): void {
        expect(Bridge::buildUrl('/users', 'admin'))->toBe('http://localhost:5174/users');
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
        Bridge::add('http://localhost:5173/');

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
        Bridge::add('http://localhost:3000');
        Bridge::reset();

        expect(Bridge::has())->toBeFalse();
    });

    test('clears all named frontends', function (): void {
        Bridge::add('http://localhost:5173', 'admin');
        Bridge::add('http://localhost:5174', 'mobile');
        Bridge::reset();

        expect(Bridge::has('admin'))->toBeFalse();
        expect(Bridge::has('mobile'))->toBeFalse();
    });
});

describe('Bridge::add', function (): void {
    test('sets default frontend when no name provided', function (): void {
        Bridge::add('http://localhost:3000');

        expect(Bridge::url())->toBe('http://localhost:3000');
        expect(Bridge::has())->toBeTrue();
    });

    test('adds named frontend when name provided', function (): void {
        Bridge::add('http://localhost:5173', 'admin');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
        expect(Bridge::has('admin'))->toBeTrue();
    });

    test('returns FrontendDefinition for default frontend', function (): void {
        $definition = Bridge::add('http://localhost:3000');

        expect($definition)->toBeInstanceOf(FrontendDefinition::class);
        expect($definition->url)->toBe('http://localhost:3000');
        expect($definition->name)->toBeNull();
    });

    test('returns FrontendDefinition for named frontend', function (): void {
        $definition = Bridge::add('http://localhost:5173', 'admin');

        expect($definition)->toBeInstanceOf(FrontendDefinition::class);
        expect($definition->url)->toBe('http://localhost:5173');
        expect($definition->name)->toBe('admin');
    });

    test('supports fluent chaining with serve', function (): void {
        $definition = Bridge::add('http://localhost:3000')
            ->serve('npm run dev', cwd: '../frontend');

        expect($definition->hasServeCommand())->toBeTrue();
        expect($definition->getServeCommand())->toBe('npm run dev');
        expect($definition->getWorkingDirectory())->toBe('../frontend');
    });

    test('adds multiple named frontends', function (): void {
        Bridge::add('http://localhost:5173', 'admin');
        Bridge::add('http://localhost:5174', 'mobile');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
        expect(Bridge::url('mobile'))->toBe('http://localhost:5174');
    });

    test('throws exception for invalid URL', function (): void {
        Bridge::add('not-a-valid-url');
    })->throws(InvalidArgumentException::class, 'Invalid URL: not-a-valid-url');

    test('throws exception for empty URL', function (): void {
        Bridge::add('');
    })->throws(InvalidArgumentException::class);

    test('throws exception for empty name', function (): void {
        Bridge::add('http://localhost:3000', '');
    })->throws(InvalidArgumentException::class, 'Frontend name cannot be empty');
});

describe('Bridge child frontends', function (): void {
    test('child method registers a child frontend with correct URL', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('/analytics', 'analytics');

        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
    });

    test('child method supports chaining multiple children', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('/analytics', 'analytics')
            ->child('/reports', 'reports');

        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
        expect(Bridge::url('reports'))->toBe('http://localhost:3001/reports');
    });

    test('child method supports full fluent chain', function (): void {
        $definition = Bridge::add('http://localhost:3001', 'admin')
            ->child('/analytics', 'analytics')
            ->child('/reports', 'reports')
            ->serve('npm run dev', cwd: '../admin-frontend')
            ->readyWhen('VITE.*ready');

        expect(Bridge::url('admin'))->toBe('http://localhost:3001');
        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
        expect(Bridge::url('reports'))->toBe('http://localhost:3001/reports');
        expect($definition->hasServeCommand())->toBeTrue();
        expect($definition->getServeCommand())->toBe('npm run dev');
        expect($definition->getReadyPattern())->toBe('VITE.*ready');
    });

    test('child normalizes paths with leading slash', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('/analytics', 'analytics');

        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
    });

    test('child normalizes paths without leading slash', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('analytics', 'analytics');

        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
    });

    test('child handles parent URL with trailing slash', function (): void {
        Bridge::add('http://localhost:3001/', 'admin')
            ->child('/analytics', 'analytics');

        expect(Bridge::url('analytics'))->toBe('http://localhost:3001/analytics');
    });

    test('child works with default frontend', function (): void {
        Bridge::add('http://localhost:3000')
            ->child('/settings', 'settings');

        expect(Bridge::url())->toBe('http://localhost:3000');
        expect(Bridge::url('settings'))->toBe('http://localhost:3000/settings');
    });

    test('registerChild is internal and creates correct URL', function (): void {
        Bridge::registerChild('http://localhost:3001', '/deep/path', 'deep');

        expect(Bridge::url('deep'))->toBe('http://localhost:3001/deep/path');
    });

    test('child with deep path nesting', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('/level1/level2/level3', 'deep');

        expect(Bridge::url('deep'))->toBe('http://localhost:3001/level1/level2/level3');
    });

    test('child overwrites parent when using same name', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('/path', 'admin');

        expect(Bridge::url('admin'))->toBe('http://localhost:3001/path');
    });

    test('child with empty path creates alias to parent', function (): void {
        Bridge::add('http://localhost:3001', 'admin')
            ->child('', 'alias');

        expect(Bridge::url('alias'))->toBe('http://localhost:3001/');
    });
});

describe('Bridge::add edge cases', function (): void {
    test('overwrites existing default frontend', function (): void {
        Bridge::add('http://localhost:3000');
        Bridge::add('http://localhost:5173');

        expect(Bridge::url())->toBe('http://localhost:5173');
    });

    test('overwrites existing named frontend', function (): void {
        Bridge::add('http://localhost:3000', 'admin');
        Bridge::add('http://localhost:5173', 'admin');

        expect(Bridge::url('admin'))->toBe('http://localhost:5173');
    });

    test('handles URL with existing path segment', function (): void {
        Bridge::add('http://localhost:3000/app');

        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:3000/app/dashboard');
    });
});

describe('Bridge::fake', function (): void {
    afterEach(function (): void {
        Bridge::clearFakes();
    });

    test('registers fake HTTP responses', function (): void {
        Bridge::fake([
            'https://api.stripe.com/*' => [
                'status' => 200,
                'body'   => ['id' => 'ch_123', 'status' => 'succeeded'],
            ],
        ]);

        expect(Bridge::hasFakes())->toBeTrue();
    });

    test('getFakes returns registered configuration', function (): void {
        $fakes = [
            'https://api.stripe.com/*' => [
                'status' => 200,
                'body'   => ['id' => 'ch_123'],
            ],
            'https://api.sendgrid.com/*' => [
                'status' => 202,
                'body'   => ['message' => 'queued'],
            ],
        ];

        Bridge::fake($fakes);

        expect(Bridge::getFakes())->toBe($fakes);
    });

    test('hasFakes returns false when no fakes registered', function (): void {
        expect(Bridge::hasFakes())->toBeFalse();
    });

    test('getFakes returns empty array when no fakes registered', function (): void {
        expect(Bridge::getFakes())->toBe([]);
    });

    test('clearFakes removes registered fakes', function (): void {
        Bridge::fake([
            'https://api.stripe.com/*' => ['status' => 200],
        ]);

        expect(Bridge::hasFakes())->toBeTrue();

        Bridge::clearFakes();

        expect(Bridge::hasFakes())->toBeFalse();
        expect(Bridge::getFakes())->toBe([]);
    });

    test('reset also clears fakes', function (): void {
        Bridge::fake([
            'https://api.stripe.com/*' => ['status' => 200],
        ]);

        expect(Bridge::hasFakes())->toBeTrue();

        Bridge::reset();

        expect(Bridge::hasFakes())->toBeFalse();
    });

    test('getFakeConfigPath returns path in temp directory', function (): void {
        $path = Bridge::getFakeConfigPath();

        expect($path)->toStartWith(sys_get_temp_dir());
        expect($path)->toContain('bridge_http_fakes.json');
    });

    test('fake writes config to file', function (): void {
        Bridge::fake([
            'https://api.example.com/*' => ['status' => 200],
        ]);

        $path = Bridge::getFakeConfigPath();

        expect(file_exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('api.example.com');
    });

    test('fake supports all response options', function (): void {
        $fakes = [
            'https://api.stripe.com/v1/charges' => [
                'status'  => 201,
                'body'    => ['id' => 'ch_123', 'amount' => 1000],
                'headers' => ['X-Request-Id' => 'req_abc123'],
            ],
        ];

        Bridge::fake($fakes);

        $retrieved = Bridge::getFakes();

        expect($retrieved['https://api.stripe.com/v1/charges']['status'])->toBe(201);
        expect($retrieved['https://api.stripe.com/v1/charges']['body']['amount'])->toBe(1000);
        expect($retrieved['https://api.stripe.com/v1/charges']['headers']['X-Request-Id'])->toBe('req_abc123');
    });

    test('fake overwrites previous fakes', function (): void {
        Bridge::fake([
            'https://api.stripe.com/*' => ['status' => 200],
        ]);

        Bridge::fake([
            'https://api.sendgrid.com/*' => ['status' => 202],
        ]);

        $fakes = Bridge::getFakes();

        expect($fakes)->toHaveKey('https://api.sendgrid.com/*');
        expect($fakes)->not->toHaveKey('https://api.stripe.com/*');
    });

    test('clearFakes is safe to call when no fakes exist', function (): void {
        expect(Bridge::hasFakes())->toBeFalse();

        // Should not throw
        Bridge::clearFakes();

        expect(Bridge::hasFakes())->toBeFalse();
    });
});
