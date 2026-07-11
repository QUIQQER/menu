<?php

namespace QUITests\Menu\Independent\Items;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Menu\Independent\Items\Custom;

class CustomTest extends TestCase
{
    public function testGetUrlReturnsOldSingleLanguageFormatAsIs(): void
    {
        $Item = new Custom([
            'data' => [
                'url' => 'index.php?project=test&lang=de&id=5'
            ]
        ]);

        $this->assertSame('index.php?project=test&lang=de&id=5', $Item->getUrl());
    }

    public function testGetUrlReturnsUrlOfCurrentLanguage(): void
    {
        $current = QUI::getLocale()->getCurrent();

        $Item = new Custom([
            'data' => [
                'url' => [
                    $current => 'https://example.com/' . $current,
                    'zz' => 'https://example.com/zz'
                ]
            ]
        ]);

        $this->assertSame('https://example.com/' . $current, $Item->getUrl());
    }
}
