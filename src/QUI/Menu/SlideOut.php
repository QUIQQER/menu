<?php

/**
 * This file contains \QUI\Menu\SlideOut
 */

namespace QUI\Menu;

use QUI;

/**
 * Class SlideOut
 * Creates an slideout menu
 *
 * @package QUI\Menu
 * @author  www.pcsg.de (Henning Leutz)
 */
class SlideOut extends QUI\Control
{
    /**
     * @var string
     */
    protected string $append = '';

    /**
     * @var string
     */
    protected string $prepend = '';

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'showHomeLink' => true,
            'menuId' => false, // if set independent menu template will be used
            'showFirstLevelIcons' => false, // current it works only for independent menu
            'collapseMobileSubmenu' => false,
            'showLevel' => 1
        ]);

        parent::__construct($attributes);
    }

    /**
     * append html to the menu
     * adds a html after the menu
     *
     * @param string $html
     */
    public function appendHTML(string $html): void
    {
        $this->append = $html;
    }

    /**
     * prepend html to the menu
     * adds a html before the menu
     *
     * @param string $html
     */
    public function prependHTML(string $html): void
    {
        $this->prepend = $html;
    }

    /**
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $collapseMobileSubmenu = $this->getAttribute('collapseMobileSubmenu');
        $showLevel = $this->getAttribute('showLevel');

        $params = [
            'this' => $this,
            'Project' => $this->getProject(),
            'jsControl' => 'package/quiqqer/menu/bin/SlideOutLazy',
            'slideOutControl' => 'package/quiqqer/menu/bin/SlideOut',
            'showHomeLink' => $this->getAttribute('showHomeLink'),
            'prepend' => $this->prepend,
            'append' => $this->append
        ];

        if ($this->getAttribute('menuId')) {
            $IndependentMenu = Independent\Handler::getMenu($this->getAttribute('menuId'));

            $template = dirname(__FILE__) . '/Menu.Independent.html';
            $params['FileMenu'] = dirname(__FILE__) . '/Menu.Children.Independent.html';
            $params['IndependentMenu'] = $IndependentMenu;
            $params['Site'] = $this->getSite();
            $params['collapseMobileSubmenu'] = $collapseMobileSubmenu;
            $params['showLevel'] = $showLevel;
            $params['showFirstLevelIcons'] = $this->getAttribute('showFirstLevelIcons');
        } else {
            $template = dirname(__FILE__) . '/Menu.html';
            $params['collapseMobileSubmenu'] = $collapseMobileSubmenu;
            $params['showLevel'] = $showLevel;
            $params['FileMenu'] = dirname(__FILE__) . '/Menu.Children.html';
            $params['Site'] = $this->getSite();
        }

        $Engine->assign($params);

        return $Engine->fetch($template);
    }

    /**
     * Return the current site
     *
     * @return QUI\Interfaces\Projects\Site
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        $Site = QUI::getRewrite()->getSite();

        if ($Site === null) {
            throw new QUI\Exception('No rewrite site available.');
        }

        return $Site;
    }
}
