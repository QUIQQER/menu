<?php

namespace QUI\Menu\Independent;

use QUI;
use QUI\Menu\Independent\Items\AbstractMenuItem;
use QUI\Utils\Doctrine;

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
        $result = QUI::getQueryBuilder()
            ->select('*')
            ->from(Doctrine::quoteIdentifier(self::table()))
            ->where(Doctrine::quoteIdentifier('id') . ' = :id')
            ->setParameter('id', $menuId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (is_array($result)) {
            return $result;
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
        $data = QUI::getQueryBuilder()
            ->select('*')
            ->from(Doctrine::quoteIdentifier(self::table()))
            ->executeQuery()
            ->fetchAllAssociative();

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
