<?php

declare(strict_types=1);

use Pest\Plugin;
use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;

Plugin::uses(BridgeTrait::class);

// Register shutdown handler to stop frontend servers and reset Bridge state
register_shutdown_function(function (): void {
    Bridge::reset();
});
