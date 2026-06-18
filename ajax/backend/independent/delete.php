<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_delete
 */

/**
 * Delete menus
 *
 * @param array $ids - JSON array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_delete',
    function ($ids) {
        $ids = json_decode($ids, true);

        foreach ($ids as $id) {
            QUI\Menu\Independent\Factory::deleteMenu($id);
        }
    },
    ['ids'],
    'quiqqer.menu.delete'
);
