<?php

namespace Drupal\cooperative\Utility;

class DateStringFormatter
{
    public static function formatDateString(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        $date = trim($date);

        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $date, $matches)) {
            [, $day, $month, $year] = $matches;
            $dt = \DateTime::createFromFormat('d-m-Y', "$day-$month-$year");
            return $dt ? $dt->format('d/m/Y') : '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $date);
            return $dt ? $dt->format('d/m/Y') : '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $date)) {
            try {
                $dt = new \DateTime($date);
                return $dt->format('d/m/Y');
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }
}
