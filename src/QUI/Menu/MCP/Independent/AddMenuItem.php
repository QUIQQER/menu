<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\AddMenuItem
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Handler;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class AddMenuItem extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $menuId,
                array $item,
                string $placement = 'root',
                string | null $referenceIdentifier = null,
                int | null $position = null
            ): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    $Menu = Handler::getMenu($menuId);
                    $data = self::addItemToData(
                        self::getMenuDataTree($Menu),
                        $item,
                        $placement,
                        $referenceIdentifier,
                        $position
                    );

                    return [
                        'menu' => self::saveMenuData($Menu, $data)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_menu_item_add',
            description: 'Adds an item to an independent QUIQQER menu. Placement can be root, child, before, or after. Invalid data is rejected and the menu is not saved.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['menuId', 'item'],
                'properties' => [
                    'menuId' => ['type' => 'integer', 'description' => 'Menu ID.'],
                    'item' => ['type' => 'object', 'description' => 'Menu item. Use quiqqer_menu_item_types for schemas and examples.'],
                    'placement' => [
                        'type' => 'string',
                        'enum' => ['root', 'child', 'before', 'after'],
                        'default' => 'root'
                    ],
                    'referenceIdentifier' => [
                        'type' => 'string',
                        'description' => 'Required for child, before, and after placement.'
                    ],
                    'position' => [
                        'type' => 'integer',
                        'description' => 'Optional zero-based position for root or child placement.'
                    ]
                ]
            ]
        );
    }
}
