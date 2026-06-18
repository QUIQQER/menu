<?php

/**
 * This file contains QUI\Menu\Mega\Standard
 */

namespace QUI\Menu\Mega;

use QUI;

/**
 * Class MegaMenu
 *
 * @package QUI\Menu
 */
class Standard extends AbstractChild
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

        $this->addCSSClass('quiqqer-menu-megaMenu-children-standard');
        $this->addCSSFile(dirname(__FILE__) . '/Standard.css');
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

        return $Engine->fetch(dirname(__FILE__) . '/Standard.html');
    }
}
