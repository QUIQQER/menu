<?php

namespace QUI\CoreRest;

use Exception;
use Psr\Http\Message\MessageInterface;

class Handler
{
    public static function getGenericErrorResponse(string $message): MessageInterface
    {
    }

    public static function getGenericExceptionResponse(Exception $Exception): MessageInterface
    {
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public static function getGenericSuccessResponse(?string $message = null, ?array $data = null): MessageInterface
    {
    }
}
