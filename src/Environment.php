<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Environment detection utilities.
 *
 * Provides helpers for detecting the runtime environment,
 * particularly for distinguishing CI from local development.
 */
final class Environment
{
    /**
     * Known CI environment variable names.
     *
     * These are set by various CI/CD platforms to indicate
     * the code is running in a continuous integration environment.
     */
    private const array CI_VARIABLES = [
        'CI',                    // Generic, GitHub Actions, GitLab CI, CircleCI, Travis
        'GITHUB_ACTIONS',        // GitHub Actions
        'GITLAB_CI',             // GitLab CI
        'CIRCLECI',              // CircleCI
        'TRAVIS',                // Travis CI
        'JENKINS_URL',           // Jenkins
        'BUILDKITE',             // Buildkite
        'TEAMCITY_VERSION',      // TeamCity
        'TF_BUILD',              // Azure Pipelines
        'BITBUCKET_BUILD_NUMBER', // Bitbucket Pipelines
        'CODEBUILD_BUILD_ID',    // AWS CodeBuild
        'DRONE',                 // Drone CI
        'APPVEYOR',              // AppVeyor
        'SEMAPHORE',             // Semaphore CI
        'WERCKER',               // Wercker
    ];

    /**
     * Detect if running in a CI environment.
     *
     * Checks for common CI environment variables set by
     * various CI/CD platforms.
     */
    public static function isCI(): bool
    {
        foreach (self::CI_VARIABLES as $variable) {
            if (self::hasEnv($variable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if running in local development.
     */
    public static function isLocal(): bool
    {
        return !self::isCI();
    }

    /**
     * Check if an environment variable is set and non-empty.
     */
    private static function hasEnv(string $name): bool
    {
        $value = getenv($name);

        return $value !== false && $value !== '';
    }
}
