<?php

/**
 * This file contains \QUI\Menu\MCP\AbstractTool
 */

namespace QUI\Menu\MCP;

use Mcp\Schema\Result\CallToolResult;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Exception;
use QUI\MCP\ToolInterface;
use QUI\Menu\Independent\Handler;
use QUI\Menu\Independent\Items\AbstractMenuItem;
use QUI\Menu\Independent\Items\Anchor;
use QUI\Menu\Independent\Items\Custom;
use QUI\Menu\Independent\Items\Site;
use QUI\Menu\Independent\Items\Url;
use QUI\Menu\Independent\LocalizedValue;
use QUI\Menu\Independent\Menu;
use QUI\Permissions\Permission;
use QUI\Utils\Doctrine;
use Throwable;

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
            $result['data'] = $data['data'];
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
     * @param list<AbstractMenuItem> $items
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
    protected static function normalizeInputItem(array $item, string $path = 'item', int $depth = 0): array
    {
        if ($depth > 64) {
            throw new Exception($path . ': menu nesting exceeds 64 levels.', 400);
        }

        self::validateKeys($item, ['type', 'title', 'identifier', 'icon', 'data', 'children'], $path);

        if (!isset($item['type']) || !in_array($item['type'], Handler::getItemList(), true)) {
            throw new Exception($path . '.type: unsupported menu item type.', 400);
        }

        $item['title'] = LocalizedValue::encode($item['title'] ?? null, $path . '.title');

        if (array_key_exists('identifier', $item)) {
            if (!is_string($item['identifier']) || $item['identifier'] === '') {
                throw new Exception($path . '.identifier: expected a non-empty string.', 400);
            }
        } else {
            $item['identifier'] = QUI\Utils\Uuid::get();
        }

        if (array_key_exists('icon', $item) && !is_string($item['icon'])) {
            throw new Exception($path . '.icon: expected a string.', 400);
        }

        if (!array_key_exists('data', $item)) {
            $item['data'] = [];
        }

        if (!is_array($item['data'])) {
            throw new Exception($path . '.data: expected an object.', 400);
        }

        $schema = self::getItemDataSchema($item['type']);
        self::validateKeys($item['data'], array_keys($schema['properties']), $path . '.data');

        foreach ($schema['required'] as $key) {
            if (!array_key_exists($key, $item['data'])) {
                throw new Exception($path . '.data.' . $key . ': required field.', 400);
            }
        }

        foreach ($item['data'] as $key => $value) {
            $fieldPath = $path . '.data.' . $key;
            if (in_array($key, ['name', 'short'], true) || ($key === 'url' && $item['type'] === Anchor::class)) {
                $item['data'][$key] = LocalizedValue::encode($value, $fieldPath);
            } elseif ($key === 'url' && is_array($value)) {
                $item['data'][$key] = LocalizedValue::encode($value, $fieldPath);
            } elseif (isset($schema['properties'][$key]['enum'])) {
                if (!in_array($value, $schema['properties'][$key]['enum'], true)) {
                    throw new Exception($fieldPath . ': invalid value.', 400);
                }
            } elseif (!is_string($value)) {
                throw new Exception($fieldPath . ': expected a string.', 400);
            }
        }

        if (array_key_exists('children', $item)) {
            $item['children'] = self::normalizeChildren($item['children'], $path . '.children', $depth + 1);
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array-key> $keys
     */
    protected static function validateKeys(array $data, array $keys, string $path): void
    {
        foreach ($data as $key => $_) {
            if (!in_array($key, $keys, true)) {
                throw new Exception($path . '.' . $key . ': unsupported field.', 400);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    protected static function normalizeChildren(mixed $children, string $path, int $depth = 0): array
    {
        if (!is_array($children) || !array_is_list($children)) {
            throw new Exception($path . ': expected an ordered array of menu items.', 400);
        }

        $result = [];
        foreach ($children as $index => $child) {
            if (!is_array($child)) {
                throw new Exception($path . '[' . $index . ']: expected a menu item object.', 400);
            }

            $result[] = self::normalizeInputItem($child, $path . '[' . $index . ']', $depth);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{children: list<array<string, mixed>>}
     */
    protected static function normalizeMenuData(array $data): array
    {
        self::validateKeys($data, ['children'], 'data');
        $children = self::normalizeChildren($data['children'] ?? null, 'data.children');
        $identifiers = [];
        self::validateIdentifiers($children, $identifiers, 'data.children');
        return ['children' => $children];
    }

    /**
     * @param list<array<string, mixed>> $children
     * @param array<array-key, true> $identifiers
     */
    private static function validateIdentifiers(array $children, array &$identifiers, string $path): void
    {
        foreach ($children as $index => $child) {
            $itemPath = $path . '[' . $index . ']';
            if (isset($identifiers[$child['identifier']])) {
                throw new Exception($itemPath . '.identifier: duplicate menu item identifier.', 400);
            }

            $identifiers[$child['identifier']] = true;
            self::validateIdentifiers($child['children'] ?? [], $identifiers, $itemPath . '.children');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getMenuDataTree(Menu $Menu): array
    {
        $menuData = $Menu->getData();
        $data = $menuData['data'];

        if (!isset($data['children']) || !is_array($data['children'])) {
            $data['children'] = [];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $title
     * @param array<string, mixed>|null $workingTitle
     * @return array<string, mixed>
     */
    protected static function saveMenuData(
        Menu $Menu,
        array $data,
        ?array $title = null,
        ?array $workingTitle = null,
        bool $create = false
    ): array {
        $User = Server::getRequestUser();
        Permission::checkPermission('quiqqer.menu.edit', $User);
        if ($create) {
            Permission::checkPermission('quiqqer.menu.create', $User);
        }

        // Work on a separate candidate. Never mutate the caller's menu on failure.
        $candidate = $Menu->getData();
        $candidate['title'] ??= [];
        $candidate['workingTitle'] ??= [];
        $candidate['data'] = self::normalizeMenuData($data);
        $Candidate = new Menu($candidate);
        $Candidate->setTitle($title === null ? null : LocalizedValue::decode($title, 'title'));
        $Candidate->setWorkingTitle(
            $workingTitle === null ? null : LocalizedValue::decode($workingTitle, 'workingTitle')
        );

        $row = $Candidate->getData();
        unset($row['id']);
        foreach ($row as $key => $value) {
            $row[$key] = json_encode($value, JSON_THROW_ON_ERROR);
        }

        // Exercise the actual storage representation and response rendering before any write.
        json_encode(self::parseMenu(new Menu(['id' => $Menu->getId()] + $row), true), JSON_THROW_ON_ERROR);
        $Connection = QUI::getDataBaseConnection();
        if ($Connection->isTransactionActive()) {
            throw new Exception('Menu writes require their own transaction.', 409);
        }

        [$savedMenu, $result] = $Connection->transactional(static function () use ($Connection, $Menu, $row, $create): array {
            $table = Doctrine::quoteIdentifier(Handler::table());
            if ($create) {
                $Connection->insert($table, $row);
                $id = (int)$Connection->lastInsertId();
            } else {
                $id = $Menu->getId();
                $Connection->update($table, $row, ['id' => $id]);
            }

            $stored = Handler::getMenuData($id);
            foreach ($row as $key => $value) {
                if ($stored[$key] !== $value) {
                    throw new Exception($key . ': stored menu differs from the validated candidate.', 500);
                }
            }

            $Saved = new Menu($stored);
            // A failed reload or response serialization rolls the database change back.
            $result = self::parseMenu($Saved, true);
            json_encode($result, JSON_THROW_ON_ERROR);
            return [$Saved, $result];
        });

        // Only notifications and cache invalidation happen after commit.
        $result['saved'] = true;
        $actions = [
            static fn() => QUI::getEvents()->fireEvent('quiqqerMenuIndependentSave', [$savedMenu]),
            static fn() => QUI\Cache\Manager::clear(Handler::getMenuCacheName($savedMenu->getId())),
            static fn() => QUI::getEvents()->fireEvent('quiqqerMenuIndependentClear', [$savedMenu->getId()])
        ];
        if ($create) {
            array_unshift($actions, static fn() => QUI::getEvents()->fireEvent('quiqqerMenuIndependentCreate', [$savedMenu]));
        }

        foreach ($actions as $action) {
            try {
                $action();
            } catch (Throwable $Exception) {
                $result['warnings'][] = 'Menu was saved; post-save notification/cache refresh failed: '
                    . $Exception->getMessage();
            }
        }

        return $result;
    }

    protected static function writeFailure(Throwable $Exception): CallToolResult
    {
        return ToolHelper::parseExceptionToResult(new Exception(
            'No changes saved. ' . $Exception->getMessage(),
            $Exception->getCode() ?: 400,
            ['saved' => false]
        ));
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
        self::validateKeys($patch, ['type', 'title', 'icon', 'data', 'children'], 'patch');

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

    /**
     * @param array<array-key, array<string, mixed>> $children
     * @param array<string, mixed> $item
     */
    protected static function insertAtPosition(array &$children, array $item, ?int $position): void
    {
        if (!is_int($position) || $position < 0 || $position >= count($children)) {
            $children[] = $item;
            return;
        }

        array_splice($children, $position, 0, [$item]);
    }

    /**
     * @param array<array-key, array<string, mixed>> $children
     * @param array<string, mixed> $item
     */
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

    /**
     * @param array<array-key, array<string, mixed>> $children
     * @param array<string, mixed> $item
     */
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
    /**
     * @param array<array-key, array<string, mixed>> $children
     * @param array<string, mixed> $patch
     */
    protected static function updateItemByIdentifier(
        array &$children,
        string $identifier,
        array $patch,
        string $path = 'data.children'
    ): bool {
        foreach ($children as $index => &$child) {
            if (($child['identifier'] ?? null) === $identifier) {
                foreach (['type', 'title', 'icon', 'data', 'children'] as $key) {
                    if (array_key_exists($key, $patch)) {
                        $child[$key] = $patch[$key];
                    }
                }

                $child = self::normalizeInputItem($child, $path . '[' . $index . ']');
                return true;
            }

            if (isset($child['children']) && is_array($child['children'])) {
                if (self::updateItemByIdentifier($child['children'], $identifier, $patch, $path . '[' . $index . '].children')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<array-key, array<string, mixed>> $children
     */
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
            $properties['url'] = self::urlSchema('External or internal URL, optionally keyed by language.');
            $properties['name'] = self::localizedMapSchema('Optional link text override.');
            $required[] = 'url';
        }

        if ($type === Custom::class) {
            $properties['url'] = self::urlSchema('Optional URL, optionally keyed by language.');
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

    /** @return array<string, mixed> */
    protected static function urlSchema(string $description): array
    {
        return [
            'description' => $description,
            'anyOf' => [
                ['type' => 'string'],
                self::localizedMapSchema('URLs keyed by language.')
            ]
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
