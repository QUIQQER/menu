<?php

namespace QUITests\Menu\Independent\Items;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Menu\Independent\Items\Anchor;
use QUI\Projects\Site;

class AnchorTest extends TestCase
{
    public function testGetUrlOmitsEmptyAnchorFragment(): void
    {
        $current = QUI::getLocale()->getCurrent();
        $Site = $this->createConfiguredMock(Site::class, [
            'getUrlRewritten' => '/target-page'
        ]);

        $Anchor = new class ([
            'data' => [
                'url' => [
                    $current => ''
                ]
            ]
        ], $Site) extends Anchor {
            public function __construct(array $attributes, private readonly ?Site $Site)
            {
                parent::__construct($attributes);
            }

            public function getSite(): ?Site
            {
                return $this->Site;
            }
        };

        $this->assertSame('/target-page', $Anchor->getUrl());
    }

    public function testGetUrlNormalizesAnchorFragment(): void
    {
        $current = QUI::getLocale()->getCurrent();
        $Site = $this->createConfiguredMock(Site::class, [
            'getUrlRewritten' => '/target-page'
        ]);

        $Anchor = new class ([
            'data' => [
                'url' => [
                    $current => '#contact'
                ]
            ]
        ], $Site) extends Anchor {
            public function __construct(array $attributes, private readonly ?Site $Site)
            {
                parent::__construct($attributes);
            }

            public function getSite(): ?Site
            {
                return $this->Site;
            }
        };

        $this->assertSame('/target-page#contact', $Anchor->getUrl());
    }
}
