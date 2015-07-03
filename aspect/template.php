<?php
namespace Aspect;
class Template extends Base
{
    public $requested = false;
    public static $isRequested = false;
    public static $isPrettyLinkStructure = false;
    protected static $objects = array();

    public function __construct($name)
    {
        parent::__construct($name);
        add_action('init', array($this, 'registerQueryArg'));
        add_action('template_redirect', array($this, 'registerTemplate'));
    }

    protected static function init()
    {
        static $initialized = false;
        parent::init();
        if (!$initialized) {
            static::$isPrettyLinkStructure = (get_option('permalink_structure') !== '');

            $initialized = true;
        }
    }

    public function registerQueryArg()
    {
        $name = self::getName($this);
        add_rewrite_tag('%' . $name . '%', '([^&].+)');
        if (self::$isPrettyLinkStructure and (isset($this->args['paged']) and $this->args['paged'])) {
            add_rewrite_rule('^' . $name . '/([^/]*)/page/([^/]*)/?', 'index.php?' . $name . '=$matches[1]&paged=$matches[2]', 'top');
        }
        if (self::$isPrettyLinkStructure)
            add_rewrite_rule('^' . $name . '/([^/]*)/?', 'index.php?' . $name . '=$matches[1]', 'top');
    }

    public function registerTemplate()
    {
        $name = self::getName($this);
        if (get_query_var($name)) {
            $this->requested = $name;
            self::$isRequested = true;
            add_filter('template_include', function () use ($name) {
                if (isset($this->args['template']))
                    return get_template_directory() . '/pages/' . $this->args['template'] . '.php';
                return get_template_directory() . '/pages/' . $name . '.php';
            });
            add_filter('wp_title', function ($title) {
                return str_replace(get_bloginfo('name', 'display'), $this->labels['singular_name'], $title);
            });
            add_filter('body_class', function ($classes) use ($name) {
                if (isset($this->args['+class']))
                    $classes = array_merge($classes, $this->args['+class']);
                if (isset($this->args['-class']))
                    $classes = array_diff($classes, $this->args['-class']);
                $classes[] = $name;
                return $classes;
            });
        }
    }

    public function getVar()
    {
        $name = self::getName($this);
        return get_query_var($name, $name);
    }

    public function link($param = null)
    {
        $name = self::getName($this);
        if ($param == null) $param = $name;
        if (self::$isPrettyLinkStructure) {
            $link = home_url() . '/' . $name . '/' . $param;
        } else {
            $link = home_url() . '/?' . $name . '=' . $param;
        }
        return $link;
    }
}