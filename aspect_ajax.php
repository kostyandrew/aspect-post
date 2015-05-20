<?php

class Aspect_Ajax
{
    public static function add_action($tag, $callback)
    {
        add_action('wp_ajax_nopriv_'.$tag, $callback);
        add_action('wp_ajax_'.$tag, $callback);
    }
}