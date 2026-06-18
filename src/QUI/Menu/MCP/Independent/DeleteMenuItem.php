<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\DeleteMenuItem
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Handler;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class DeleteMenuItem extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $menuId, string $identifier): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    $Menu = Handler::getMenu($menuId);
                    $data = self::deleteItemFromData(
                        self::getMenuDataTree($Menu),
                        $identifier
                    );

                    return [
                        'menu' => self::saveMenuData($Menu, $data)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_item_delete',
            description: 'Deletes one item from an independent QUIQQER menu by identifier. The menu is only saved after validation succeeds.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['menuId', 'identifier'],
                'properties' => [
                    'menuId' => ['type' => 'integer', 'description' => 'Menu ID.'],
                    'identifier' => ['type' => 'string', 'description' => 'Stable menu item identifier.']
                ]
            ]
        );
    }
}
