<?php

/**
 * This file contains QUI\Menu\NavTabs
 */

namespace QUI\Menu;

use QUI;
use QUI\Projects\Site\Utils;

use function count;

/**
 * Class NavTabs
 *
 * @author  Michael Danielczok
 * @package QUI\Menu
 */
class NavTabs extends QUI\Control
{
    /**
     * entries array
     * [
     *   'title'   => string,
     *   'content' => string
     * ]
     *
     * @var list<array{title: mixed, content: string}>
     */
    private array $entries = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'nodeName' => 'div',
            'class' => 'quiqqer-menu-navTabs',
            'data-qui' => 'package/quiqqer/menu/bin/Controls/NavTabs',
            'activeEntry' => 1, // number
            'parentSite' => false, // for child sites, example: index.php?project=test&lang=de&id=1
            'showShort' => false, // only if parentSite set
            'max' => 10,
            'entries' => [],
            'template' => 'default'
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/NavTabs.css');
    }

    /**
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $this->setJavaScriptControlOption('animation', 'slide');

        if ($this->getAttribute('parentSite')) {
            $this->entries = $this->getChildrenFromParent();
        }

        $active = 1;

        if ($this->getAttribute('activeEntry') && $this->getAttribute('activeEntry') > 0) {
            $active = $this->getAttribute('activeEntry');
        }

        $Engine->assign([
            'this' => $this,
            'entries' => $this->entries,
            'active' => $active
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/NavTabs.html');
    }

    /**
     * Get children sites from parent site
     *
     * @return array|string
     * @throws QUI\Exception
     */
    private function getChildrenFromParent(): array | string
    {
        $ParentSite = null;

        if ($this->getAttribute('parentSite')) {
            try {
                $ParentSite = Utils::getSiteByLink($this->getAttribute('parentSite'));
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());

                return '';
            }
        }

        $sites = $ParentSite->getChildren([
            'where' => [
                'active' => 1,
            ],
            'limit' => $this->getAttribute('max')
        ]);

        $entries = [];

        /** @var QUI\Projects\Site $Site */
        foreach ($sites as $Site) {
            $short = '';

            if ($this->getAttribute('showShort') && $Site->getAttribute('short')) {
                $short = '<div class="quiqqer-menu-navTabs-content-item-shortDesc text-muted">' .
                    $Site->getAttribute('short') .
                    '</div>';
            }

            $entryContent = $short . $Site->getAttribute('content');

            $entry = [
                'title' => $Site->getAttribute('title'),
                'content' => $entryContent,
            ];

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Convert data set to entries
     * [
     *   ['title1', 'content1],
     *   ['title2', 'content2],
     *   ['title3', 'content3],
     *   .....................
     * ]
     *
     * @param array $data
     * @return false|void
     */
    public function setData(array $data)
    {
        if (count($data) < 1) {
            return false;
        }

        $entries = [];

        foreach ($data as $dataSet) {
            if (count($dataSet) < 2) {
                continue;
            }

            $entry = [
                'title' => $dataSet[0],
                'content' => $dataSet[1],
            ];

            $entries[] = $entry;
        }

        $this->entries = $entries;
    }
}
