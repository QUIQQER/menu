<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_getData
 */

QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_getData',
    function ($id) {
        $Menu = QUI\Menu\Independent\Handler::getMenu($id);
        return $Menu->getData();
    },
    ['id'],
    'Permission::checkAdminUser'
);
