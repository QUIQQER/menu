<?php

namespace QUI\Menu\Independent\Items;

use QUI;
use QUI\Locale;

use function is_array;
use function is_string;
use function json_decode;
use function trim;

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

        $url = $data['url'];

        if (is_string($url)) {
            $decoded = json_decode($url, true);

            if (!is_array($decoded)) {
                return trim($url);
            }

            $url = $decoded;
        }

        if (!is_array($url)) {
            return '';
        }

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($url[$current]) && is_string($url[$current]) && trim($url[$current]) !== '') {
            return trim($url[$current]);
        }

        // fallback, so entries without a translated url keep working
        foreach ($url as $langUrl) {
            if (is_string($langUrl) && trim($langUrl) !== '') {
                return trim($langUrl);
            }
        }

        return '';
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
