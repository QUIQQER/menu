<?php

namespace QUI\Menu\Independent;

use Exception;
use QUI;
use QUI\Menu\Independent\Items\AbstractMenuItem;

use function array_filter;
use function array_values;
use function class_exists;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;

/**
 * Main Menu Class
 *
 * - Manage menu items
 */
class Menu
{
    protected int $id;
    /** @var array<string, string>|null */
    protected ?array $title = null;
    /** @var array<string, string>|null */
    protected ?array $workingTitle = null;
    /** @var array<string, mixed> */
    protected array $data = [];
    /** @var list<AbstractMenuItem> */
    protected array $children = [];
    protected int $currentChildId = 0;

    /**
     * @param int|array<string, mixed> $menuId - menu id or menu data
     *
     * @throws QUI\Exception
     * @throws QUI\Database\Exception
     */
    public function __construct(int | array $menuId)
    {
        if (is_numeric($menuId)) {
            $data = Handler::getMenuData($menuId);
        } else {
            $data = $menuId;

            if (
                !isset($data['id'])
                || !isset($data['title'])
                || !isset($data['workingTitle'])
            ) {
                throw new QUI\Exception(
                    'Menu not found',
                    404,
                    [
                        'menuData' => $data
                    ]
                );
            }
        }

        $this->id = (int)$data['id'];

        if (is_string($data['title'])) {
            $title = json_decode($data['title'], true);

            if (is_array($title)) {
                $this->title = $title;
            }
        } elseif (is_array($data['title'])) {
            $this->title = $data['title'];
        }

        if (is_string($data['workingTitle'])) {
            $workingTitle = json_decode($data['workingTitle'], true);

            if (is_array($workingTitle)) {
                $this->workingTitle = $workingTitle;
            }
        } elseif (is_array($data['workingTitle'])) {
            $this->workingTitle = $data['workingTitle'];
        }

        if (is_string($data['data'])) {
            $decode = json_decode($data['data'], true);

            if (is_array($decode)) {
                $this->data = $decode;
            }
        } elseif (is_array($data['data'])) {
            $this->data = $data['data'];
        }

        // build children
        if (isset($this->data['children']) && is_array($this->data['children'])) {
            $this->buildChildren($this, $this->data['children']);
        }
    }

    //region children

    /**
     * @param AbstractMenuItem|Menu $Parent
     * @param array<array-key, array<string, mixed>> $children
     * @return void
     */
    protected function buildChildren(AbstractMenuItem | Menu $Parent, array $children): void
    {
        foreach ($children as $item) {
            $type = $item['type'] ?? null;

            if (!is_string($type) || !class_exists($type)) {
                continue;
            }

            if (isset($item['title'])) {
                $item['title'] = json_decode($item['title'], true);
            }

            $Item = new $type($item);

            if (!($Item instanceof AbstractMenuItem)) {
                continue;
            }

            $Parent->appendChild($Item);

            if (isset($item['children']) && is_array($item['children'])) {
                $this->buildChildren($Item, $item['children']);
            }
        }
    }

    /**
     * Add a child item
     *
     * @param AbstractMenuItem $Item
     */
    public function appendChild(AbstractMenuItem $Item): void
    {
        $this->children[] = $Item;
    }

    /**
     * Return the children of this menu
     *
     * @param bool $onlyActive - if true, returns only the active children, if false, all children are returned
     * @return list<AbstractMenuItem>
     */
    public function getChildren(bool $onlyActive = true): array
    {
        if ($onlyActive === false) {
            return $this->children;
        }

        return array_values(array_filter($this->children, function (AbstractMenuItem $Item): bool {
            return $Item->isActive();
        }));
    }

    //endregion

    //region getter

    /**
     * @return array{id: int, title: string, workingTitle: string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'workingTitle' => $this->getWorkingTitle(),
            'data' => $this->data
        ];
    }

    public function getNewItemId(): int
    {
        return ++$this->currentChildId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param QUI\Locale|null $Locale
     * @return string
     */
    public function getTitle(null | QUI\Locale $Locale = null): string
    {
        if ($this->title === null) {
            return '';
        }

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->title[$current])) {
            return $this->title[$current];
        }

        return '';
    }

    /**
     * @param QUI\Locale|null $Locale
     * @return string
     */
    public function getWorkingTitle(null | QUI\Locale $Locale = null): string
    {
        if ($this->workingTitle === null) {
            return '';
        }

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->workingTitle[$current])) {
            return $this->workingTitle[$current];
        }

        return '';
    }

    /**
     * @return array{id: int, title: array<string, string>|null, workingTitle: array<string, string>|null, data: array<string, mixed>}
     */
    public function getData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'workingTitle' => $this->workingTitle,
            'data' => $this->data
        ];
    }

    //endregion

    //region setter

    /**
     * @param null|QUI\Interfaces\Users\User $PermissionUser
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Permissions\Exception
     */
    public function save(?QUI\Interfaces\Users\User $PermissionUser = null): void
    {
        QUI\Permissions\Permission::checkPermission('quiqqer.menu.edit', $PermissionUser);

        QUI::getDataBase()->update(Handler::table(), [
            'title' => json_encode($this->title),
            'workingTitle' => json_encode($this->workingTitle),
            'data' => json_encode($this->data)
        ], [
            'id' => $this->getId()
        ]);

        try {
            QUI::getEvents()->fireEvent('quiqqerMenuIndependentSave', [$this]);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * set the titles in different languages
     *
     * @param array<string, string>|null $title - ['de' => '', 'en' => '']
     * @return void
     */
    public function setTitle(?array $title): void
    {
        if ($title === null) {
            return;
        }

        if (!is_array($this->title)) {
            $this->title = [];
        }

        $available = QUI::availableLanguages();

        foreach ($available as $language) {
            if (isset($title[$language])) {
                $this->title[$language] = $title[$language];
            }
        }
    }

    /**
     * set the working titles in different languages
     *
     * @param array<string, string>|null $title - ['de' => '', 'en' => '']
     * @return void
     */
    public function setWorkingTitle(?array $title): void
    {
        if ($title === null) {
            return;
        }

        if (!is_array($this->workingTitle)) {
            $this->workingTitle = [];
        }

        $available = QUI::availableLanguages();

        foreach ($available as $language) {
            if (isset($title[$language])) {
                $this->workingTitle[$language] = $title[$language];
            }
        }
    }

    /**
     * @param array<string, mixed>|null $data
     * @return void
     */
    public function setData(?array $data): void
    {
        if ($data === null) {
            return;
        }

        if (isset($data['children'])) {
            $data = $this->sanitizeData($data);

            if ($data) {
                $this->data = $data;
            }
        }
    }

    /**
     * Checks a data array and filters not allowed entries.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function sanitizeData(array $data): ?array
    {
        return $this->checkData($data);
    }

    /**
     * Checks a data array and filters not allowed entries
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    protected function checkData(array $data): ?array
    {
        $result = [];

        if (isset($data['children'])) {
            $result['children'] = [];

            foreach ($data['children'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $child = $this->checkMenuDataItem($item);

                if ($child) {
                    $result['children'][] = $child;
                }
            }
        }

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    protected function checkMenuDataItem(array $item): ?array
    {
        $result = [];

        if (isset($item['title'])) {
            $result['title'] = $item['title'];
        }

        if (isset($item['identifier']) && is_string($item['identifier']) && $item['identifier'] !== '') {
            $result['identifier'] = $item['identifier'];
        } else {
            $result['identifier'] = QUI\Utils\Uuid::get();
        }

        if (isset($item['data'])) {
            $result['data'] = $item['data'];
        }

        // @todo check if fa icon or image
        if (isset($item['icon'])) {
            $result['icon'] = $item['icon'];
        }

        if (isset($item['type']) && is_string($item['type']) && class_exists($item['type'])) {
            $Item = new $item['type']();

            if ($Item instanceof AbstractMenuItem) {
                $result['type'] = $item['type'];
            }
        }

        if (isset($item['children'])) {
            foreach ($item['children'] as $childItem) {
                if (!is_array($childItem)) {
                    continue;
                }

                $child = $this->checkMenuDataItem($childItem);

                if ($child) {
                    $result['children'][] = $child;
                }
            }
        }

        if (!isset($result['title']) || !isset($result['type'])) {
            return null;
        }

        return $result;
    }
    //endregion
}
