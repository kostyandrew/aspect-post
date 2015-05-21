<?php

class Aspect_Helper
{
    public static function copyrightYear($start_year)
    {
        $current_year = date('Y');

        if ($current_year > $start_year) return $start_year . '-' . $current_year;
        return $current_year;
    }
    public static function times($times, $callback) {
        for($_i = 0; $_i < $times; $_i++) {
            call_user_func($callback);
        }
    }
}