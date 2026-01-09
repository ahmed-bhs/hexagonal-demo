<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\Type;

use App\Cadeau\Attribution\Domain\ValueObject\Age;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class AgeType extends Type
{
    public const NAME = 'age';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Age
    {
        if ($value === null) {
            return null;
        }

        return new Age((int) $value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Age) {
            return $value->value;
        }

        return $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
