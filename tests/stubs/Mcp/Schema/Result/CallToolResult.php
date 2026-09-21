<?php

namespace Mcp\Schema\Result;

if (!class_exists(CallToolResult::class)) {
    class CallToolResult
    {
        public bool $isError = false;
        public string $message = '';
    }
}
