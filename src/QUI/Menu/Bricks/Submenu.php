<?php

/**
 * This file contains \QUI\Menu\Bricks\Submenu
 */

namespace QUI\Menu\Bricks;

use QUI;
use QUI\Exception;
use QUI\Menu\Independent;
use QUI\Projects\Site\Utils;

/**
 * Class Brick Submenu
 *
 *  It creates a submenu navigation
 *
 *  This control is supposed to work with QUI Independent Menu, but you can use it with QUI Site.
 *  You have to pass a menu ID or a parent page.
 *
 * @package QUI\Menu
 * @author www.pcsg.de (Michael Danielczok)
 */
class Submenu extends QUI\Control
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // defaults values
        $this->setAttributes([
            'class' => 'quiqqer-menu-bricks-submenu',
            'startId' => false,
            // id or site link
            'menuId' => false,
            // id of an independent menu
            'template' => 'list-buttonStyle',
            // 'list-buttonStyle', 'list-simple', 'box-imageTop', 'box-imageOverlay'
            'controlBgColor' => '',
            'controlBgPadding' => '1rem',
            'linkColor' => '',
            'linkColorHover' => '',
            'itemsAlignment' => 'center',
            // 'start', 'center', 'end', 'space-between', 'space-around'
            'showImages' => true,
            // if true, icons or images will be displayed
            'imageFitMode' => 'cover',
            // any valid css property for image-fit attribute , i.e. 'cover', 'contain', 'scale-down'
            'imageContainerHeight' => '',
            // any valid css property (with unit!) for height attribute, i.e. '150px', '10vw' or even clamp() function (if no value passed the container will be a square)
            'boxBgColor' => '#f5f5f6',
            'boxWidth' => '250px'
            // any valid css property (with unit!) for height attribute, i.e. '250px', '10vw' or even clamp() function
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', false);
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $linkColor = $this->getAttribute('linkColor');

        if ($linkColor === '') {
            $linkColor = 'inherit';
        }

        $linkColorHover = $this->getAttribute('linkColorHover');

        if (empty($linkColor)) {
            $linkColorHover = 'inherit';
        }

        $Submenu = new QUI\Menu\Controls\Submenu([
            'startId' => $this->getAttribute('startId'),
            'menuId' => $this->getAttribute('menuId'),
            'template' => $this->getAttribute('template'),
            'controlBgColor' => $this->getAttribute('controlBgColor'),
            'controlBgPadding' => $this->getAttribute('controlBgPadding'),
            'linkColor' => $linkColor,
            'linkColorHover' => $linkColorHover,
            'itemsAlignment' => $this->getAttribute('itemsAlignment'),
            'showImages' => $this->getAttribute('showImages'),
            'imageFitMode' => $this->getAttribute('imageFitMode'),
            'imageContainerHeight' => $this->getAttribute('imageContainerHeight'),
            'boxBgColor' => $this->getAttribute('boxBgColor'),
            'boxWidth' => $this->getAttribute('boxWidth')
        ]);

        $Engine->assign([
            'this' => $this,
            'Submenu' => $Submenu
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Submenu.html');
    }
}
