<?php

/**
 * This file contains QUI\Menu\OnePageNav
 */

namespace QUI\Menu;

use QUI;

/**
 * Class OnePageNav
 *
 * @package quiqqer/menu
 */
class OnePageNav extends QUI\Control
{
    /**
     * constructor
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'title' => 'One Page Navigation',
            'contentList' => false,
            'entries' => [],
            'qui-class' => 'package/quiqqer/menu/bin/Controls/OnePageNav'
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/OnePageNav.css'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $entries = $this->getAttribute('entries');

        if (is_string($entries)) {
            $entries = json_decode($entries, true);
        }

        $Engine->assign([
            'this' => $this,
            'entries' => $entries
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/OnePageNav.html');
    }
}
