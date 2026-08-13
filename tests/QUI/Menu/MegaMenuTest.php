<?php

namespace QUITests\Menu;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Projects\Site;
use QUI\Menu\EventHandler;
use QUI\Menu\MegaMenu;
use QUI\Projects\Project;

class MegaMenuTest extends TestCase
{
    public function testCacheHitKeepsFrontendOptions(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getConfig')->willReturn(false);
        $Site = $this->createMock(Site::class);

        $Menu = new MegaMenu([
            'Project' => $Project,
            'Site' => $Site,
            'enableMobile' => false,
            'showMenuDelay' => 125
        ]);

        $attributes = array_filter($Menu->getAttributes(), function ($entry) {
            return is_object($entry) === false;
        });

        $cacheKey = EventHandler::menuCacheName() . '/megaMenu/' . md5(serialize($attributes));
        $cachedResult = [
            'subMenus' => [],
            'html' => 'cached-mega-menu'
        ];

        QUI\Cache\Manager::clear($cacheKey);
        QUI\Cache\Manager::set($cacheKey, $cachedResult);

        try {
            $html = $Menu->create();

            $this->assertStringContainsString($cachedResult['html'], $html);
            $this->assertStringContainsString('data-qui-options-enablemobile="0"', $html);
            $this->assertStringContainsString('data-qui-options-showmenuafter="125"', $html);
        } finally {
            QUI\Cache\Manager::clear($cacheKey);
        }
    }
}
