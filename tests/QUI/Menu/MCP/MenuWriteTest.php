<?php

namespace QUITests\Menu\MCP;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Menu\Independent\Handler;
use QUI\Menu\Independent\Items\Custom;
use QUI\Menu\Independent\Items\Url;
use QUI\Menu\Independent\Menu;
use QUI\Menu\MCP\Independent\AddMenuItem;
use QUI\Menu\MCP\Independent\CreateMenu;
use QUI\Menu\MCP\Independent\DeleteMenuItem;
use QUI\Menu\MCP\Independent\GetMenu;
use QUI\Menu\MCP\Independent\UpdateMenu;
use QUI\Menu\MCP\Independent\UpdateMenuItem;
use ReflectionProperty;
use RuntimeException;

/** Real DBAL writes in a private database; no production records or caches. */
class MenuWriteTest extends TestCase
{
    private Connection $Connection;
    private Connection $PreviousConnection;
    private ?QUI\Events\Manager $PreviousEvents;
    private ?QUI\Interfaces\Users\User $PreviousUser;
    private Builder $Builder;
    private array $events = [];
    private bool $failEvents = false;
    private string $cacheKey;

    protected function setUp(): void
    {
        if (!property_exists(Server::class, 'RequestUser') || !property_exists(Builder::class, 'tools')) {
            $this->markTestSkipped('This test uses the optional MCP transport stubs.');
        }

        if (getenv('GITLAB_CI') !== 'true' && !str_starts_with(VAR_DIR, sys_get_temp_dir() . '/')) {
            $this->markTestSkipped('An isolated QUIQQER runtime is required for cache tests.');
        }

        $this->PreviousConnection = QUI::getDataBaseConnection();
        $this->Connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $this->Connection);
        $Table = new Table(Handler::table());
        $Table->addColumn('id', 'integer', ['autoincrement' => true]);
        $Table->setPrimaryKey(['id']);
        foreach (['title', 'workingTitle', 'data'] as $column) {
            $Table->addColumn($column, 'text');
        }
        $this->Connection->createSchemaManager()->createTable($Table);
        $this->Connection->insert(Handler::table(), [
            'id' => 1,
            'title' => '{"de":"Test","en":"Test"}',
            'workingTitle' => '{}',
            'data' => json_encode(['children' => [$this->item('original')]], JSON_THROW_ON_ERROR)
        ]);

        $this->PreviousUser = (new ReflectionProperty(Server::class, 'RequestUser'))->getValue();
        $User = $this->createMock(QUI\Interfaces\Users\User::class);
        $User->method('isSU')->willReturn(true);
        (new ReflectionProperty(Server::class, 'RequestUser'))->setValue(null, $User);
        $this->PreviousEvents = QUI::$Events;
        $callback = function (string $event): void {
            $this->events[] = [$event, $this->Connection->isTransactionActive()];
            if ($this->failEvents) {
                throw new RuntimeException('Simulated notification failure');
            }
        };
        QUI::$Events = new class ($callback) extends QUI\Events\Manager {
            public function __construct(private \Closure $callback)
            {
            }

            public function fireEvent(string $event, false | array $args = false, bool $force = false): array
            {
                ($this->callback)($event);
                return [];
            }
        };
        $this->cacheKey = Handler::getMenuCacheName(1) . '/mcp-regression';
        QUI\Cache\Manager::set($this->cacheKey, 'existing frontend');
        $this->Builder = new Builder();
        foreach (
            [CreateMenu::class, UpdateMenu::class, AddMenuItem::class, UpdateMenuItem::class,
            DeleteMenuItem::class, GetMenu::class] as $tool
        ) {
            (new $tool())->register($this->Builder);
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->PreviousConnection)) {
            return;
        }
        QUI\Cache\Manager::clear($this->cacheKey);
        QUI::$Events = $this->PreviousEvents;
        (new ReflectionProperty(Server::class, 'RequestUser'))->setValue(null, $this->PreviousUser);
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $this->PreviousConnection);
        $this->Connection->close();
    }

    public function testCreateGetUpdateGetPreservesNestedTranslationsAndOrder(): void
    {
        $child = $this->item('child');
        $child['title'] = ['de' => 'Häufige Fragen', 'en' => 'FAQ'];
        $child['type'] = Custom::class;
        $child['data']['name'] = ['de' => 'Fragen', 'en' => 'Questions'];
        $child['data']['short'] = ['de' => 'Hilfe', 'en' => 'Help'];
        $child['data']['url'] = ['de' => '/fragen', 'en' => '/faq'];
        $parent = $this->item('parent');
        $parent['children'] = [$child];
        $result = $this->call('create', ['data' => ['children' => [$parent, $this->item('second')]]]);
        $this->assertIsArray($result);
        $id = $result['menu']['id'];
        $first = $this->call('get', ['id' => $id]);
        $this->assertIsArray($first);
        $this->assertSame(['parent', 'second'], array_column($first['menu']['data']['children'], 'identifier'));
        $nested = $first['menu']['data']['children'][0]['children'][0];
        $this->assertSame($child['title'], json_decode($nested['title'], true));
        $this->assertSame($child['data']['short'], json_decode($nested['data']['short'], true));
        $this->assertIsArray($this->call('update', ['id' => $id, 'data' => $first['menu']['data']]));
        $this->assertSame($first, $this->call('get', ['id' => $id]));
        $Item = Handler::getMenu($id)->getChildren()[0]->getChildren()[0];
        $this->assertSame('FAQ', $Item->getTitle(new QUI\Locale('en')));
        $this->assertSame('/faq', $Item->getUrl(new QUI\Locale('en')));
        $this->assertSame('Questions', $Item->getName(new QUI\Locale('en')));
        $this->assertSame('Help', $Item->getShort(new QUI\Locale('en')));
        foreach ($this->events as [, $active]) {
            $this->assertFalse($active, 'Events must only run after commit.');
        }
    }

    public function testObjectKeyOrderAndPartialUpdateWithoutTitle(): void
    {
        $item = array_reverse($this->item('added'), true);
        $item['title'] = ['en' => 'Added', 'de' => 'Neu'];
        $item['children'] = [];
        $result = $this->call('item_add', ['menuId' => 1, 'item' => $item, 'position' => 0]);
        $this->assertIsArray($result);
        $this->assertSame(['added', 'original'], array_column($result['menu']['data']['children'], 'identifier'));
        $result = $this->call('item_update', ['menuId' => 1, 'identifier' => 'added', 'patch' => ['icon' => 'fa fa-home']]);
        $this->assertIsArray($result);
        $this->assertSame($item['title'], json_decode($result['menu']['data']['children'][0]['title'], true));
        $this->assertSame([], $result['menu']['data']['children'][0]['children']);
        $before = $result['menu']['data'];
        $result = $this->call('update', ['id' => 1, 'title' => ['de' => 'Neuer Menütitel']]);
        $this->assertIsArray($result);
        $this->assertSame($before, $result['menu']['data']);
        $result = $this->call('item_delete', ['menuId' => 1, 'identifier' => 'added']);
        $this->assertIsArray($result);
        $this->assertSame(['original'], array_column($result['menu']['data']['children'], 'identifier'));
    }

    public function testInvalidNestedDataLeavesDatabaseFrontendAndCacheUnchanged(): void
    {
        $badValues = [null, 42, ['de' => ['bad']], '{invalid', 'null', '["bad"]', 'Startseite'];
        foreach ($badValues as $value) {
            $child = $this->item('child');
            $child['title'] = $value;
            $parent = $this->item('parent');
            $parent['children'] = [$child];
            $before = Handler::getMenuData(1);
            $result = $this->call('update', ['id' => 1, 'data' => ['children' => [$parent]]]);
            $this->assertFailure($result, 'data.children[0].children[0].title');
            $this->assertUnchanged($before);
            $result = $this->call('create', ['data' => ['children' => [$parent]]]);
            $this->assertFailure($result, 'data.children[0].children[0].title');
            $this->assertSame(1, (int)$this->Connection->fetchOne('SELECT COUNT(*) FROM ' . Handler::table()));
        }
    }

    public function testInvalidItemPatchAndNonObjectChildrenAreRejected(): void
    {
        $before = Handler::getMenuData(1);
        foreach ([['title' => ['de' => 7]], ['data' => null], ['unexpected' => 1], ['children' => [false]]] as $patch) {
            $result = $this->call('item_update', ['menuId' => 1, 'identifier' => 'original', 'patch' => $patch]);
            $this->assertFailure($result);
            $this->assertUnchanged($before);
        }
        foreach ([['children' => 'invalid'], ['children' => [false]], ['children' => ['key' => $this->item('x')]]] as $data) {
            $this->assertFailure($this->call('update', ['id' => 1, 'data' => $data]), 'data.children');
            $this->assertUnchanged($before);
        }
        $item = $this->item('original');
        $this->assertFailure($this->call('item_add', ['menuId' => 1, 'item' => $item]), 'identifier');
        $this->assertUnchanged($before);
    }

    public function testPostWriteVerificationFailureRollsBackUpdateAndCreate(): void
    {
        $before = Handler::getMenuData(1);
        // Simulate a database-side change after the write, before the verification read.
        foreach (['UPDATE', 'INSERT'] as $operation) {
            $this->Connection->executeStatement('CREATE TRIGGER corrupt_' . strtolower($operation)
                . ' AFTER ' . $operation . ' ON ' . Handler::table()
                . ' BEGIN UPDATE ' . Handler::table() . " SET data = 'invalid' WHERE id = NEW.id; END");
        }
        $this->assertFailure($this->call('update', ['id' => 1, 'data' => ['children' => []]]), 'stored menu');
        $this->assertUnchanged($before);
        $this->assertFailure($this->call('create', ['data' => ['children' => [$this->item('new')]]]), 'stored menu');
        $this->assertSame(1, (int)$this->Connection->fetchOne('SELECT COUNT(*) FROM ' . Handler::table()));
        $this->assertUnchanged($before);
    }

    public function testPostCommitNotificationErrorExplicitlyReportsSaved(): void
    {
        $this->failEvents = true;
        $result = $this->call('update', ['id' => 1, 'data' => ['children' => []]]);
        $this->assertIsArray($result);
        $this->assertTrue($result['menu']['saved']);
        $this->assertStringContainsString('Menu was saved', $result['menu']['warnings'][0]);
        $this->assertSame([], Handler::getMenu(1)->getChildren());
    }

    public function testBackendListAndFrontendLoadExistingObjectTitles(): void
    {
        $item = $this->item('original');
        $item['title'] = ['de' => 'FAQ', 'en' => 'FAQ'];
        $item['children'] = [$this->item('nested')];
        $item['children'][0]['title'] = ['de' => 'Kind', 'en' => 'Child'];
        $this->Connection->update(Handler::table(), ['data' => json_encode(['children' => [$item]])], ['id' => 1]);
        $menus = Handler::getList();
        $this->assertCount(1, $menus);
        $this->assertSame('FAQ', $menus[0]->getChildren()[0]->getTitle(new QUI\Locale('de')));
        $this->assertSame('Child', Handler::getMenu(1)->getChildren()[0]->getChildren()[0]->getTitle(new QUI\Locale('en')));
    }

    private function call(string $name, array $arguments): array | CallToolResult
    {
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($this->Builder);
        foreach ($tools as $tool) {
            if ($tool['name'] === 'quiqqer_menu_' . $name) {
                return ($tool['handler'])(...$arguments);
            }
        }
        throw new RuntimeException('Tool not registered: ' . $name);
    }

    private function assertFailure(mixed $result, string $path = ''): void
    {
        $this->assertInstanceOf(CallToolResult::class, $result);
        $this->assertTrue($result->isError);
        $message = $result->message ?? $result->content[0]->text;
        $this->assertStringContainsString('No changes saved.', $message);
        if ($path !== '') {
            $this->assertStringContainsString($path, $message);
        }
    }

    private function assertUnchanged(array $before): void
    {
        $this->assertSame($before, Handler::getMenuData(1));
        $this->assertSame('original', Handler::getMenu(1)->getChildren()[0]->getTitle(new QUI\Locale('en')));
        $this->assertSame('existing frontend', QUI\Cache\Manager::get($this->cacheKey));
        $this->assertSame([], $this->events);
        $this->assertFalse($this->Connection->isTransactionActive());
    }

    private function item(string $identifier): array
    {
        return [
            'identifier' => $identifier,
            'type' => Url::class,
            'title' => json_encode(['de' => $identifier, 'en' => $identifier]),
            'data' => ['url' => '/' . $identifier, 'status' => 1]
        ];
    }
}
