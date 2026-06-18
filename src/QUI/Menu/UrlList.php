<?php

/**
 * This file contains \QUI\Menu\UrlList
 */

namespace QUI\Menu;

use QUI;
use QUI\Exception;
use QUI\Projects\Site\Utils;

/**
 * Class UrlList
 *
 * Simple url list.
 *
 * It creates a list of sites.
 * This control is supposed to work with QUI Independent Menu, but you can use it with QUI Site.
 * You have to pass a menu ID or a parent page.
 *
 * Only one level of sub sites will be displayed started from given page (or menu id).
 * This control DO NOT support nested navigation structure.
 *
 * @package QUI\Menu
 * @author www.pcsg.de (Michael Danielczok)
 */
class UrlList extends QUI\Control
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // defaults values
        $this->setAttributes([
            'class' => 'qui-menu-urlList',
            'headerText' => '', // title above the url list
            'startId' => false, // id or site link
            'menuId' => false, // id of an (independent) menu
            'display' => 'default',
            'icon' => '', // only fontawesome icons are supported
            'resetLinkColor' => true // if true, the link inherit color from parent
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', false);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws Exception
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        if ($this->getAttribute('menuId')) {
            // independent menu
            $children = $this->getChildrenForIndependentMenu();
            $template = dirname(__FILE__) . '/UrlList.Independent.Default.html';
        } elseif ($this->getAttribute('startId')) {
            // qui site
            $children = $this->getChildrenForQUISite();
            $template = dirname(__FILE__) . '/UrlList.Default.html';
        } else {
            return '';
        }

        $Engine = QUI::getTemplateManager()->getEngine();
        $icon = '';

        if ($this->getAttribute('icon') && str_starts_with($this->getAttribute('icon'), 'fa ')) {
            $icon = $this->getAttribute('icon');
        }

        switch ($this->getAttribute('display')) {
            default:
            case 'default':
                $cssClass = 'qui-menu-urlList--default'; // "quiqqer-urlList--default" is deprecated
                $cssFile = 'UrlList.Default.css';
                break;
        }

        if ($this->getAttribute('resetLinkColor')) {
            $this->addCSSClass('qui-menu-urlList--resetLinkColor');
        }

        $Engine->assign([
            'this' => $this,
            'headerText' => $this->getAttribute('headerText'),
            'children' => $children,
            'icon' => $icon,
        ]);

        $this->addCSSClass($cssClass);
        $this->addCSSFile(dirname(__FILE__) . '/' . $cssFile);

        return $Engine->fetch($template);
    }

    /**
     * Get sites for independent menu
     *
     * @return list<Independent\Items\AbstractMenuItem>
     * @throws QUI\Exception
     */
    public function getChildrenForIndependentMenu(): array
    {
        $IndependentMenu = Independent\Handler::getMenu($this->getAttribute('menuId'));

        return $IndependentMenu->getChildren();
    }

    /**
     * Get sites for QUI site
     *
     * @return array<int, QUI\Projects\Site>|int
     * @throws Exception
     */
    public function getChildrenForQUISite(): array|int
    {
        $Project = $this->getProject();

        // start
        try {
            $startId = $this->getAttribute('startId');

            if (Utils::isSiteLink($startId)) {
                $Site = Utils::getSiteByLink($startId);
            } else {
                $Site = $Project->get((int)$startId);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return [];
        }

        return $this->getChildren($Site);
    }

    /**
     * @param QUI\Projects\Site $Site
     * @return array<int, QUI\Projects\Site>|int
     * @throws QUI\Exception
     */
    public function getChildren(QUI\Projects\Site $Site): array|int
    {
        if (!$this->getAttribute('showAllChildren')) {
            return $Site->getNavigation();
        }

        return $Site->getChildren();
    }
}
