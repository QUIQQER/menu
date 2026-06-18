<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\UpdateMenuItem
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Handler;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class UpdateMenuItem extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $menuId, string $identifier, array $patch): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    $Menu = Handler::getMenu($menuId);
                    $data = self::updateItemInData(
                        self::getMenuDataTree($Menu),
                        $identifier,
                        $patch
                    );

                    return [
                        'menu' => self::saveMenuData($Menu, $data)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_item_update',
            description: 'Updates one item in an independent QUIQQER menu by identifier. Invalid data is rejected and the menu is not saved.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['menuId', 'identifier', 'patch'],
                'properties' => [
                    'menuId' => ['type' => 'integer', 'description' => 'Menu ID.'],
                    'identifier' => ['type' => 'string', 'description' => 'Stable menu item identifier.'],
                    'patch' => [
                        'type' => 'object',
                        'description' => 'Fields to update: type, title, icon, data, children. Use quiqqer_menu_item_types for schemas.'
                    ]
                ]
            ]
        );
    }
}
