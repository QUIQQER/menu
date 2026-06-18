<?php

/**
 * This file contains QUI\Menu\Mega\Simple
 */

namespace QUI\Menu\Mega;

use QUI;

/**
 * Class MegaMenu
 *
 * @package QUI\Menu
 */
class Simple extends AbstractChild
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
        $this->setAttributes([
            'independentMenu' => false,
            'MenuChild' => null
        ]);

        parent::__construct($params);

        $this->addCSSClass('quiqqer-menu-megaMenu-children-simple');
        $this->addCSSFile(dirname(__FILE__) . '/Simple.css');
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

        if ($this->getAttribute('independentMenu')) {
            $MenuChild = $this->getAttribute('MenuChild');

            if (
                !$this->getAttribute(
                    'MenuChild'
                ) && !$MenuChild instanceof QUI\Menu\Independent\Items\AbstractMenuItem
            ) {
                return '';
            }

            $Engine->assign([
                'this' => $this,
                'children' => $MenuChild->getChildren()
            ]);

            $template = dirname(__FILE__) . '/Simple.Independent.html';
        } else {
            $Engine->assign([
                'this' => $this,
                'children' => $this->getChildren(),
                'Site' => $this->getSite()
            ]);

            $template = dirname(__FILE__) . '/Simple.html';
        }

        return $Engine->fetch($template);
    }
}
