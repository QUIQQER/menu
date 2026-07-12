<?php

namespace QUITests\Menu\Independent\Items;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Menu\Independent\Items\Url;

use function json_encode;

class UrlTest extends TestCase
{
    public function testGetUrlReturnsOldSingleLanguageFormatAsIs(): void
    {
        $Item = new Url([
            'data' => [
                'url' => 'https://example.com/page'
            ]
        ]);

        $this->assertSame('https://example.com/page', $Item->getUrl());
    }

    public function testGetUrlReturnsUrlOfCurrentLanguage(): void
    {
        $current = QUI::getLocale()->getCurrent();

        $Item = new Url([
            'data' => [
                'url' => [
                    $current => 'https://example.com/' . $current,
                    'zz' => 'https://example.com/zz'
                ]
            ]
        ]);

        $this->assertSame('https://example.com/' . $current, $Item->getUrl());
    }

    public function testGetUrlDecodesJsonStringAndFallsBackToNonEmptyEntry(): void
    {
        $current = QUI::getLocale()->getCurrent();

        $Item = new Url([
            'data' => [
                'url' => json_encode([
                    $current => '',
                    'zz' => 'https://example.com/zz'
                ])
            ]
        ]);

        $this->assertSame('https://example.com/zz', $Item->getUrl());
    }
}
