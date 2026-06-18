<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_getItemName
 */

/**
 * Return the item name
 */

use QUI\Menu\Independent\Items\AbstractMenuItem;

QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_getItemName',
    function ($item): string {
        $item = json_decode($item, true);

        if (!is_array($item)) {
            return '';
        }

        $type = $item['type'] ?? '';

        if (!is_string($type) || !is_subclass_of($type, AbstractMenuItem::class)) {
            return '';
        }

        $Item = new $type($item);
        return $Item->getName();
    },
    ['item'],
    'Permission::checkAdminUser'
);
