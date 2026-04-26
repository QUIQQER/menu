<?php

/**
 * This file contains QUI\Menu\Controls\Tabs
 */

namespace QUI\Menu\Controls;

use QUI;

/**
 * Class NavTabs
 *
 * @author  Michael Danielczok
 * @package QUI\Menu
 */
class Tabs extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'class' => 'quiqqer-tabs',
            'qui-class' => 'package/quiqqer/menu/bin/Controls/Tabs',
            'activeEntry' => 1,
            // number
            'entries' => [],
            // simple.navTop, simple.navBottom, buttonBar, buttonBar.navBottom, tabular
            'template' => 'simple.navTop',
            'animation' => 'scaleToLargeScaleFromSmall',

            // autoplay & progress
            'autoPlay' => false, // enable / disable autoplay function
            'autoPlay.controls' => false, // show play / pause button
            'autoPlay.controls.alignment' => 'right', // left / center / right
            'autoPlay.interval' => 5000,
            'autoPlay.pauseOnHover' => false,
            'autoPlay.progress.indicator' => 'progressbar', // if empty no slider indicator will be shown

            // tabs nav
            'navImgHeight' => 20,
            // number
            'navStyle' => 'imgLeft',
            // imgLeft, imgTop, onlyImg
            'navWrapText' => 'noWrap',
            // wrap / noWrap; allow breaking text on space. 'noWrap' set "white-space: nowrap;" CSS property on nav text
            'navFillSpace' => false,
            // it feels the available space
            'navCenter' => false,
            'enableDragToScroll' => true,

            // tabs content
            'contentPos' => 'center', // top, center, bottom
            'contentImgMinWidth' => 200,
            // number; do not use large values, recommended is between 100 and 800
            'contentImgMaxWidth' => 400,
            // number; do not use large values, recommended is between 200 and 800
            'contentTextWidth' => 600,
            // number
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/Tabs.css');
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
        $navFile = null;
        $navCssFile = null;
        $templateCssClass = null;
        $navLayout = null;

        // Eindeutige ID pro Widget-Instanz (für ARIA-IDs, Live-Region etc.)
        $instanceId = 'tabs-' . uniqid('', true);

        if (is_string($entries)) {
            $entries = json_decode($entries, true);
        }

        foreach ($entries as $entry) {
            if (isset($entry['isDisabled']) && $entry['isDisabled'] === 1) {
                continue;
            }

            $enabledEntries[] = $entry;
        }

        $active = 1;

        $this->setJavaScriptControlOption('enabledragtoscroll', $this->getAttribute('enableDragToScroll'));
        $this->setJavaScriptControlOption('autoplay', $this->getAttribute('autoPlay'));
        $this->setJavaScriptControlOption('autoplayinterval', $this->getAttribute('autoPlay.interval'));
        $this->setJavaScriptControlOption('pauseonhover', $this->getAttribute('autoPlay.pauseOnHover'));

        // 1) Aktiv per Query-Parameter ?open=<slug> (slug wie in id / urlEncodeString)
        if (isset($_GET['open']) && $_GET['open']) {
            $open = (string)$_GET['open'];
            $idx = 1;
            foreach ($enabledEntries as $entry) {
                $slug = QUI\Utils\Security\Orthos::urlEncodeString($entry['tabTitle'] ?? '');
                if ($slug === $open) {
                    $active = $idx;
                    break;
                }
                $idx++;
            }
        }

        // 2) Fallback: activeEntry-Attribut (1-basiert)
        if ($active === 1 && $this->getAttribute('activeEntry') && $this->getAttribute('activeEntry') > 0) {
            $active = (int)$this->getAttribute('activeEntry');
        }

        /* template */
        switch ($this->getAttribute('template')) {
            case 'tabular':
                $navPos = 'top';
                $navFile = dirname(__FILE__) . '/Tabs.Nav.Tabular.html';
                $navCssFile = dirname(__FILE__) . '/Tabs.Nav.Tabular.css';
                $templateCssClass = 'quiqqer-tabsAdvanced-control--tabular';
                $navLayout = 'tabular';
                break;

            case 'buttonBar.navBottom':
                $navPos = 'bottom';
                $navFile = dirname(__FILE__) . '/Tabs.Nav.ButtonBar.html';
                $navCssFile = dirname(__FILE__) . '/Tabs.Nav.ButtonBar.css';
                $templateCssClass = 'quiqqer-tabsAdvanced-control--buttonBar';
                $navLayout = 'buttonBar';
                break;

            case 'buttonBar':
                $navPos = 'top';
                $navFile = dirname(__FILE__) . '/Tabs.Nav.ButtonBar.html';
                $navCssFile = dirname(__FILE__) . '/Tabs.Nav.ButtonBar.css';
                $templateCssClass = 'quiqqer-tabsAdvanced-control--buttonBar';
                $navLayout = 'buttonBar';
                break;

            case 'simple.navBottom':
                $navPos = 'bottom';
                $navFile = dirname(__FILE__) . '/Tabs.Nav.Simple.html';
                $navCssFile = dirname(__FILE__) . '/Tabs.Nav.Simple.css';
                $templateCssClass = 'quiqqer-tabsAdvanced-control--simple';
                $navLayout = 'simple';
                break;

            case 'simple.navTop':
            default:
                $navPos = 'top';
                $navFile = dirname(__FILE__) . '/Tabs.Nav.Simple.html';
                $navCssFile = dirname(__FILE__) . '/Tabs.Nav.Simple.css';
                $templateCssClass = 'quiqqer-tabsAdvanced-control--simple';
                $navLayout = 'simple';
                break;
        }

        $this->addCSSFile($navCssFile);

        /* nav */
        $showNavText = true;
        $navWrapText = 'navText__wrap';
        $navTabStyleCss = 'navTabStyle__imgLeft';
        $navAlignment = '';
        $navFillSpace = '';
        $navImgHeight = $this->getAttribute('navImgHeight');

        if ($navImgHeight) {
            $this->setCustomVariable('navImgHeight', $navImgHeight);
        }

        switch ($this->getAttribute('navStyle')) {
            case 'imgTop':
                $navTabStyleCss = 'navTabStyle__imgTop';
                break;
            case 'onlyImg':
                $navTabStyleCss = 'navTabStyle__onlyImg';
                $showNavText = false;
        }

        if (!$this->getAttribute('navFillSpace') && $this->getAttribute('navCenter')) {
            // centre nav tabs only if "navFillSpace" options is not enabled
            $navAlignment = 'quiqqer-tabsAdvanced-nav--center';
        }

        if ($this->getAttribute('navFillSpace')) {
            $navFillSpace = 'quiqqer-tabsAdvanced-nav-inner--navFillSpace';
        }

        if ($this->getAttribute('navWrapText') === 'noWrap') {
            $navWrapText = 'navText__noWrap';
        }

        $contentPos = match ($this->getAttribute('contentPos')) {
            'top' => 'flex-start',
            'bottom' => 'flex-end',
            default => 'center',
        };

        $this->setCustomVariable('contentPos', $contentPos);

        $contentImgMaxWidth = $this->getAttribute('contentImgMaxWidth');
        $contentImgMinWidth = $this->getAttribute('contentImgMinWidth');
        $contentTextWidth = $this->getAttribute('contentTextWidth');

        if (!$contentImgMaxWidth) {
            $contentImgMaxWidth = 400;
        }

        $this->setCustomVariable('contentImgMaxWidth', $contentImgMaxWidth . 'px');

        if ($contentImgMinWidth) {
            $this->setCustomVariable('contentImgMinWidth', $contentImgMinWidth . 'px');
        }

        if ($contentTextWidth) {
            $this->setCustomVariable('contentTextWidth', $contentTextWidth . 'px');
        }

        $controlsAlignments = match ($this->getAttribute('autoPlay.controls.alignment')) {
            'left', 'center', 'right' => $this->getAttribute('autoPlay.controls.alignment'),
            default => 'left'
        };

        $this->setCustomVariable('controlsAlignment', $controlsAlignments);

        $animation = match ($this->getAttribute('animation')) {
            'fadeOutFadeIn',
            'scaleToSmallScaleFromLarge',
            'scaleToSmallScaleFromSmall',
            'scaleToLargeScaleFromLarge',
            'scaleToLargeScaleFromSmall',
            'slideOutToRightSlideInFromLeft',
            'slideOutToRightSlideInFromRight',
            'slideOutToBottomSlideInFromBottom',
            'slideOutToBottomSlideInFromTop',
            'slideOutToLeftSlideInFromRight',
            'slideOutToLeftSlideInFromLeft' => $this->getAttribute('animation'),
            default => 'scaleToLargeScaleFromSmall'
        };

        $this->setJavaScriptControlOption('animation', $animation);

        $Engine->assign([
            'this' => $this,
            'entries' => $enabledEntries,
            'active' => $active,
            'instanceId' => $instanceId,
            'navStyle' => $this->getAttribute('navStyle'),
            'navPos' => $navPos,
            'navFile' => $navFile,
            'navWrapText' => $navWrapText,
            'navTabStyleCss' => $navTabStyleCss,
            'showNavText' => $showNavText,
            'navAlignment' => $navAlignment,
            'navFillSpace' => $navFillSpace,
            'templateCssClass' => $templateCssClass,
            'navLayout' => $navLayout,
            'navFillSpaceEnabled' => (bool)$this->getAttribute('navFillSpace'),
            'contentImgMaxWidth' => $contentImgMaxWidth,
            'autoPlay' => $this->getAttribute('autoPlay')
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Tabs.html');
    }

    /**
     * Set custom css variable to the control as inline style
     * --_qui-controls-tabs-$name: var(--qui-menu-urlList-$name, $value);
     *
     * Example:
     *     --_qui-menu-controls-tabs-navImgHeight: var(--qui-menu-controls-tabs-navImgHeight, 400px);
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    private function setCustomVariable(string $name, string $value): void
    {
        if (!$name || !$value) {
            return;
        }

        $this->setStyle(
            '--_qui-menu-controls-tabs-' . $name,
            'var(--qui-menu-controls-tabs-' . $name . ', ' . $value . ')'
        );
    }
}
