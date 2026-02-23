<?php

namespace QUI\Menu;

use QUI;
use QUI\Config;
use QUI\Interfaces\Projects\Site;
use Smarty;
use SmartyException;
use QUI\Smarty\Collector;

/**
 * Class EventHandler
 *
 * @package QUI\Menu
 */
class EventHandler
{
    /**
     * Cache Manager Configs
     */
    public static ?Config $Config = null;

    /**
     * @return string
     */
    public static function menuCacheName(): string
    {
        return 'quiqqer/package/menu';
    }

    /**
     * @param Site $Site
     */
    public static function onSiteSave(Site $Site): void
    {
        QUI\Cache\Manager::clear(self::menuCacheName());
    }

    /**
     * Clear system cache on project save
     *
     * @return void
     */
    public static function onProjectConfigSave(): void
    {
        QUI\Cache\Manager::clear(self::menuCacheName());
    }

    /**
     * Event : on smarty init
     * add new brickarea function
     *
     * @param Smarty $Smarty
     * @throws SmartyException
     */
    public static function onSmartyInit(Smarty $Smarty): void
    {
        if (!isset($Smarty->registered_plugins['function']['menu'])) {
            $Smarty->registerPlugin("function", "menu", "\\QUI\\Menu\\Independent\\Smarty::menu");
        }

        if (!isset($Smarty->registered_classes['QUI\Utils\Security\Orthos'])) {
            $Smarty->registerClass('QUI\Utils\Security\Orthos', '\QUI\Utils\Security\Orthos');
        }
    }

    /**
     * QUIQQER Event: onAdminLoadFooter
     *
     * @return void
     */
    public static function onAdminLoadFooter(): void
    {
        $jsFile = URL_OPT_DIR . 'quiqqer/menu/bin/onAdminLoadFooter.js';
        echo '<script src="' . $jsFile . '" async></script>';
    }

    /**
     * @param $menuId
     * @return void
     */
    public static function onQuiqqerMenuIndependentClear($menuId): void
    {
        QUI\Cache\Manager::clear(self::menuCacheName());
    }

    /**
     * @param Collector $Collector
     * @param QUI\Template $Template
     * @return void
     */
    public static function onQuiqqerTemplateBodyEnd(Collector $Collector, QUI\Template $Template): void
    {
        $Project = $Template->getAttribute('Project');
        $menuId = intval($Project->getConfig('floatingMenu.settings.menuId'));

        if (!$menuId) {
            return;
        }

        $showLandSwitch = $Project->getConfig('floatingMenu.settings.showLangSwitch');
        $toggleButton = $Project->getConfig('floatingMenu.settings.toggleButton');
        // todo - showToggleButton works not correctly in FloatedNav
        $toggleButton = 'mobile';

        $FloatingMenu = new QUI\Menu\Controls\FloatedNav([
            'menuId' => $menuId,
            'showLangSwitch' => $showLandSwitch,
            'showToggleButton' => $toggleButton
        ]);

        $Collector->append($FloatingMenu->create());
    }
}
