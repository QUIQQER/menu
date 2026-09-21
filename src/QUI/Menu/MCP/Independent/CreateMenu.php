<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\CreateMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\Menu\Independent\Menu;
use QUI\Menu\MCP\AbstractTool;
use Throwable;

class CreateMenu extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                array | null $title = null,
                array | null $workingTitle = null,
                array | null $data = null
            ): CallToolResult | array {
                try {
                    self::checkMenuMcpPermission();

                    $Menu = new Menu([
                        'id' => 0,
                        'title' => [],
                        'workingTitle' => [],
                        'data' => ['children' => []]
                    ]);

                    return [
                        'menu' => self::saveMenuData($Menu, $data ?? ['children' => []], $title, $workingTitle, true)
                    ];
                } catch (Throwable $Exception) {
                    return self::writeFailure($Exception);
                }
            },
            name: 'quiqqer_menu_create',
            description: 'Creates an independent QUIQQER menu. Requires menu MCP and menu create/edit permissions.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
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
                        'description' => 'Complete replacement tree: {children: [...]}. Item titles and localized data fields accept language objects or legacy JSON strings. Use quiqqer_menu_item_types for item schemas. Omit data to create an empty menu.'
                    ]
                ]
            ]
        );
    }
}
