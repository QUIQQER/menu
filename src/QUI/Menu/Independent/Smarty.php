<?php

namespace QUI\Menu\Independent;

use QUI;

use function class_exists;
use function is_string;

/**
 * Smart function for the smarty {menu} function
 *
 * {menu id=ID control=QUI\Class\Menu\Control}
 */
class Smarty
{
    /**
     * Menu function for smarty
     *
     * @param array<string, mixed> $params
     * @param mixed $smarty
     * @return string
     */
    public static function menu(array $params, mixed $smarty): string
    {
        if (empty($params['id']) || empty($params['control'])) {
            QUI\System\Log::addError('No menuId or menuDesign param for {menu} smarty function');
            return '';
        }

        try {
            $Project = QUI::getRewrite()->getProject();
        } catch (QUI\Exception) {
            return '';
        }

        $menuId = (int)$params['id'];
        $cacheName = Handler::getMenuCacheName($menuId, $Project);

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception) {
        }

        try {
            $control = $params['control'];

            if (!is_string($control) || !class_exists($control)) {
                return '';
            }

            $Menu = QUI\Menu\Independent\Handler::getMenu($menuId);
            $Control = new $control($Menu);

            if (!($Control instanceof QUI\Control)) {
                return '';
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            return '';
        }

        $html = $Control->create();
        QUI\Cache\Manager::set($cacheName, $html);

        return $html;
    }
}
