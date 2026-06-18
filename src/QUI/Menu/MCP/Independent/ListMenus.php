<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\ListMenus
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Handler;
use QUI\Menu\Independent\Menu;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class ListMenus extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    return [
                        'menus' => array_map(
                            static fn(Menu $Menu): array => self::parseMenu($Menu),
                            Handler::getList()
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_list',
            description: 'Lists independent QUIQQER menus.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => []
            ]
        );
    }
}
