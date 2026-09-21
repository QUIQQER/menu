<?php

namespace QUI\Menu\Independent;

use QUI\Exception;

/** Shared decoding for legacy JSON strings and localized MCP objects. */
final class LocalizedValue
{
    /**
     * Read legacy titles without applying the MCP write contract to plain text.
     *
     * @return array<string, string>|string
     */
    public static function decodeForRead(mixed $value, string $path): array | string
    {
        // Legacy Site controls can store false for their unused localized title.
        if ($value === null || $value === false) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $value;
            }

            if ($decoded === null || $decoded === false) {
                return [];
            }

            if (is_string($decoded)) {
                return $decoded;
            }

            if (!is_array($decoded)) {
                return $value;
            }

            $value = $decoded;
        }

        return self::decode($value, $path);
    }

    /** @return array<string, string> */
    public static function decode(mixed $value, string $path): array
    {
        // Legacy controls also store an empty string for an unset localized field.
        if ($value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            throw new Exception($path . ': expected a localized object or its JSON string.', 400);
        }

        foreach ($value as $language => $text) {
            if (!is_string($language) || !is_string($text)) {
                throw new Exception($path . '.' . $language . ': expected a string translation.', 400);
            }
        }

        return $value;
    }

    public static function encode(mixed $value, string $path): string
    {
        $translations = self::decode($value, $path);
        try {
            return json_encode((object)$translations, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new Exception($path . ': translations cannot be encoded as JSON.', 400);
        }
    }
}
