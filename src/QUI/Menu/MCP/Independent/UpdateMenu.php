<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\UpdateMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
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
                    $Menu->setTitle($title);
                    $Menu->setWorkingTitle($workingTitle);
                    $Menu->setData($data);
                    $Menu->save(Server::getRequestUser());

                    return [
                        'menu' => self::parseMenu(Handler::getMenu($id), true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
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
                        'description' => 'Menu data. Use a children array with supported item type class names.'
                    ]
                ]
            ]
        );
    }
}
