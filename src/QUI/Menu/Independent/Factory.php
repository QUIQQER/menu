<?php

namespace QUI\Menu\Independent;

use Exception;
use QUI;
use QUI\Interfaces\Users\User;

/**
 * Menu factory
 */
class Factory
{
    /**
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function createMenu(?User $PermissionUser = null): Menu
    {
        QUI\Permissions\Permission::checkPermission('quiqqer.menu.create', $PermissionUser);

        QUI::getDataBase()->insert(Handler::table(), [
            'title' => '',
            'workingTitle' => '',
            'data' => ''
        ]);

        $lastId = (int)QUI::getPDO()->lastInsertId();
        $Menu = Handler::getMenu($lastId);

        try {
            QUI::getEvents()->fireEvent('quiqqerMenuIndependentCreate', [$Menu]);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $Menu;
    }

    /**
     * @param int $menuId
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function deleteMenu(int $menuId, ?User $PermissionUser = null): void
    {
        QUI\Permissions\Permission::checkPermission('quiqqer.menu.delete', $PermissionUser);

        QUI::getDataBase()->delete(Handler::table(), [
            'id' => $menuId
        ]);

        try {
            QUI::getEvents()->fireEvent('quiqqerMenuIndependentDelete', [$menuId]);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }
}
