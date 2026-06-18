<?php

namespace QUITests\Menu\MCP;

use Mcp\Server\Builder;
use QUI\Menu\MCP\AbstractTool;

class MenuMcpToolTestAccessor extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
    }

    public static function itemTypes(): array
    {
        return self::parseItemTypes();
    }

    public static function addItem(
        array $data,
        array $item,
        string $placement,
        ?string $referenceIdentifier = null,
        ?int $position = null
    ): array {
        return self::addItemToData($data, $item, $placement, $referenceIdentifier, $position);
    }

    public static function updateItem(array $data, string $identifier, array $patch): array
    {
        return self::updateItemInData($data, $identifier, $patch);
    }

    public static function deleteItem(array $data, string $identifier): array
    {
        return self::deleteItemFromData($data, $identifier);
    }
}
