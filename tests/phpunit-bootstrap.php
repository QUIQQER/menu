<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

$isolatedBootstrap = __DIR__ . '/../../core/tests/runtime-bootstrap.php';
if (getenv('GITLAB_CI') !== 'true' && is_file($isolatedBootstrap)) {
    require_once $isolatedBootstrap;
} else {
    require_once __DIR__ . '/../../../../bootstrap.php';
}
require_once __DIR__ . '/stubs/Mcp/Server/Builder.php';
require_once __DIR__ . '/stubs/Mcp/Schema/Result/CallToolResult.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/ProviderInterface.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/Server.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/ToolHelper.php';
require_once __DIR__ . '/stubs/QUI/MCP/ToolInterface.php';
require_once __DIR__ . '/QUI/Menu/MCP/MenuMcpToolTestAccessor.php';
