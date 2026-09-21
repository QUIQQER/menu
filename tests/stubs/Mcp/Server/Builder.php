<?php

namespace Mcp\Server;

if (!class_exists(Builder::class)) {
    class Builder
    {
        public array $tools = [];

        /**
         * @param callable $callback
         * @param array<string, mixed>|null $inputSchema
         */
        public function addTool(
            callable $callback,
            string $name,
            string $description,
            ?array $inputSchema = null
        ): void {
            $this->tools[] = ['handler' => $callback, 'name' => $name];
        }
    }
}
