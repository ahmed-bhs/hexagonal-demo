<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\Type;

use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class HabitantIdType extends Type
{
    public const NAME = 'habitant_id';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?HabitantId
    {
        if ($value === null) {
            return null;
        }

        return new HabitantId($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof HabitantId) {
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
