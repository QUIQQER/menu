<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\DeleteMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Factory;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class DeleteMenu extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $id): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();
                    Factory::deleteMenu($id, Server::getRequestUser());

                    return [
                        'deleted' => true,
                        'id' => $id
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_delete',
            description: 'Deletes an independent QUIQQER menu. Requires menu MCP and menu delete permissions.',
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
