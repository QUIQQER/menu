<?php

/**
 * This file contains QUI\Menu\Mega\Icons
 */

namespace QUI\Menu\Mega;

use QUI;

/**
 * Class Icons
 *
 * @package QUI\Menu
 */
class Icons extends AbstractChild
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

        $this->addCSSClass('quiqqer-menu-megaMenu-children-icons');
        $this->addCSSFile(dirname(__FILE__) . '/Icons.css');
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

        return $Engine->fetch(dirname(__FILE__) . '/Icons.html');
    }
}
