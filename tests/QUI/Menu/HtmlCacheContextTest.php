<?php

namespace QUITests\Menu;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\Menu\DropDownMenu;
use QUI\Menu\EventHandler;
use QUI\Menu\Independent;
use QUI\Menu\MegaMenu;

class HtmlCacheContextTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<MegaMenu|DropDownMenu>, string}>
     */
    public static function menuTypes(): iterable
    {
        yield 'mega menu' => [MegaMenu::class, 'megaMenu'];
        yield 'dropdown menu' => [DropDownMenu::class, 'dropDownMenu'];
    }

    #[DataProvider('menuTypes')]
    public function testMenuCacheHitsUseTheCurrentImageContext(string $class, string $prefix): void
    {
        $Project = $this->createMock(QUI\Projects\Project::class);
        $Project->method('getConfig')->willReturn(false);
        $Site = $this->createMock(QUI\Interfaces\Projects\Site::class);
        $attributes = [
            'Project' => $Project,
            'Site' => $Site,
            'id' => 'phpunit-html-context-' . uniqid(),
            'enableMobile' => false
        ];
        $Menu = new $class($attributes);
        $cacheAttributes = array_filter($Menu->getAttributes(), static fn ($entry) => !is_object($entry));
        $legacyKey = EventHandler::menuCacheName() . '/' . $prefix . '/' . md5(serialize($cacheAttributes));
        $wrap = static fn (string $html): mixed => $class === MegaMenu::class
            ? ['subMenus' => [], 'html' => $html]
            : $html;

        $this->assertContextualCacheHits(
            $legacyKey,
            $wrap,
            static fn (): string => (new $class($attributes))->getBody()
        );
    }

    public function testIndependentMenuCacheHitsUseTheCurrentImageContext(): void
    {
        $id = random_int(100000000, 200000000);
        $legacyKey = Independent\Handler::getMenuCacheName($id, QUI::getRewrite()->getProject());

        $this->assertContextualCacheHits(
            $legacyKey,
            static fn (string $html): string => $html,
            static fn (): string => Independent\Smarty::menu([
                'id' => $id,
                'control' => 'UnusedOnCacheHit'
            ], null)
        );
    }

    private function assertContextualCacheHits(string $legacyKey, callable $wrap, callable $render): void
    {
        $webp = false;
        $listener = static function (array &$context) use (&$webp): void {
            $context['test.webp'] = $webp;
        };
        $Events = QUI::getEvents();
        $Config = CacheManager::getConfig();
        $original = $Config->getSection('general');
        $keys = [$legacyKey];
        $html = '<picture><img src="/media/cache/demo/image.jpg"></picture>';
        $webpHtml = '<picture><source type="image/webp" srcset="/media/cache/demo/image.webp">'
            . '<img src="/media/cache/demo/image.jpg"></picture>';
        $Events->addEvent('onQuiqqerMenuHtmlCacheContext', $listener);

        try {
            $Config->set('general', 'nocache', 0);
            CacheManager::set($legacyKey, $wrap('legacy HTML must not be reused'));

            foreach ([false, true] as $webp) {
                $key = $legacyKey . '/' . EventHandler::htmlCacheContext();
                $keys[] = $key;
                CacheManager::set($key, $wrap($webp ? $webpHtml : $html));
            }

            foreach ([false, true, true, false] as $webp) {
                $this->assertSame($webp ? $webpHtml : $html, $render());
            }

            // Existing menu invalidation must still clear all request variants.
            CacheManager::clear($legacyKey);
            $this->expectException(QUI\Cache\Exception::class);
            CacheManager::get($keys[1]);
        } finally {
            $Events->removeEvent('onQuiqqerMenuHtmlCacheContext', $listener);

            foreach ($keys as $key) {
                CacheManager::clear($key);
            }

            if (is_array($original)) {
                $Config->setSection('general', $original);
            } else {
                $Config->del('general');
            }
        }
    }
}
