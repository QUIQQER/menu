<?php

namespace Psr\Http\Message;

interface ServerRequestInterface extends MessageInterface
{
    public function getParsedBody(): mixed;
}
