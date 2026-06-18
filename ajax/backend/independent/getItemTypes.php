<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_getItemTypes
 */

use QUI\Menu\Independent\Items\AbstractMenuItem;

/**
 * Returns all menus
 *
 * @return array<int, array<string, string>>
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_getItemTypes',
    function (): array {
        $list = QUI\Menu\Independent\Handler::getItemList();
        $result = [];

        foreach ($list as $class) {
            if (!is_subclass_of($class, AbstractMenuItem::class)) {
                continue;
            }

            $result[] = [
                'title' => $class::itemTitle(),
                'desc' => $class::itemShort(),
                'icon' => $class::itemIcon(),
                'jsControl' => $class::itemJsControl(),
                'class' => $class
            ];
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
