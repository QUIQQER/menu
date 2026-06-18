<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_save
 */

QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_save',
    function ($id, $title, $workingTitle, $data) {
        $title = json_decode($title, true);
        $workingTitle = json_decode($workingTitle, true);
        $data = json_decode($data, true);

        $Menu = QUI\Menu\Independent\Handler::getMenu($id);
        $Menu->setTitle($title);
        $Menu->setWorkingTitle($workingTitle);
        $Menu->setData($data);
        $Menu->save();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/menu', 'menu.saved.successfully')
        );
    },
    ['id', 'title', 'workingTitle', 'data'],
    'quiqqer.menu.edit'
);
