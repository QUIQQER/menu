<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\UpdateMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\Menu\Independent\Handler;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class UpdateMenu extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $id,
                array | null $title = null,
                array | null $workingTitle = null,
                array | null $data = null
            ): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    $Menu = Handler::getMenu($id);
                    return [
                        'menu' => self::saveMenuData(
                            $Menu,
                            $data ?? self::getMenuDataTree($Menu),
                            $title,
                            $workingTitle
                        )
                    ];
                } catch (Throwable $Exception) {
                    return self::writeFailure($Exception);
                }
            },
            name: 'quiqqer_menu_update',
            description: 'Updates an independent QUIQQER menu. Requires menu MCP and menu edit permissions.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['id'],
                'properties' => [
                    'id' => ['type' => 'integer', 'description' => 'Menu ID.'],
                    'title' => [
                        'type' => 'object',
                        'description' => 'Localized frontend title, keyed by language.',
                        'additionalProperties' => ['type' => 'string']
                    ],
                    'workingTitle' => [
                        'type' => 'object',
                        'description' => 'Localized internal working title, keyed by language.',
                        'additionalProperties' => ['type' => 'string']
                    ],
                    'data' => [
                        'type' => 'object',
                        'description' => 'Complete replacement tree: {children: [...]}. Item titles and localized data fields accept language objects or legacy JSON strings. Use quiqqer_menu_item_types for item schemas. Omit data to preserve existing items.'
                    ]
                ]
            ]
        );
    }
}
