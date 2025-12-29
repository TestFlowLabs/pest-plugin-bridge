<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Laravel\BridgeHttpFakeMiddleware;

describe('BridgeHttpFakeMiddleware', function (): void {
    describe('class structure', function (): void {
        test('class exists', function (): void {
            expect(class_exists(BridgeHttpFakeMiddleware::class))->toBeTrue();
        });

        test('class is final', function (): void {
            $reflection = new ReflectionClass(BridgeHttpFakeMiddleware::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        test('has handle method', function (): void {
            expect(method_exists(BridgeHttpFakeMiddleware::class, 'handle'))->toBeTrue();
        });

        test('can be instantiated', function (): void {
            $middleware = new BridgeHttpFakeMiddleware();

            expect($middleware)->toBeInstanceOf(BridgeHttpFakeMiddleware::class);
        });
    });

    describe('handle method signature', function (): void {
        test('handle method accepts Request and Closure', function (): void {
            $reflection = new ReflectionMethod(BridgeHttpFakeMiddleware::class, 'handle');
            $parameters = $reflection->getParameters();

            expect($parameters)->toHaveCount(2);
            expect($parameters[0]->getName())->toBe('request');
            expect($parameters[1]->getName())->toBe('next');
        });

        test('handle method returns Response type', function (): void {
            $reflection = new ReflectionMethod(BridgeHttpFakeMiddleware::class, 'handle');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('Symfony\Component\HttpFoundation\Response');
        });
    });
});
