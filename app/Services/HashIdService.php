<?php

declare(strict_types=1);

namespace App\Services;

use Hashids\Hashids;

class HashIdService
{
    protected static ?Hashids $encoder = null;

    protected static function getEncoder(): Hashids
    {
        if (self::$encoder === null) {
            $salt = config('app.key', 'default-salt-sekolah-terpadu-2026');
            $minHashLength = 8;
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

            self::$encoder = new Hashids($salt, $minHashLength, $alphabet);
        }

        return self::$encoder;
    }

    public static function encode(int|string $id): string
    {
        return self::getEncoder()->encode((int) $id);
    }

    public static function decode(string $hash): ?int
    {
        $numbers = self::getEncoder()->decode($hash);

        return ! empty($numbers) ? (int) $numbers[0] : null;
    }
}
