<?php
namespace Aspect;
class Template extends Base
{
    private $thisRequested = false;
    private static $requested = false;
    private static $prettyLinkStructure;
    protected static $objects = array();

    public function __construct($name)
    {
        parent::__construct($name);
        if (empty(self::$prettyLinkStructure)) {
            self::$prettyLinkStructure = (get_option('permalink_structure') !== '');
        }
        add_action('init', array($this, 'registerQueryArg'));
        add_action('template_redirect', array($this, 'registerTemplate'));
    }

    public function registerQueryArg()
    {
        $name = self::getName($this);
        add_rewrite_tag('%' . $name . '%', '([^&].+)');
        if (self::$prettyLinkStructure and (isset($this->args['paged']) and $this->args['paged'])) {
            add_rewrite_rule('^' . $name . '/([^/]*)/page/([^/]*)/?', 'index.php?' . $name . '=$matches[1]&paged=$matches[2]', 'top');
        }
        if (self::$prettyLinkStructure)
            add_rewrite_rule('^' . $name . '/([^/]*)/?', 'index.php?' . $name . '=$matches[1]', 'top');
    }

    public function registerTemplate()
    {
        $name = self::getName($this);
        if (get_query_var($name)) {
            self::$requested = true;
            $this->thisRequested = true;
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

    public function __call($name, array $params)
    {
        switch ($name) {
            case 'isRequested' : {
                return $this->thisRequested;
                break;
            }
            case 'link' : {
                return call_user_func_array(array($this, 'link'), $params);
                break;
            }
            case 'getVar': {
                $name = self::getName($this);
                return get_query_var($name, $name);
            }
            default:
                return false;
        }
    }

    public static function __callStatic($name, array $params)
    {
        switch ($name) {
            case 'isRequested' : {
                return self::$requested;
                break;
            }
            case 'link' : {
                $object = self::getObject(array_shift($params));
                return call_user_func_array(array($object, 'link'), $params);
                break;
            }
            case 'getVar': {
                $object = self::getObject(array_shift($params));
                return call_user_func(array($object, 'getVar'));
            }
            default:
                return false;
        }
    }

    private function link($param = null)
    {
        $name = self::getName($this);
        if($param == null) $param = $name;
        if (self::$prettyLinkStructure) {
            $link = home_url().'/'.$name.'/'.$param;
        } else {
            $link = home_url().'/?'.$name.'='.$param;
        }
        return $link;
    }
    public static function isPrettyLinkStructure() {
        return self::$prettyLinkStructure;
    }
}