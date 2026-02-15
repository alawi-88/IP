<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Pending = 'pending';
    case Qualified = 'qualified';
    case NotQualified = 'not_qualified';
    case Winner = 'winner';

    public static function toArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->name;
        }

        unset($array[self::from('pending')->value]);

        return $array;
    }

    public static function filteredOptions(int $stageCount, int $currentStage): array
    {
        return match (true) {
            $stageCount === 1,
                $currentStage === $stageCount => [
                self::Winner->value => self::Winner->name,
                self::NotQualified->value => self::NotQualified->name,
            ],

            default => [
                self::Qualified->value => self::Qualified->name,
                self::NotQualified->value => self::NotQualified->name,
            ],
        };
    }


}
