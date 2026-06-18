<?php

namespace QUI\Menu\Rest;

use Exception;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use QUI;
use QUI\CoreRest\Handler;
use QUI\Menu\Independent\Factory as MenuFactory;
use QUI\Menu\Independent\Handler as MenuHandler;
use QUI\REST\ProviderInterface;
use QUI\REST\Server;
use QUI\Utils\Security\Orthos;
use Slim\Routing\RouteCollectorProxy;

use function get_object_vars;
use function is_array;
use function is_object;
use function is_string;

/**
 * REST API endpoints for QUIQQER Menus.
 */
class Provider implements ProviderInterface
{
    /**
     * Registered some REST Api Calls
     *
     * @param Server $Server
     */
    public function register(Server $Server): void
    {
        $Slim = $Server->getSlim();

        $Slim->group('/menus', function (RouteCollectorProxy $RouteCollector) {
            $RouteCollector->post('/create', [$this, 'create']);
            $RouteCollector->get('/get', [$this, 'get']);
            $RouteCollector->patch('/update', [$this, 'update']);
            $RouteCollector->delete('/delete', [$this, 'delete']);
        });
    }

    /**
     * CREATE a new QUIQQER Menu
     *
     * @param RequestInterface $Request
     * @param ResponseInterface $Response
     * @return MessageInterface
     */
    public function create(RequestInterface $Request, ResponseInterface $Response): MessageInterface
    {
        $params = self::getParsedBodyData($Request);
        $title = self::getLocaleMap($params['title'] ?? null);

        if ($title === null) {
            return Handler::getGenericErrorResponse('Field "title" is missing.');
        }

        $menu = [
            'title' => $title
        ];

        if (!empty($params['id'])) {
            $menu['id'] = (int)Orthos::clear($params['id']);
        }

        $workingTitle = self::getLocaleMap($params['workingTitle'] ?? null);

        if ($workingTitle !== null) {
            $menu['workingTitle'] = $workingTitle;
        }

        if (isset($params['data']) && is_array($params['data'])) {
            $menu['data'] = $params['data'];
        }

        try {
            $menuId = false;

            if (!empty($menu['id'])) {
                $menuId = $menu['id'];

                try {
                    $Menu = MenuHandler::getMenu($menuId);
                } catch (Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                    $Menu = false;
                }

                if ($Menu) {
                    throw new QUI\Exception(
                        'Menu with specific id #' . $menuId . ' cannot be created, since a menu with this id already'
                        . ' exists.'
                    );
                }
            }

            $Menu = MenuFactory::createMenu();
            $newMenuId = $Menu->getId();

            if ($menuId) {
                QUI::getDataBase()->update(
                    MenuHandler::table(),
                    [
                        'id' => $menuId,
                    ],
                    [
                        'id' => $newMenuId
                    ]
                );

                $newMenuId = $menuId;
                $Menu = MenuHandler::getMenu($newMenuId);
            }

            $Menu->setTitle($menu['title']);

            if (!empty($menu['workingTitle'])) {
                $Menu->setWorkingTitle($menu['workingTitle']);
            }

            if (!empty($menu['data'])) {
                $Menu->setData($menu['data']);
            }

            $Menu->save(QUI::getUsers()->getSystemUser());
        } catch (Exception $Exception) {
            return Handler::getGenericExceptionResponse($Exception);
        }

        return Handler::getGenericSuccessResponse('Menu created.', [
            'id' => $newMenuId
        ]);
    }

    /**
     * GET data of a QUIQQER Menu
     *
     * @param RequestInterface $Request
     * @param ResponseInterface $Response
     * @return MessageInterface
     */
    public function get(RequestInterface $Request, ResponseInterface $Response): MessageInterface
    {
        $params = self::getParsedBodyData($Request);

        if (empty($params['id'])) {
            return Handler::getGenericErrorResponse('Field "id" is missing.');
        }

        $menuId = (int)Orthos::clear($params['id']);

        try {
            $Menu = MenuHandler::getMenu($menuId);
        } catch (Exception $Exception) {
            return Handler::getGenericExceptionResponse($Exception);
        }

        return Handler::getGenericSuccessResponse(
            null,
            $Menu->toArray()
        );
    }

    /**
     * UPDATE data of a QUIQQER Menu
     *
     * @param RequestInterface $Request
     * @param ResponseInterface $Response
     * @return MessageInterface
     */
    public function update(RequestInterface $Request, ResponseInterface $Response): MessageInterface
    {
        $params = self::getParsedBodyData($Request);

        if (empty($params['id'])) {
            return Handler::getGenericErrorResponse('Field "id" is missing.');
        }

        $menu = [
            'id' => (int)Orthos::clear($params['id'])
        ];

        $title = self::getLocaleMap($params['title'] ?? null);

        if ($title !== null) {
            $menu['title'] = $title;
        }

        $workingTitle = self::getLocaleMap($params['workingTitle'] ?? null);

        if ($workingTitle !== null) {
            $menu['workingTitle'] = $workingTitle;
        }

        if (isset($params['data']) && is_array($params['data'])) {
            $menu['data'] = $params['data'];
        }

        try {
            $Menu = MenuHandler::getMenu($menu['id']);

            if (!empty($menu['title'])) {
                $Menu->setTitle($menu['title']);
            }

            if (!empty($menu['workingTitle'])) {
                $Menu->setWorkingTitle($menu['workingTitle']);
            }

            if (!empty($menu['data'])) {
                $Menu->setData($menu['data']);
            }

            $Menu->save(QUI::getUsers()->getSystemUser());
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return Handler::getGenericExceptionResponse($Exception);
        }

        return Handler::getGenericSuccessResponse(
            'Menu #' . $menu['id'] . ' successfully updated.',
            $Menu->toArray()
        );
    }

    /**
     * DELETE a QUIQQER Site
     *
     * @param RequestInterface $Request
     * @param ResponseInterface $Response
     * @return MessageInterface
     */
    public function delete(RequestInterface $Request, ResponseInterface $Response): MessageInterface
    {
        $params = self::getParsedBodyData($Request);

        if (empty($params['id'])) {
            return Handler::getGenericErrorResponse('Field "id" is missing.');
        }

        $menuId = (int)Orthos::clear($params['id']);

        try {
            MenuFactory::deleteMenu($menuId);
        } catch (Exception $Exception) {
            return Handler::getGenericExceptionResponse($Exception);
        }

        return Handler::getGenericSuccessResponse(
            'Menu #' . $menuId . ' successfully deleted.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getParsedBodyData(RequestInterface $Request): array
    {
        $params = $Request->getParsedBody();

        if (is_array($params)) {
            return $params;
        }

        if (is_object($params)) {
            return get_object_vars($params);
        }

        return [];
    }

    /**
     * @return array<string, string>|null
     */
    private static function getLocaleMap(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $value = Orthos::clearArray($value);
        $result = [];

        foreach ($value as $language => $text) {
            if (is_string($language) && is_string($text)) {
                $result[$language] = $text;
            }
        }

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Get file containing OpenApi definition for this API.
     *
     * @return string|false - Absolute file path or false if no definition exists
     */
    public function getOpenApiDefinitionFile(): bool|string
    {
        return false;
    }

    /**
     * Get unique internal API name.
     *
     * This is required for requesting specific data about an API (i.e. OpenApi definition).
     *
     * @return string - Only letters; no other characters!
     */
    public function getName(): string
    {
        return 'QuiqqerMenus';
    }

    /**
     * Get title of this API.
     *
     * @param QUI\Locale|null $Locale (optional)
     * @return string
     */
    public function getTitle(?QUI\Locale $Locale = null): string
    {
        if (empty($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/menu', 'provider.Rest.title');
    }
}
