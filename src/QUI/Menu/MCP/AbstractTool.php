<?php

/**
 * This file contains \QUI\Menu\MCP\AbstractTool
 */

namespace QUI\Menu\MCP;

use QUI\AI\MCP\Server;
use QUI\Exception;
use QUI\MCP\ToolInterface;
use QUI\Menu\Independent\Handler;
use QUI\Menu\Independent\Items\AbstractMenuItem;
use QUI\Menu\Independent\Items\Anchor;
use QUI\Menu\Independent\Items\Custom;
use QUI\Menu\Independent\Items\Site;
use QUI\Menu\Independent\Items\Url;
use QUI\Menu\Independent\Menu;
use QUI\Permissions\Permission;

use function array_key_exists;
use function array_map;
use function array_splice;
use function class_exists;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function min;

abstract class AbstractTool implements ToolInterface
{
    public const MENU_MCP_PERMISSION = 'quiqqer.menu.mcp.canUse';

    protected const REL_VALUES = [
        '',
        'alternate',
        'author',
        'bookmark',
        'external',
        'help',
        'license',
        'next',
        'nofollow',
        'noopener',
        'noreferrer',
        'prev',
        'search',
        'tag'
    ];

    protected const TARGET_VALUES = [
        '',
        '_self',
        'frame',
        'popup',
        '_blank',
        '_top',
        '_parent'
    ];

    protected const MENU_TYPE_VALUES = [
        'Standard',
        'Icons',
        'IconsDescription',
        'Image',
        'Simple',
        'noMenu'
    ];

    protected static function checkMenuMcpPermission(): void
    {
        Permission::checkPermission(
            self::MENU_MCP_PERMISSION,
            Server::getRequestUser()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseMenu(Menu $Menu, bool $withItems = false): array
    {
        $data = $Menu->getData();

        $result = [
            'id' => $Menu->getId(),
            'title' => self::normalizeLocaleMap($data['title'] ?? null),
            'workingTitle' => self::normalizeLocaleMap($data['workingTitle'] ?? null)
        ];

        if ($withItems) {
            $result['data'] = $data['data'] ?? [];
            $result['items'] = self::parseMenuItems($Menu->getChildren(false));
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function parseItemTypes(): array
    {
        return array_map(static function (string $type): array {
            return [
                'type' => $type,
                'title' => $type::itemTitle(),
                'description' => $type::itemShort(),
                'icon' => $type::itemIcon(),
                'jsControl' => $type::itemJsControl(),
                'schema' => self::getItemSchema($type),
                'example' => self::getItemExample($type)
            ];
        }, Handler::getItemList());
    }

    /**
     * @param array<AbstractMenuItem> $items
     * @return array<int, array<string, mixed>>
     */
    protected static function parseMenuItems(array $items): array
    {
        return array_map(static fn(AbstractMenuItem $Item): array => self::parseMenuItem($Item), $items);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseMenuItem(AbstractMenuItem $Item): array
    {
        $type = $Item->getType();

        $result = [
            'identifier' => $Item->getIdentifier(),
            'type' => $type,
            'typeTitle' => class_exists($type) ? $type::itemTitle() : '',
            'title' => $Item->getTitle(),
            'name' => $Item->getName(),
            'url' => $Item->getUrl(),
            'icon' => $Item->getIcon(),
            'active' => $Item->isActive(),
            'data' => $Item->getCustomData()
        ];

        $children = $Item->getChildren(false);

        if (!empty($children)) {
            $result['children'] = self::parseMenuItems($children);
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected static function normalizeLocaleMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $lang => $text) {
            if (is_string($lang) && is_string($text)) {
                $result[$lang] = $text;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected static function normalizeInputItem(array $item): array
    {
        if (!isset($item['type']) || !is_string($item['type'])) {
            throw new Exception('Menu item type is required.', 400);
        }

        if (!in_array($item['type'], Handler::getItemList(), true)) {
            throw new Exception('Unsupported menu item type.', 400, ['type' => $item['type']]);
        }

        if (!isset($item['title']) || !is_array($item['title'])) {
            throw new Exception('Menu item title must be a localized object.', 400);
        }

        $item['title'] = self::normalizeLocaleMap($item['title']);

        if (isset($item['identifier']) && (!is_string($item['identifier']) || $item['identifier'] === '')) {
            throw new Exception('Menu item identifier must be a non-empty string.', 400);
        }

        if (!isset($item['identifier'])) {
            $item['identifier'] = \QUI\Utils\Uuid::get();
        }

        if (isset($item['icon']) && !is_string($item['icon'])) {
            throw new Exception('Menu item icon must be a string.', 400);
        }

        if (!isset($item['data']) || !is_array($item['data'])) {
            $item['data'] = [];
        }

        self::validateTypeData($item['type'], $item['data']);

        if (isset($item['children']) && !is_array($item['children'])) {
            throw new Exception('Menu item children must be an array.', 400);
        }

        if (isset($item['children'])) {
            $item['children'] = array_map(
                static fn(array $child): array => self::normalizeInputItem($child),
                $item['children']
            );
        }

        return $item;
    }

    /**
     * @throws Exception
     */
    protected static function validateTypeData(string $type, array $data): void
    {
        self::validateCommonData($data);

        if ($type === Site::class && !isset($data['site'])) {
            throw new Exception('Site menu items require data.site.', 400);
        }

        if ($type === Url::class && !isset($data['url'])) {
            throw new Exception('URL menu items require data.url.', 400);
        }

        if ($type === Anchor::class && (!isset($data['site']) || !isset($data['url']))) {
            throw new Exception('Anchor menu items require data.site and data.url.', 400);
        }
    }

    /**
     * @throws Exception
     */
    protected static function validateCommonData(array $data): void
    {
        if (isset($data['target']) && !in_array($data['target'], self::TARGET_VALUES, true)) {
            throw new Exception('Invalid menu item target.', 400, ['target' => $data['target']]);
        }

        if (isset($data['rel']) && !in_array($data['rel'], self::REL_VALUES, true)) {
            throw new Exception('Invalid menu item rel value.', 400, ['rel' => $data['rel']]);
        }

        if (isset($data['menuType']) && !in_array($data['menuType'], self::MENU_TYPE_VALUES, true)) {
            throw new Exception('Invalid menu item menuType.', 400, ['menuType' => $data['menuType']]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getMenuDataTree(Menu $Menu): array
    {
        $menuData = $Menu->getData();
        $data = $menuData['data'] ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        if (!isset($data['children']) || !is_array($data['children'])) {
            $data['children'] = [];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected static function saveMenuData(Menu $Menu, array $data): array
    {
        $sanitized = $Menu->sanitizeData($data);

        if ($sanitized !== $data) {
            throw new Exception('Invalid menu data. The menu was not saved.', 400, [
                'data' => $data,
                'sanitized' => $sanitized
            ]);
        }

        $Menu->setData($data);
        $Menu->save(Server::getRequestUser());

        return self::parseMenu(Handler::getMenu($Menu->getId()), true);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected static function addItemToData(
        array $data,
        array $item,
        string $placement,
        ?string $referenceIdentifier,
        ?int $position
    ): array {
        $item = self::normalizeInputItem($item);

        if ($placement === 'root') {
            self::insertAtPosition($data['children'], $item, $position);
            return $data;
        }

        if ($referenceIdentifier === null || $referenceIdentifier === '') {
            throw new Exception('referenceIdentifier is required for child, before, and after placement.', 400);
        }

        if ($placement === 'child') {
            if (!self::insertItemBelow($data['children'], $referenceIdentifier, $item, $position)) {
                throw new Exception('Parent menu item was not found.', 404, ['referenceIdentifier' => $referenceIdentifier]);
            }

            return $data;
        }

        if ($placement === 'before' || $placement === 'after') {
            if (!self::insertItemRelative($data['children'], $referenceIdentifier, $item, $placement === 'before')) {
                throw new Exception('Reference menu item was not found.', 404, ['referenceIdentifier' => $referenceIdentifier]);
            }

            return $data;
        }

        throw new Exception('Invalid item placement.', 400, ['placement' => $placement]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $patch
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected static function updateItemInData(array $data, string $identifier, array $patch): array
    {
        if (!self::updateItemByIdentifier($data['children'], $identifier, $patch)) {
            throw new Exception('Menu item was not found.', 404, ['identifier' => $identifier]);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    protected static function deleteItemFromData(array $data, string $identifier): array
    {
        if (!self::deleteItemByIdentifier($data['children'], $identifier)) {
            throw new Exception('Menu item was not found.', 404, ['identifier' => $identifier]);
        }

        return $data;
    }

    protected static function insertAtPosition(array &$children, array $item, ?int $position): void
    {
        if (!is_int($position) || $position < 0 || $position >= count($children)) {
            $children[] = $item;
            return;
        }

        array_splice($children, $position, 0, [$item]);
    }

    protected static function insertItemBelow(array &$children, string $parentIdentifier, array $item, ?int $position): bool
    {
        foreach ($children as &$child) {
            if (($child['identifier'] ?? null) === $parentIdentifier) {
                if (!isset($child['children']) || !is_array($child['children'])) {
                    $child['children'] = [];
                }

                self::insertAtPosition($child['children'], $item, $position);
                return true;
            }

            if (isset($child['children']) && is_array($child['children'])) {
                if (self::insertItemBelow($child['children'], $parentIdentifier, $item, $position)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function insertItemRelative(
        array &$children,
        string $referenceIdentifier,
        array $item,
        bool $before
    ): bool {
        foreach ($children as $index => &$child) {
            if (($child['identifier'] ?? null) === $referenceIdentifier) {
                array_splice($children, $before ? $index : $index + 1, 0, [$item]);
                return true;
            }

            if (isset($child['children']) && is_array($child['children'])) {
                if (self::insertItemRelative($child['children'], $referenceIdentifier, $item, $before)) {
                    return true;
                }
            }
        }

        return false;
    }
    protected static function updateItemByIdentifier(array &$children, string $identifier, array $patch): bool
    {
        foreach ($children as &$child) {
            if (($child['identifier'] ?? null) === $identifier) {
                foreach (['type', 'title', 'icon', 'data', 'children'] as $key) {
                    if (array_key_exists($key, $patch)) {
                        $child[$key] = $patch[$key];
                    }
                }

                $child = self::normalizeInputItem($child);
                return true;
            }

            if (isset($child['children']) && is_array($child['children'])) {
                if (self::updateItemByIdentifier($child['children'], $identifier, $patch)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function deleteItemByIdentifier(array &$children, string $identifier): bool
    {
        foreach ($children as $index => &$child) {
            if (($child['identifier'] ?? null) === $identifier) {
                array_splice($children, $index, 1);
                return true;
            }

            if (isset($child['children']) && is_array($child['children'])) {
                if (self::deleteItemByIdentifier($child['children'], $identifier)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getItemSchema(string $type): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['type', 'title', 'data'],
            'properties' => [
                'identifier' => ['type' => 'string', 'description' => 'Stable item identifier. Optional on create.'],
                'type' => ['const' => $type],
                'title' => [
                    'type' => 'object',
                    'description' => 'Localized item title, keyed by language.',
                    'additionalProperties' => ['type' => 'string']
                ],
                'icon' => ['type' => 'string', 'description' => 'FontAwesome class or image reference.'],
                'data' => self::getItemDataSchema($type),
                'children' => ['type' => 'array', 'description' => 'Nested menu items.']
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getItemDataSchema(string $type): array
    {
        $properties = self::getCommonDataProperties();
        $required = [];

        if ($type === Site::class) {
            $properties['site'] = ['type' => 'string', 'description' => 'QUIQQER site link/path used by Site\Utils::getSiteByLink().'];
            $required[] = 'site';
        }

        if ($type === Url::class) {
            $properties['url'] = ['type' => 'string', 'description' => 'External or internal URL.'];
            $properties['name'] = self::localizedMapSchema('Optional link text override.');
            $required[] = 'url';
        }

        if ($type === Custom::class) {
            $properties['url'] = ['type' => 'string', 'description' => 'Optional URL.'];
            $properties['name'] = self::localizedMapSchema('Optional link text override.');
            $properties['short'] = self::localizedMapSchema('Optional short text.');
            $properties['click'] = ['type' => 'string', 'description' => 'Optional click handler value.'];
        }

        if ($type === Anchor::class) {
            $properties['site'] = ['type' => 'string', 'description' => 'QUIQQER site link/path used by Site\Utils::getSiteByLink().'];
            $properties['url'] = self::localizedMapSchema('Anchor value without leading #, keyed by language.');
            $properties['name'] = self::localizedMapSchema('Optional link text override.');
            $required = ['site', 'url'];
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => $required,
            'properties' => $properties
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getCommonDataProperties(): array
    {
        return [
            'status' => ['type' => 'integer', 'enum' => [0, 1], 'description' => '1 active, 0 inactive.'],
            'target' => ['type' => 'string', 'enum' => self::TARGET_VALUES],
            'rel' => ['type' => 'string', 'enum' => self::REL_VALUES],
            'menuType' => ['type' => 'string', 'enum' => self::MENU_TYPE_VALUES]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function localizedMapSchema(string $description): array
    {
        return [
            'type' => 'object',
            'description' => $description,
            'additionalProperties' => ['type' => 'string']
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getItemExample(string $type): array
    {
        return match ($type) {
            Site::class => [
                'type' => Site::class,
                'title' => ['de' => 'Startseite', 'en' => 'Home'],
                'data' => [
                    'site' => '1',
                    'target' => '',
                    'menuType' => 'Standard',
                    'status' => 1,
                    'rel' => ''
                ]
            ],
            Url::class => [
                'type' => Url::class,
                'title' => ['de' => 'Externer Link', 'en' => 'External link'],
                'icon' => 'fa fa-globe',
                'data' => [
                    'url' => 'https://example.com',
                    'target' => '_blank',
                    'menuType' => 'Standard',
                    'status' => 1,
                    'name' => ['de' => 'Example', 'en' => 'Example'],
                    'rel' => 'noopener'
                ]
            ],
            Custom::class => [
                'type' => Custom::class,
                'title' => ['de' => 'Kontakt', 'en' => 'Contact'],
                'icon' => 'fa fa-envelope',
                'data' => [
                    'url' => '/kontakt',
                    'target' => '',
                    'menuType' => 'Standard',
                    'status' => 1,
                    'name' => ['de' => 'Kontakt', 'en' => 'Contact'],
                    'short' => ['de' => 'Kontakt aufnehmen', 'en' => 'Get in touch'],
                    'rel' => ''
                ]
            ],
            Anchor::class => [
                'type' => Anchor::class,
                'title' => ['de' => 'Abschnitt', 'en' => 'Section'],
                'icon' => 'fa fa-anchor',
                'data' => [
                    'site' => '1',
                    'url' => ['de' => 'leistungen', 'en' => 'services'],
                    'menuType' => 'Standard',
                    'status' => 1,
                    'name' => ['de' => 'Leistungen', 'en' => 'Services']
                ]
            ],
            default => []
        };
    }
}
