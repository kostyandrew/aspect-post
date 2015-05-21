<?php
if(!defined('ASPECT_PREFIX')) define('ASPECT_PREFIX', 'aspect');

abstract class Aspect_Base
{
    protected $name;
    public $args = array();
    public $labels = array();
    protected $attaches = array();
    protected static $objects = array();

    public function __construct($name)
    {
        $this->name = esc_attr(str_replace(' ', '_', $name));
        if (isset(self::$objects[$this->name])) throw new Exception(get_called_class() . ' with ' . $name . ' exists');
        self::$objects[get_called_class()][$this->name] = $this;
        $this->args['labels'] = &$this->labels;
        /* Creating Label Using Translating */
        $singular_name = ucwords($name);
        $multi_name = $singular_name . 's';
        $this->labels['singular_name'] = __($singular_name);
        $this->labels['name'] = __($multi_name);
    }

    public function __toString()
    {
        return self::getName($this);
    }

    public static function getObject($name)
    {
        $re_name = str_replace(' ', '_', $name);
        if (isset(self::$objects[get_called_class()][$re_name])) {
            $object = self::$objects[get_called_class()][$re_name];
            return $object;
        }
        throw new Exception(get_called_class() . ' with ' . $name . ' not found');
    }

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

    public function detach()
    {
        $obj = func_get_args();
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
            if (is_object($arg)) $obj = $arg;
            if($name) {
                $name .= '_' . $obj->name;
            }else{
                $name .= $obj->name;
            }
        }
        return $name;
    }
}