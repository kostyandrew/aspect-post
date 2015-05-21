<?php
class Aspect_Template extends Aspect_Base
{
    static $requested = false;
    public function __construct($name)
    {
        parent::__construct($name);
        add_action('init', array($this, 'registerQueryArg'));
        add_action('template_redirect', array($this, 'registerTemplate'));
    }

    public function registerQueryArg()
    {
        $name = self::getName($this);
        add_rewrite_tag('%' . $name . '%', '([^&].+)');
        add_rewrite_rule('^' . $name . '/([^/]*)/?', 'index.php?'.$name.'=$matches[1]', 'top');
    }

    public function registerTemplate()
    {
        $name = self::getName($this);
        if (get_query_var($name)) {
            add_filter('template_include', function () use ($name) {
                self::$requested = true;
                if(isset($this->args['template']))
                    return get_template_directory() . '/pages/'.$this->args['template'].'.php';
                return get_template_directory() . '/pages/'.$name.'.php';
            });
            add_filter('wp_title', function($title){
                return str_replace(get_bloginfo( 'name', 'display' ), $this->labels['singular_name'], $title);
            });
            add_filter('body_class', function ($classes) use($name) {
                if(isset($this->args['+class']))
                    $classes = array_merge($classes, $this->args['+class']);
                if(isset($this->args['-class']))
                    $classes = array_diff($classes, $this->args['-class']);
                $classes[] = $name;
                return $classes;
            });
        }
    }

    public static function isRequested() {
        return self::$requested;
    }
}