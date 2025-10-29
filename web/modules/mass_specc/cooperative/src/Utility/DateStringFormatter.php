<?php
namespace Drupal\cooperative\Utility;
class DateStringFormatter
{
    public static function formatDateString(string $date): string
    {
        if (empty($date)) {
            return '';
        }

        if (!preg_match('/^(\d{1,2})(\d{1,2})(\d{4})$/', $date, $matches)) {
            return '';
        }

        [, $month, $day, $year] = $matches;

        $dt = \DateTime::createFromFormat('Y-m-d', $date);

        if (!$dt) {
            return '';
        }

        return $dt->format('d/m/Y');
    }
}