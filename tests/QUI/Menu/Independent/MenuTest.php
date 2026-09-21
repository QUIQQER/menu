<?php

namespace QUITests\Menu\Independent;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Locale;
use QUI\Menu\Independent\Items\Anchor;
use QUI\Menu\Independent\Items\Custom;
use QUI\Menu\Independent\Items\Url;
use QUI\Menu\Independent\Menu;

class MenuTest extends TestCase
{
    public static function legacyTitles(): array
    {
        return [
            'plain text' => ['Startseite', 'Startseite', 'Startseite'],
            'empty' => ['', '', ''],
            'null' => [null, '', ''],
            'JSON null' => ['null', '', ''],
            'JSON string' => ['"Startseite"', 'Startseite', 'Startseite'],
            'JSON string null' => ['"null"', 'null', 'null'],
            'numeric text' => ['2026', '2026', '2026'],
            'HTML text' => ['<b>Start & mehr</b>', '<b>Start & mehr</b>', '<b>Start & mehr</b>'],
            'JSON translations' => ['{"de":"Startseite","en":"Home"}', 'Startseite', 'Home'],
            'translations' => [['de' => 'Startseite', 'en' => 'Home'], 'Startseite', 'Home'],
            'missing translation' => [['de' => 'Startseite'], 'Startseite', ''],
            'empty translation' => [['de' => '', 'en' => 'Home'], '', 'Home']
        ];
    }

    #[DataProvider('legacyTitles')]
    public function testLegacyTitlesLoadAtEveryDepthAndResolveWithoutChangingStoredData(
        mixed $title,
        string $german,
        string $english
    ): void {
        $German = $this->createConfiguredMock(Locale::class, ['getCurrent' => 'de']);
        $English = $this->createConfiguredMock(Locale::class, ['getCurrent' => 'en']);

        foreach ([Url::class, Custom::class, Anchor::class] as $type) {
            $item = ['type' => $type, 'title' => $title];
            $data = ['children' => [$item + ['children' => [$item]]]];
            $Menu = new Menu([
                'id' => 1,
                'title' => [],
                'workingTitle' => [],
                'data' => json_encode($data, JSON_THROW_ON_ERROR)
            ]);
            $Parent = $Menu->getChildren()[0];

            foreach ([$Parent, $Parent->getChildren()[0], new $type($item)] as $Item) {
                $this->assertSame($german, $Item->getTitle($German));
                $this->assertSame($english, $Item->getTitle($English));
            }

            $this->assertSame($data, $Menu->getData()['data']);
        }
    }

    public function testMissingTitleIsEmpty(): void
    {
        $this->assertSame('', (new Url())->getTitle(new Locale('de')));
    }

    public function testPlainTitleIsEscapedWhenRenderingHtml(): void
    {
        $Item = new Url([
            'title' => '"><script>alert(1)</script>&',
            'data' => ['url' => '/']
        ]);

        $html = $Item->getHTML(new Locale('de'));
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString(
            'title="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&amp;"',
            $html
        );
        $this->assertStringContainsString('>&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&amp;</a>', $html);
    }

    public function testInvalidNestedTitleReportsItsFieldInsteadOfATypeError(): void
    {
        $this->expectException(\QUI\Exception::class);
        $this->expectExceptionMessage('data.children[0].children[0].title');
        new Menu([
            'id' => 1,
            'title' => [],
            'workingTitle' => [],
            'data' => ['children' => [[
                'type' => Url::class,
                'title' => '{"de":"Parent"}',
                'children' => [[
                    'type' => Url::class,
                    'title' => ['de' => 42]
                ]]
            ]]]
        ]);
    }

    public function testSanitizeDataKeepsExistingIdentifiers(): void
    {
        $Menu = $this->createMenu();

        $sanitized = $Menu->sanitizeData([
            'children' => [
                [
                    'identifier' => 'existing-id',
                    'type' => Url::class,
                    'title' => ['de' => 'Start', 'en' => 'Home'],
                    'icon' => 'fa fa-home',
                    'data' => [
                        'url' => '/',
                        'status' => 1
                    ]
                ]
            ]
        ]);

        $this->assertIsArray($sanitized);
        $this->assertSame('existing-id', $sanitized['children'][0]['identifier']);
    }

    public function testSanitizeDataRemovesInvalidItems(): void
    {
        $Menu = $this->createMenu();

        $sanitized = $Menu->sanitizeData([
            'children' => [
                [
                    'identifier' => 'invalid-without-type',
                    'title' => ['de' => 'Invalid']
                ],
                [
                    'identifier' => 'valid',
                    'type' => Url::class,
                    'title' => ['de' => 'Valid'],
                    'data' => ['url' => '/valid']
                ]
            ]
        ]);

        $this->assertIsArray($sanitized);
        $this->assertCount(1, $sanitized['children']);
        $this->assertSame('valid', $sanitized['children'][0]['identifier']);
    }

    public function testSanitizeDataKeepsNestedValidatedChildren(): void
    {
        $Menu = $this->createMenu();

        $sanitized = $Menu->sanitizeData([
            'children' => [
                [
                    'identifier' => 'parent',
                    'type' => Url::class,
                    'title' => ['de' => 'Parent'],
                    'data' => ['url' => '/parent'],
                    'children' => [
                        [
                            'identifier' => 'child',
                            'type' => Url::class,
                            'title' => ['de' => 'Child'],
                            'data' => ['url' => '/child']
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertIsArray($sanitized);
        $this->assertSame('child', $sanitized['children'][0]['children'][0]['identifier']);
    }

    private function createMenu(): Menu
    {
        return new Menu([
            'id' => 1,
            'title' => [],
            'workingTitle' => [],
            'data' => []
        ]);
    }
}
