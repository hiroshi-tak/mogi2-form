<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function getMonthDays(Carbon $month)
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $days = [];

        while ($start <= $end) {
            $days[] = $start->copy();
            $start->addDay();
        }

        return $days;
    }
}
