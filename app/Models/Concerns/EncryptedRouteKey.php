<?php

namespace App\Models\Concerns;

trait EncryptedRouteKey
{
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function getRouteKey()
    {
        return static::encodeId($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::findOrFail(static::decodeId($value));
    }

    public function getHashedIdAttribute(): string
    {
        return static::encodeId($this->getKey());
    }

    public static function encodeId($id): string
    {
        return strtr(rtrim(base64_encode(encrypt((string) $id)), '='), '+/', '-_');
    }

    public static function decodeId(string $value): int
    {
        $value = strtr($value, '-_', '+/');
        $pad = strlen($value) % 4;
        if ($pad !== 0) {
            $value .= str_repeat('=', 4 - $pad);
        }

        return (int) decrypt(base64_decode($value));
    }
}
