<?php

/**
 * This file contains \QUI\Menu\MCP\Provider
 */

namespace QUI\Menu\MCP;

use Mcp\Server\Builder;
use QUI\AI\MCP\ProviderInterface;
use QUI\AI\MCP\Server;
use QUI\MCP\ToolInterface;
use QUI\Menu\MCP\Independent\AddMenuItem;
use QUI\Menu\MCP\Independent\CreateMenu;
use QUI\Menu\MCP\Independent\DeleteMenu;
use QUI\Menu\MCP\Independent\DeleteMenuItem;
use QUI\Menu\MCP\Independent\GetItemTypes;
use QUI\Menu\MCP\Independent\GetMenu;
use QUI\Menu\MCP\Independent\ListMenus;
use QUI\Menu\MCP\Independent\UpdateMenu;
use QUI\Menu\MCP\Independent\UpdateMenuItem;
use QUI\Permissions\Permission;
use Throwable;

/**
 * Menu MCP provider
 */
class Provider implements ProviderInterface
{
    /**
     * @var array<ToolInterface>
     */
    protected array $tools;

    public function __construct()
    {
        $this->tools = [
            new ListMenus(),
            new GetMenu(),
            new GetItemTypes(),
            new CreateMenu(),
            new UpdateMenu(),
            new DeleteMenu(),
            new AddMenuItem(),
            new UpdateMenuItem(),
            new DeleteMenuItem()
        ];
    }

    public function register(Builder $serverBuilder): void
    {
        if (!$this->canUseMcp()) {
            return;
        }

        foreach ($this->tools as $Tool) {
            $Tool->register($serverBuilder);
        }
    }

    protected function canUseMcp(): bool
    {
        try {
            Permission::checkPermission(
                AbstractTool::MENU_MCP_PERMISSION,
                Server::getRequestUser()
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
