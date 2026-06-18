<?php

namespace QUITests\Menu\Independent;

use PHPUnit\Framework\TestCase;
use QUI\Menu\Independent\Items\Url;
use QUI\Menu\Independent\Menu;

class MenuTest extends TestCase
{
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
