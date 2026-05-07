<?php

namespace App\Helpers;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DateHelper
{
    public static function timeLeftLabel(CarbonInterface $deadline): ?string
    {
        $days = (int) ceil(Carbon::now()->diffInDays($deadline, false));

        if ($days < 0) {
            return null;
        }

        if ($days === 0) {
            return 'остался последний день';
        }

        if ($days < 7) {
            $count = $days;
            $forms = ['день', 'дня', 'дней'];
            $gender = 'm';
        } elseif ($days < 30) {
            $count = (int) round($days / 7);
            $forms = ['неделя', 'недели', 'недель'];
            $gender = 'f';
        } else {
            $count = (int) round($days / 30);
            $forms = ['месяц', 'месяца', 'месяцев'];
            $gender = 'm';
        }

        $idx = self::pluralIndex($count);
        $verb = self::verbForm($idx, $gender);

        return $verb.' '.$count.' '.$forms[$idx];
    }

    private static function pluralIndex(int $n): int
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 0;
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 1;
        }

        return 2;
    }

    private static function verbForm(int $idx, string $gender): string
    {
        if ($idx === 0) {
            return $gender === 'f' ? 'осталась' : 'остался';
        }

        return 'осталось';
    }
}
