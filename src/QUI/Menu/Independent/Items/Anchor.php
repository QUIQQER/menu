<?php

namespace QUI\Menu\Independent\Items;

use QUI;

use function is_array;
use function is_string;
use function json_decode;
use function trim;

/**
 * menu item to an anchor
 */
class Anchor extends AbstractMenuItem
{
    //region frontend item methods

    /**
     * @return string
     */
    public function getUrl(): string
    {
        $Site = $this->getSite();
        $data = $this->getCustomData();
        $current = QUI::getLocale()->getCurrent();

        if (!isset($data['url'])) {
            return '';
        }

        $url = '';
        $anchor = [];

        if ($Site) {
            try {
                $url .= $Site->getUrlRewritten();
            } catch (QUI\Exception) {
            }
        }

        if (is_string($data['url'])) {
            $anchor = json_decode($data['url'], true);
        } elseif (is_array($data['url'])) {
            $anchor = $data['url'];
        }

        if (is_array($anchor) && isset($anchor[$current])) {
            $anchorValue = trim($anchor[$current], '#');

            if ($anchorValue !== '') {
                $url .= '#' . $anchorValue;
            }
        }

        return $url;
    }

    /**
     * @return QUI\Projects\Site|null
     */
    public function getSite(): ?QUI\Projects\Site
    {
        $data = $this->getCustomData();

        if (!is_array($data) || !isset($data['site'])) {
            return null;
        }

        try {
            $siteUrl = $data['site'];
            $Site = QUI\Projects\Site\Utils::getSiteByLink($siteUrl);
        } catch (QUI\Exception) {
            return null;
        }

        $current = QUI::getLocale()->getCurrent();

        // if current language is another language as the site
        if ($current !== $Site->getAttribute('lang')) {
            try {
                $Project = $Site->getProject();
                $langId = $Site->getId($current);
                $LangProject = QUI::getProject($Project->getName(), $current);
                return $LangProject->get($langId);
            } catch (QUI\Exception) {
                return null;
            }
        }

        if ($Site->getAttribute('active')) {
            return $Site;
        }

        return null;
    }

    //endregion

    //region type stuff

    /**
     * @return string
     */
    public static function itemTitle(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.anchor.title');
    }

    /**
     * Short description of the menu types
     *
     * @return string
     */
    public static function itemShort(): string
    {
        return QUI::getLocale()->get('quiqqer/menu', 'item.anchor.short');
    }

    /**
     * @return string
     */
    public static function itemIcon(): string
    {
        return 'fa fa-anchor';
    }

    /**
     * @return string
     */
    public static function itemJsControl(): string
    {
        return 'package/quiqqer/menu/bin/Controls/Independent/Items/Anchor';
    }

    //endregion
}
