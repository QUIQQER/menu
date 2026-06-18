<?php

/**
 * This file contains package_quiqqer_menu_ajax_backend_independent_get
 */

use QUI\Menu\Independent\Items\AbstractMenuItem;

if (!function_exists('packageQuiqqerMenuParseChildren')) {
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    function packageQuiqqerMenuParseChildren(array $data): array
    {
        if (!isset($data['children']) || !is_array($data['children'])) {
            return $data;
        }

        foreach ($data['children'] as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $type = $entry['type'] ?? '';

            if (!is_string($type) || !is_subclass_of($type, AbstractMenuItem::class)) {
                continue;
            }

            $Item = new $type($entry);
            $icon = $type::itemIcon();

            if (isset($entry['children']) && is_array($entry['children'])) {
                $data['children'][$key] = packageQuiqqerMenuParseChildren($entry);
            }

            $data['children'][$key]['typeIcon'] = $icon;
            $data['children'][$key]['titleFrontend'] = $Item->getTitle();
        }

        return $data;
    }
}

QUI::getAjax()->registerFunction(
    'package_quiqqer_menu_ajax_backend_independent_get',
    function ($id) {
        $Menu = QUI\Menu\Independent\Handler::getMenu((int)$id);
        $result = $Menu->toArray();
        $result['data'] = packageQuiqqerMenuParseChildren($result['data']);

        return $result;
    },
    ['id'],
    'Permission::checkAdminUser'
);
