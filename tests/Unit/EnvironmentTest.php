<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Environment;

describe('Environment', function (): void {
    describe('isCI', function (): void {
        test('returns true when CI env var is set', function (): void {
            // Store original value
            $original = getenv('CI');

            // Set CI env var
            putenv('CI=true');

            expect(Environment::isCI())->toBeTrue();

            // Restore
            if ($original !== false) {
                putenv("CI={$original}");
            } else {
                putenv('CI');
            }
        });

        test('returns true when GITHUB_ACTIONS is set', function (): void {
            $original = getenv('GITHUB_ACTIONS');

            putenv('GITHUB_ACTIONS=true');

            expect(Environment::isCI())->toBeTrue();

            if ($original !== false) {
                putenv("GITHUB_ACTIONS={$original}");
            } else {
                putenv('GITHUB_ACTIONS');
            }
        });

        test('returns true when GITLAB_CI is set', function (): void {
            $original = getenv('GITLAB_CI');

            putenv('GITLAB_CI=true');

            expect(Environment::isCI())->toBeTrue();

            if ($original !== false) {
                putenv("GITLAB_CI={$original}");
            } else {
                putenv('GITLAB_CI');
            }
        });

        test('returns true when CIRCLECI is set', function (): void {
            $original = getenv('CIRCLECI');

            putenv('CIRCLECI=true');

            expect(Environment::isCI())->toBeTrue();

            if ($original !== false) {
                putenv("CIRCLECI={$original}");
            } else {
                putenv('CIRCLECI');
            }
        });

        test('returns true when JENKINS_URL is set', function (): void {
            $original = getenv('JENKINS_URL');

            putenv('JENKINS_URL=http://jenkins.example.com');

            expect(Environment::isCI())->toBeTrue();

            if ($original !== false) {
                putenv("JENKINS_URL={$original}");
            } else {
                putenv('JENKINS_URL');
            }
        });
    });

    describe('isLocal', function (): void {
        test('returns opposite of isCI', function (): void {
            // When no CI vars are set
            $ciVars    = ['CI', 'GITHUB_ACTIONS', 'GITLAB_CI', 'CIRCLECI', 'TRAVIS', 'JENKINS_URL'];
            $originals = [];

            foreach ($ciVars as $var) {
                $originals[$var] = getenv($var);
                putenv($var);
            }

            expect(Environment::isLocal())->toBeTrue();
            expect(Environment::isCI())->toBeFalse();

            // When CI is set
            putenv('CI=true');
            expect(Environment::isLocal())->toBeFalse();
            expect(Environment::isCI())->toBeTrue();

            // Restore
            putenv('CI');
            foreach ($originals as $var => $value) {
                if ($value !== false) {
                    putenv("{$var}={$value}");
                }
            }
        });
    });
});
