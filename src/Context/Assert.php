<?php

declare(strict_types = 1);

namespace Cheppers\DrupalExtension\Context;

use PHPUnit\Framework\Assert as A;

class Assert
{
    public static function assertOneOfStringsMatchesFormat(string $format, array $strings, string $message = '')
    {
        foreach ($strings as $string) {
            try {
                A::assertStringMatchesFormat($format, $string);
            } catch (\Exception $e) {
                continue;
            }

            return;
        }

        $message = $message ?: "Pattern $format matches to one of the provided items";
        A::fail($message);
    }

    public static function assertNonOfStringsMatchesFormat(string $format, array $strings, string $message = '')
    {
        foreach ($strings as $string) {
            A::assertStringNotMatchesFormat(
                $format,
                $string,
                $message ?: "Pattern $format doesn't matches to any of the provided items"
            );
        }
    }
}
