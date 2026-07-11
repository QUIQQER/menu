<?php

namespace QUI\Menu\Independent\Items;

use QUI;
use QUI\Locale;

use function is_array;

/**
 * Custom item
 * - flexible menu item
 */
class Custom extends AbstractMenuItem
{
    //region frontend item methods

    /**
     * Multilingual: {"de": "...", "en": "..."};
     * a plain string (old single-language format) is returned unchanged
     * for every language (backward compatible).
     *
     * @param ?Locale $Locale
     * @return string
     */
    public function getUrl(null | Locale $Locale = null): string
    {
        $data = $this->getCustomData();

        if (!is_array($data) || !isset($data['url'])) {
            return '';
        }

        return $this->resolveLocalizedUrl($data['url'], $Locale);
    }

    /**
     * Get the click event
     *
     * @return string|null
     */
    public function getClickEvent()
    {
        $data = $this->getCustomData();
        return isset($data['click']) ? htmlspecialchars($data['click'], ENT_QUOTES, 'UTF-8') : null;
    }

    //endregion

    //region type stuff

    /**
     * return the item type title
     *
     * @return string
     */
    public static function itemTitle(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.custom.title');
    }

    /**
     * Short description of the menu types
     *
     * @return string
     */
    public static function itemShort(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.custom.short');
    }

    /**
     * return the item type icon
     *
     * @return string
     */
    public static function itemIcon(): string
    {
        return 'fa fa-keyboard-o';
    }

    /**
     * return the type js admin control
     *
     * @return string
     */
    public static function itemJsControl(): string
    {
        return 'package/quiqqer/menu/bin/Controls/Independent/Items/Custom';
    }

    //endregion
}
