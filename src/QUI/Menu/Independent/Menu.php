<?php

namespace QUI\Menu\Independent;

use Exception;
use QUI;
use QUI\Menu\Independent\Items\AbstractMenuItem;

use function array_filter;
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
    protected ?array $title = null;
    protected ?array $workingTitle = null;
    protected array $data = [];
    protected array $children = [];
    protected int $currentChildId = 0;

    /**
     * @param int|array $menuId - menu id or menu data
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

        $this->id = $data['id'];

        if (is_string($data['title'])) {
            $this->title = json_decode($data['title'], true);
        } elseif (is_array($data['title'])) {
            $this->title = $data['title'];
        }

        if (is_string($data['workingTitle'])) {
            $this->workingTitle = json_decode($data['workingTitle'], true);
        } elseif (is_array($data['workingTitle'])) {
            $this->workingTitle = $data['workingTitle'];
        }

        if (is_string($data['data'])) {
            $decode = json_decode($data['data'], true);

            if ($decode) {
                $this->data = json_decode($data['data'], true);
            }
        } elseif (is_array($data['data'])) {
            $this->data = $data['data'];
        }

        // build children
        if (isset($this->data['children'])) {
            $this->buildChildren($this, $this->data['children']);
        }
    }

    //region children

    /**
     * @param AbstractMenuItem|Menu $Parent
     * @param array $children
     * @return void
     */
    protected function buildChildren(AbstractMenuItem | Menu $Parent, array $children): void
    {
        foreach ($children as $item) {
            $type = $item['type'];

            if (!class_exists($type)) {
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
     * @return array
     */
    public function getChildren(bool $onlyActive = true): array
    {
        if ($onlyActive === false) {
            return $this->children;
        }

        return array_filter($this->children, function ($Item) {
            return $Item->isActive();
        });
    }

    //endregion

    //region getter

    /**
     * @return array
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

    public function getNewItemId()
    {
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
     * @return array
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
     * @param array|null $title - ['de' => '', 'en' => '']
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
            if (isset($title[$language]) && is_string($title[$language])) {
                $this->title[$language] = $title[$language];
            }
        }
    }

    /**
     * set the working titles in different languages
     *
     * @param array|null $title - ['de' => '', 'en' => '']
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
            if (isset($title[$language]) && is_string($title[$language])) {
                $this->workingTitle[$language] = $title[$language];
            }
        }
    }

    /**
     * @param array|null $data
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
     * @param array $data
     * @return array|null
     */
    public function sanitizeData(array $data): ?array
    {
        return $this->checkData($data);
    }

    /**
     * Checks a data array and filters not allowed entries
     *
     * @param $data
     * @return array|null
     */
    protected function checkData($data): ?array
    {
        $result = [];

        if (isset($data['children'])) {
            $result['children'] = [];

            foreach ($data['children'] as $item) {
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
     * @param array $item
     * @return array|null
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

        if (
            isset($item['type'])
            && class_exists($item['type'])
            && new $item['type']() instanceof AbstractMenuItem
        ) {
            $result['type'] = $item['type'];
        }

        if (isset($item['children'])) {
            foreach ($item['children'] as $item) {
                $child = $this->checkMenuDataItem($item);

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
