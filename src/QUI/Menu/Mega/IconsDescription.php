<?php

/**
 * This file contains QUI\Menu\Mega\IconsDescription
 */

namespace QUI\Menu\Mega;

use QUI;

/**
 * Class Icons
 * Sub menu -> Icons / Images with Description
 *
 * @package QUI\Menu
 */
class IconsDescription extends AbstractChild
{
    /**
     * @var array<int, QUI\Projects\Site>|null
     */
    protected ?array $children = null;

    /**
     * Standard constructor.
     *
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        $this->addCSSClass('quiqqer-menu-megaMenu-children-iconsDesc');
        $this->addCSSFile(dirname(__FILE__) . '/IconsDescription.css');
    }

    /**
     * Return the html body
     *
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'this' => $this,
            'children' => $this->getChildren(),
            'Site' => $this->getSite()
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/IconsDescription.html');
    }
}
