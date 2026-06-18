<?php

/**
 * This file contains \QUI\Menu\Controls\Submenu
 */

namespace QUI\Menu\Controls;

use QUI;
use QUI\Exception;
use QUI\Menu\Independent;
use QUI\Projects\Site\Utils;

/**
 * Class Submenu
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
    private string $templateFile;
    private string $templateCssFile;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // defaults values
        $this->setAttributes([
            'class' => 'quiqqer-submenu',
            'startId' => false, // id or site link
            'menuId' => false, // id of an independent menu
            'template' => 'list-buttonStyle', // 'list-buttonStyle', 'list-simple', 'box-imageTop', 'box-imageOverlay'
            'controlBgColor' => '',
            'controlBgPadding' => '1rem',
            'linkColor' => 'inherit',
            'linkColorHover' => 'inherit',
            'itemsAlignment' => 'center', // 'start', 'center', 'end', 'space-between', 'space-around'
            'showImages' => true, // if true, icons or images will be displayed
            'imageFitMode' => 'cover', // any valid css property for image-fit attribute , i.e. 'cover', 'contain', 'scale-down'
            'imageContainerHeight' => '',// any valid css property (with unit!) for height attribute, i.e. '150px', '10vw' or even clamp() function (if no value passed the container will be a square)
            'boxBgColor' => '#f5f5f6',
            'boxWidth' => '250px'// any valid css property (with unit!) for height attribute, i.e. '250px', '10vw' or even clamp() function
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', false);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws Exception
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $isIndependentMenu = false;

        if ($this->getAttribute('menuId')) {
            // independent menu
            $children = $this->getChildrenForIndependentMenu();
            $isIndependentMenu = true;
        } elseif ($this->getAttribute('startId')) {
            // qui site
            $children = $this->getChildrenForQUISite();
        } else {
            return '';
        }

        $url = false;

        $url = QUI::getRewrite()->getSite()?->getUrlRewritten() ?: false;

        $Engine = QUI::getTemplateManager()->getEngine();

        $this->addCSSClass('quiqqer-submenu--' . $this->getAttribute('template'));

        switch ($this->getAttribute('template')) {
            case 'box-imageTop':
            case 'box-imageOverlay':
                $templateName = '/Submenu.Box.html';

                if ($isIndependentMenu) {
                    $templateName = '/Submenu.Box.Independent.html';
                }

                $this->templateFile = dirname(__FILE__) . $templateName;
                $this->templateCssFile = dirname(__FILE__) . '/Submenu.Box.css';

                $this->setCustomVariable('imageFitMode', $this->getAttribute('imageFitMode'));

                $imageContainerHeight = $this->getAttribute('imageContainerHeight');

                if ($imageContainerHeight) {
                    $this->setCustomVariable('imageContainerHeight', $imageContainerHeight);
                }

                $this->setCustomVariable('boxBgColor', $this->getAttribute('boxBgColor'));
                $this->setCustomVariable('boxWidth', $this->getAttribute('boxWidth'));

                break;

            case 'list-simple':
                $templateName = '/Submenu.List.html';

                if ($isIndependentMenu) {
                    $templateName = '/Submenu.List.Independent.html';
                }

                $this->templateFile = dirname(__FILE__) . $templateName;
                $this->templateCssFile = dirname(__FILE__) . '/Submenu.List.css';

                break;

            default:
            case 'list-buttonStyle':
                $templateName = '/Submenu.List.html';

                if ($isIndependentMenu) {
                    $templateName = '/Submenu.List.Independent.html';
                }

                if (!$this->getAttribute('controlBgColor')) {
                    $this->setAttribute('controlBgColor', '#f5f5f5');
                }

                $this->templateFile = dirname(__FILE__) . $templateName;
                $this->templateCssFile = dirname(__FILE__) . '/Submenu.List.css';

                break;
        }

        if ($this->getAttribute('controlBgColor')) {
            $this->setCustomVariable(
                'controlBgColor',
                $this->getAttribute('controlBgColor')
            );

            if ($this->getAttribute('controlBgPadding')) {
                $this->addCSSClass('quiqqer-submenu--controlPadding');

                $this->setCustomVariable(
                    'controlBgPadding',
                    $this->getAttribute('controlBgPadding')
                );
            }
        }

        if ($this->getAttribute('linkColor')) {
            $this->setCustomVariable(
                'linkColor',
                $this->getAttribute('linkColor')
            );

            $this->addCSSClass('quiqqer-submenu--linkColor');
        }

        if ($this->getAttribute('linkColorHover')) {
            $this->setCustomVariable(
                'linkColor--hover',
                $this->getAttribute('linkColorHover')
            );

            $this->addCSSClass('quiqqer-submenu--linkColorHover');
        }

        switch ($this->getAttribute('itemsAlignment')) {
            case 'start':
            case 'center':
            case 'end':
            case 'space-between':
            case 'space-around':
                $itemsAlignment = $this->getAttribute('itemsAlignment');
                break;

            default:
                $itemsAlignment = 'center';
                break;
        }

        $this->setCustomVariable('itemsAlignment', $itemsAlignment);

        $Engine->assign([
            'this' => $this,
            'children' => $children,
            'url' => $url,
            'IconHandler' => new QUI\Icons\Handler()
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/Submenu.css');
        $this->addCSSFile($this->templateCssFile);

        return $Engine->fetch($this->templateFile);
    }

    /**
     * Get sites for independent menu
     *
     * @return list<Independent\Items\AbstractMenuItem>
     * @throws QUI\Exception
     */
    public function getChildrenForIndependentMenu(): array
    {
        $IndependentMenu = Independent\Handler::getMenu($this->getAttribute('menuId'));

        return $IndependentMenu->getChildren();
    }

    /**
     * Get sites for QUI site
     *
     * @return array<int, QUI\Projects\Site>|int
     * @throws Exception
     */
    public function getChildrenForQUISite(): array|int
    {
        $Project = $this->getProject();

        // start
        try {
            $startId = $this->getAttribute('startId');

            if (Utils::isSiteLink($startId)) {
                $Site = Utils::getSiteByLink($startId);
            } else {
                $Site = $Project->get((int)$startId);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return [];
        }

        return $this->getChildren($Site);
    }

    /**
     * @param QUI\Projects\Site $Site
     * @return array<int, QUI\Projects\Site>|int
     * @throws QUI\Exception
     */
    public function getChildren(QUI\Projects\Site $Site): array|int
    {
        if (!$this->getAttribute('showAllChildren')) {
            return $Site->getNavigation();
        }

        return $Site->getChildren();
    }

    /**
     * Set custom css variable to the control as inline style
     * --_quiqqer-menu-submenu-$name: var(--qui-submenu-$name, $value);
     *
     * Example:
     *     --_qui-submenu-linkColor: var(--quiqqer-menu-submenu-linkColor, inherit);
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
            '--_quiqqer-menu-submenu__' . $name,
            'var(--quiqqer-menu-submenu__' . $name . ', ' . $value . ')'
        );
    }
}
