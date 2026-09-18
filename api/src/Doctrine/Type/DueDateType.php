<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\ValueObject\Task\DueDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class DueDateType extends Type
{
    private const DUE_DATE = 'dueDate';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DueDate
    {
        if ($value === null) {
            return null;
        }

        return new DueDate(new \DateTimeImmutable($value));
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DueDate) {
            return $value->toDateTimeImmutable()->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getName(): string
    {
        return self::DUE_DATE;
    }
}
