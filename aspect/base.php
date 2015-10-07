<?php
namespace Aspect;
if(!defined('ASPECT_PREFIX')) define('ASPECT_PREFIX', 'aspect');

abstract class Base
{
    protected $name;
    public $args = array();
    public $labels = array();
    public $attaches = array();
    protected static $objects = array();

    /**
     * @param $name
     * @throws \Exception
     */
    public function __construct($name)
    {
        $this->grey_name = $name;
        $this->name = esc_attr(str_replace(' ', '_', $name));
        if (isset(static::$objects[$this->name])) throw new \Exception(get_called_class() . ' with ' . $name . ' already exists');
        static::$objects[$this->name] = $this;
        $this->args['labels'] = &$this->labels;
        /* Creating Label Using Translating */
        $singular_name = ucwords($name);
        $multi_name = $singular_name . 's';
        $this->labels['singular_name'] = __($singular_name);
        $this->labels['name'] = __($multi_name);

        static::init();
    }

    protected static function init() {
        static $initialized = false;
        if(!$initialized) {
            // do some

            $initialized = true;
        }
    }

    public function __toString()
    {
        return self::getName($this);
    }

    /**
     * @param $name
     * @return static
     * @throws \Exception
     */
    public static function get($name)
    {
        $re_name = str_replace(' ', '_', $name);
        if (isset(static::$objects[$re_name])) {
            $object = static::$objects[$re_name];
            return $object;
        }
        throw new \Exception(get_called_class() . ' with ' . $name . ' not found');
    }

    public static function set($name) {
        return new static($name);
    }

    /**
     * @return static
     */
    public function setArgument($args, $data = null)
    {
        if (is_array($args)) {
            $this->args = array_merge($this->args, $args);
        } elseif (is_string($args)) {
            $this->args[$args] = $data;
        }
        return $this;
    }

    public function unsetArgument($name)
    {
        if (isset($this->args[$name])) {
            unset($this->args[$name]);
        }
        return $this;
    }

    public function setLabel($args, $data)
    {
        if (is_array($args)) {
            $this->labels = array_merge($this->labels, $args);
        } elseif (is_string($args)) {
            $this->labels[$args] = $data;
        }
        return $this;
    }

    public function unsetLabel($name)
    {
        if (isset($this->labels[$name])) {
            unset($this->labels[$name]);
        }
        return $this;
    }

    public function attach()
    {
        $obj = func_get_args();
        $this->attaches = array_merge($this->attaches, $obj);
        return $this;
    }

    public function attachFew($obj)
    {
        $this->attaches = array_merge($this->attaches, $obj);
        return $this;
    }

    public function detach()
    {
        $obj = func_get_args();
        $this->attaches = array_diff($this->attaches, $obj);
        return $this;
    }
    public function detachFew($obj)
    {
        $this->attaches = array_diff($this->attaches, $obj);
        return $this;
    }

    public function attachTo()
    {
        $objs = func_get_args();
        foreach ($objs as $obj) {
            $obj->attach($this);
        }
        return $this;
    }

    public function detachFrom()
    {
        $objs = func_get_args();
        foreach ($objs as $obj) {
            $obj->detach($this);
        }
        return $this;
    }

    public static function getName()
    {
        $args = func_get_args();
        $name = ASPECT_PREFIX;
        foreach ($args as $arg) {
            if (!is_object($arg)) throw new \Exception(strval($arg) . ' must be Aspect Object');
            if($name) {
                $name .= '_' . $arg->name;
            }else{
                $name .= $arg->name;
            }
        }
        return $name;
    }

    /**
     * @return static[]
     */
    public static function createFew() {
        $arr = func_get_args();
        $return = array();
        foreach ($arr as $name => $args) {
            if (is_array($args)) {
                $obj = new static($name);
                $obj->args = array_merge($obj->args, $args);
            } else {
                $obj = new static($args);
            }
            $return[] = $obj;
        }
        return $return;
    }

    /**
     * @return static[]
     */
    public static function getFew() {
        $arr = func_get_args();
        $return = array();
        foreach ($arr as $name => $args) {
            if (is_array($args)) {
                $obj = static::get($name);
                $obj->args = array_merge($obj->args, $args);
            } else {
                $obj = static::get($args);
            }
            $return[] = $obj;
        }
        return $return;
    }

    /**
     * @return static[]
     */
    public static function getAll() {
        return static::$objects;
    }

    public static function filter_array(&$el) {
        if(is_string($el))
            $el = sanitize_text_field($el);
        if(is_array($el)) {
            array_walk($el, array('static', 'filter_array'));
            $el = array_filter($el);
        }
    }

    public function getOrigin($args = array()) {
        static $number = 0;
        $name = static::getName($this).$number++;
        $origin = new Origin($name);
        $origin
            ->setArgument($args);
        return $origin;
    }
}