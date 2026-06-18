<?php

/**
 * This file contains QUI\Menu\NavTabsVertical
 */

namespace QUI\Menu;

use QUI;

/**
 * Class NavTabs
 *
 * @author  Michael Danielczok
 * @package QUI\Menu
 */
class NavTabsVertical extends QUI\Control
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
            'class' => 'quiqqer-menu-navTabsVertical',
            'qui-class' => 'package/quiqqer/menu/bin/Controls/NavTabs',
            'navTitle' => false,
            'navContent' => false,
            'imagePos' => 'top',
            'imageMaxWidth' => false,
            'imageMaxHeight' => false,
            'entries' => [],
            'template' => 'default'
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/NavTabsVertical.css');
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
        $enabledEntries = [];

        if (is_string($entries)) {
            $entries = json_decode($entries, true);
        }

        foreach ($entries as $entry) {
            if (isset($entry['isDisabled']) && $entry['isDisabled'] === 1) {
                continue;
            }

            $enabledEntries[] = $entry;
        }

        $Engine->assign([
            'this' => $this,
            'entries' => $enabledEntries,
            'navTitle' => $this->getAttribute('navTitle'),
            'navContent' => $this->getAttribute('navContent'),
            'imagePos' => $this->getAttribute('imagePos')
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/NavTabsVertical.html');
    }
}
