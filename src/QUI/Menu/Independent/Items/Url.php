<?php

namespace QUI\Menu\Independent\Items;

use QUI;
use QUI\Locale;

use function is_array;

/**
 * menu item to an external url
 */
class Url extends AbstractMenuItem
{
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

    //region type stuff

    /**
     * @return string
     */
    public static function itemTitle(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.url.title');
    }

    /**
     * Short description of the menu types
     *
     * @return string
     */
    public static function itemShort(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.url.short');
    }

    /**
     * @return string
     */
    public static function itemIcon(): string
    {
        return 'fa fa-globe';
    }

    /**
     * @return string
     */
    public static function itemJsControl(): string
    {
        return 'package/quiqqer/menu/bin/Controls/Independent/Items/Url';
    }

    //endregion type stuff
}
