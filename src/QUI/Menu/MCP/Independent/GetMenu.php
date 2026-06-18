<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\GetMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Handler;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class GetMenu extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $id): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    return [
                        'menu' => self::parseMenu(Handler::getMenu($id), true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_get',
            description: 'Returns an independent QUIQQER menu including its item tree.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['id'],
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Menu ID.']
                ]
            ]
        );
    }
}
