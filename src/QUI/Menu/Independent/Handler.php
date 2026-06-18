<?php

namespace QUI\Menu\Independent;

use QUI;
use QUI\Menu\Independent\Items\AbstractMenuItem;

use function is_scalar;
use function md5;

/**
 * Menu handler
 * - get menus
 */
class Handler
{
    /**
     * @return string
     */
    public static function table(): string
    {
        return QUI::getDBTableName('menus');
    }

    /**
     * @param int $menuId
     * @return Menu
     *
     * @throws QUI\Exception
     */
    public static function getMenu(int $menuId): Menu
    {
        return new Menu($menuId);
    }

    /**
     * @param bool|int $menuId
     * @param QUI\Projects\Project|null $Project
     * @return string
     */
    public static function getMenuCacheName(
        bool | int $menuId = false,
        null | QUI\Projects\Project $Project = null
    ): string {
        if ($Project) {
            $project = $Project->getName();
            $lang = $Project->getLang();
            $template = $Project->getAttribute('template');
            $template = is_scalar($template) ? (string)$template : '';
            $projectHash = '/' . md5($project . '/' . $lang . '/' . $template);
        } else {
            $projectHash = '';
        }

        if ($menuId) {
            return 'quiqqer/menu/independent/' . $menuId . $projectHash;
        }

        return 'quiqqer/menu/independent';
    }

    /**
     * @param int $menuId
     * @return array<string, mixed>
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function getMenuData(int $menuId): array
    {
        $data = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'where' => [
                'id' => $menuId
            ],
            'limit' => 1
        ]);

        if (isset($data[0])) {
            return $data[0];
        }

        throw new QUI\Exception(
            'Menu not found',
            404,
            [
                'menuId' => $menuId
            ]
        );
    }

    /**
     * @return list<Menu>
     * @throws QUI\Database\Exception
     */
    public static function getList(): array
    {
        $data = QUI::getDataBase()->fetch([
            'from' => self::table()
        ]);

        $result = [];

        foreach ($data as $entry) {
            try {
                $result[] = new Menu($entry);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return list<class-string<AbstractMenuItem>>
     *
     * @todo Item Class Provider -> API
     */
    public static function getItemList(): array
    {
        return [
            QUI\Menu\Independent\Items\Site::class,
            QUI\Menu\Independent\Items\Anchor::class,
            QUI\Menu\Independent\Items\Url::class,
            QUI\Menu\Independent\Items\Custom::class,
        ];
    }
}
