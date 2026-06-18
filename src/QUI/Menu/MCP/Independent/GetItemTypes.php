<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\GetItemTypes
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class GetItemTypes extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    return [
                        'itemTypes' => self::parseItemTypes()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_item_types',
            description: 'Lists the item types that can be used in independent QUIQQER menus.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => []
            ]
        );
    }
}
