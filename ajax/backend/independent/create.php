<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_create
 */

/**
 * Creates a new menu
 *
 * @param string $title - JSON array
 * @param string $title - JSON array
 *
 * @return array
 */

use QUI\Menu\Independent\Factory;

QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_create',
    function ($title, $workingTitle) {
        $title = json_decode($title, true);
        $workingTitle = json_decode($workingTitle, true);

        $Menu = Factory::createMenu();
        $Menu->setTitle($title);
        $Menu->setWorkingTitle($workingTitle);
        $Menu->save();

        return $Menu->getId();
    },
    ['title', 'workingTitle'],
    'quiqqer.menu.create'
);
