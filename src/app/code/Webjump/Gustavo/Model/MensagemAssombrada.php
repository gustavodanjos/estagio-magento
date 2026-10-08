<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model;

class MensagemAssombrada
{
    public const FIELD_CODE = 'webjump_gustavo_mensagem';
    public const MAX_LENGTH = 255;

    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
