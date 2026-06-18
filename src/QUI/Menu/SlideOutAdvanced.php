<?php

/**
 * This file contains \QUI\Menu\SlideOutAdvanced
 */

namespace QUI\Menu;

use QUI;

use function dirname;

/**
 * Class MenuAdvanced
 * Creates a slide-out menu
 *
 * @package QUI\Menu
 */
class SlideOutAdvanced extends QUI\Control
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'menuId' => false, // if set independent menu template will be used
            'showFirstLevelIcons' => false, // current it works only for independent menu
            'showHomeLink' => true,
            'showShortDesc' => true,
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/SlideOutAdvanced.css'
        );
    }

    /**
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $params = [
            'this' => $this,
            'Project' => $this->getProject(),
            'jsControl' => 'package/quiqqer/menu/bin/SlideOutLazy',
            'slideOutControl' => 'package/quiqqer/menu/bin/SlideoutAdvanced',
            'showShortDesc' => $this->getAttribute('showShortDesc'),
            'showHomeLink' => $this->getAttribute('showHomeLink')
        ];

        if ($this->getAttribute('menuId')) {
            $IndependentMenu = Independent\Handler::getMenu($this->getAttribute('menuId'));

            $template = dirname(__FILE__) . '/SlideOutAdvanced.Independent.html';
            $params['FileMenu'] = dirname(__FILE__) . '/SlideOutAdvanced.Children.Independent.html';
            $params['IndependentMenu'] = $IndependentMenu;
            $params['Site'] = $this->getSite();
            $params['showFirstLevelIcons'] = $this->getAttribute('showFirstLevelIcons');
        } else {
            $template = dirname(__FILE__) . '/SlideOutAdvanced.html';
            $params['FileMenu'] = dirname(__FILE__) . '/SlideOutAdvanced.Children.html';
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

        return QUI::getRewrite()->getSite();
    }
}
