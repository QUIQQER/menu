<?php

namespace QUITests\Menu\MCP;

use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Menu\Independent\Items\Anchor;
use QUI\Menu\Independent\Items\Custom;
use QUI\Menu\Independent\Items\Site;
use QUI\Menu\Independent\Items\Url;

class AbstractToolTest extends TestCase
{
    public function testItemTypesContainSchemasAndExamples(): void
    {
        $types = MenuMcpToolTestAccessor::itemTypes();
        $byType = [];

        foreach ($types as $type) {
            $byType[$type['type']] = $type;
        }

        foreach ([Site::class, Anchor::class, Url::class, Custom::class] as $class) {
            $this->assertArrayHasKey($class, $byType);
            $this->assertArrayHasKey('schema', $byType[$class]);
            $this->assertArrayHasKey('example', $byType[$class]);
            $this->assertSame($class, $byType[$class]['schema']['properties']['type']['const']);
        }

        $this->assertContains('site', $byType[Site::class]['schema']['properties']['data']['required']);
        $this->assertContains('url', $byType[Url::class]['schema']['properties']['data']['required']);
        $this->assertSame(['site', 'url'], $byType[Anchor::class]['schema']['properties']['data']['required']);
    }

    public function testAddItemSupportsRootChildBeforeAndAfterPlacement(): void
    {
        $data = ['children' => []];
        $data = MenuMcpToolTestAccessor::addItem($data, $this->urlItem('first', '/first'), 'root');
        $data = MenuMcpToolTestAccessor::addItem($data, $this->urlItem('after-first', '/after'), 'after', 'first');
        $data = MenuMcpToolTestAccessor::addItem($data, $this->urlItem('before-first', '/before'), 'before', 'first');
        $data = MenuMcpToolTestAccessor::addItem($data, $this->urlItem('child', '/child'), 'child', 'first');

        $this->assertSame('before-first', $data['children'][0]['identifier']);
        $this->assertSame('first', $data['children'][1]['identifier']);
        $this->assertSame('after-first', $data['children'][2]['identifier']);
        $this->assertSame('child', $data['children'][1]['children'][0]['identifier']);
    }

    public function testUpdateItemCanChangeTypeWhenNewDataIsValid(): void
    {
        $data = [
            'children' => [
                $this->urlItem('link', '/old')
            ]
        ];

        $data = MenuMcpToolTestAccessor::updateItem($data, 'link', [
            'type' => Custom::class,
            'data' => [
                'url' => '/custom',
                'name' => ['de' => 'Custom'],
                'short' => ['de' => 'Short'],
                'status' => 1
            ]
        ]);

        $this->assertSame(Custom::class, $data['children'][0]['type']);
        $this->assertSame('/custom', $data['children'][0]['data']['url']);
    }

    public function testUpdateItemRejectsInvalidTypeChange(): void
    {
        $this->expectException(Exception::class);

        MenuMcpToolTestAccessor::updateItem([
            'children' => [
                $this->urlItem('link', '/old')
            ]
        ], 'link', [
            'type' => Site::class,
            'data' => [
                'status' => 1
            ]
        ]);
    }

    public function testDeleteItemRemovesNestedItemByIdentifier(): void
    {
        $data = [
            'children' => [
                [
                    'identifier' => 'parent',
                    'type' => Url::class,
                    'title' => ['de' => 'Parent'],
                    'data' => ['url' => '/parent'],
                    'children' => [
                        $this->urlItem('child', '/child')
                    ]
                ]
            ]
        ];

        $data = MenuMcpToolTestAccessor::deleteItem($data, 'child');

        $this->assertSame('parent', $data['children'][0]['identifier']);
        $this->assertSame([], $data['children'][0]['children']);
    }

    private function urlItem(string $identifier, string $url): array
    {
        return [
            'identifier' => $identifier,
            'type' => Url::class,
            'title' => ['de' => $identifier],
            'data' => [
                'url' => $url,
                'status' => 1,
                'menuType' => 'Standard'
            ]
        ];
    }
}
