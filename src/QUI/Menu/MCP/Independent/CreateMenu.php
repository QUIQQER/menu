<?php

/**
 * This file contains \QUI\Menu\MCP\Independent\CreateMenu
 */

namespace QUI\Menu\MCP\Independent;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Menu\Independent\Factory;
use QUI\Menu\Independent\Handler;
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

                    $Menu = Factory::createMenu(Server::getRequestUser());
                    $Menu->setTitle($title);
                    $Menu->setWorkingTitle($workingTitle);
                    $Menu->setData($data);
                    $Menu->save(Server::getRequestUser());

                    return [
                        'menu' => self::parseMenu(Handler::getMenu($Menu->getId()), true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
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
                        'description' => 'Menu data. Use a children array with supported item type class names.'
                    ]
                ]
            ]
        );
    }
}
