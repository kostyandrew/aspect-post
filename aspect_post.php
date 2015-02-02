<?php
define('ASPECT_PREFIX', 'aspect_');

abstract class Aspect_Base
{
    protected $name;
    protected $args = array();
    protected $labels = array();
    protected $attaches = array();
    protected $types = array();
    protected static $objects = array();

    protected function __construct($name)
    {
        $this->name = str_replace(' ', '_', $name);
        if (isset(self::$objects[$this->name])) throw new Exception(get_called_class() . ' with ' . $name . ' exists');
        self::$objects[$this->name] = $this;

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
        if (isset(self::$objects[$re_name])) {
            $object = self::$objects[$re_name];
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

    public function setType()
    {
        $obj = func_get_args();
        $this->types = array_merge($this->types, $obj);
        return $this;
    }

    public function unsetType()
    {
        $obj = func_get_args();
        $this->types = array_diff($this->types, $obj);
        return $this;
    }

    public static function getName($type, $parent = null)
    {
        if (is_string($type)) {
            $object = self::getObject($type);
        }
        if (is_object($type))
            $object = $type;
        if (isset($object) and $parent !== null) {
            if (is_string($parent)) {
                $parent_object = self::getObject($parent);
            }
            if (is_object($parent))
                $parent_object = $parent;
            if (isset($parent_object))
                return ASPECT_PREFIX . $parent_object->name . '_' . str_replace(' ', '_', $object->name);
        } else {
            return ASPECT_PREFIX . str_replace(' ', '_', $object->name);
        }

        throw new Exception('Incorrect input parameters');
    }
}

class Aspect_Type extends Aspect_Base
{
    public function __construct($name)
    {
        parent::__construct($name);
        /* Default Supports */
        $this->args['supports'] = array();
        $this->addSupport('title');
        $this->addSupport('editor');
        add_action("init", array($this, 'registerType'));
        return $this;
    }

    public function addSupport()
    {
        $args = func_get_args();
        $this->args['supports'] = array_merge($this->args['supports'], $args);
        return $this;
    }

    public function removeSupport()
    {
        $args = func_get_args();
        $this->args['supports'] = array_diff($this->args['supports'], $args);
        return $this;
    }

    public function registerType()
    {
        register_post_type(self::getName($this), $this->args);
    }
}

class Aspect_Taxonomy extends Aspect_Base
{
    public function __construct($name)
    {
        parent::__construct($name);
        add_action("init", array($this, 'registerTaxonomy'));
        return $this;
    }

    public function registerTaxonomy()
    {
        foreach ($this->types as $type) {
            register_taxonomy(self::getName($this), (string)$type, $this->args);
        }
    }
}

class Aspect_Box extends Aspect_Base
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->setArgument('context', 'advanced');
        $this->setArgument('priority', 'default');
        if (is_admin()) {
            add_action("add_meta_boxes", array($this, 'registerBox'));
            add_action("save_post", array($this, 'saveBox'));
        }
    }

    public function registerBox()
    {
        foreach ($this->types as $type) {
            add_meta_box(self::getName($this), $this->labels['singular_name'], array($this, 'renderBox'), (string)$type, $this->args['context'], $this->args['priority']);
        }
    }

    public function renderBox($post)
    {
        wp_nonce_field(self::getName($this), self::getName($this));
        foreach ($this->attaches as $input) {
            $input->render($post, $this);
        }
    }

    public function saveBox($post_id)
    {
        if (!isset($_POST[self::getName($this)]) or !wp_verify_nonce($_POST[self::getName($this)], self::getName($this)))
            return $post_id;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;
        if ('page' == $_POST['post_type'] && !current_user_can('edit_page', $post_id)) {
            return $post_id;
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        foreach ($this->attaches as $input) {
            if (!isset($_POST[self::getName($input, $this)])) continue;
            $data = sanitize_text_field($_POST[self::getName($input, $this)]);
            update_post_meta($post_id, self::getName($input, $this), $data);
        }
        return $post_id;
    }
}

class Aspect_Input extends Aspect_Base
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->args['type'] = 'text';
    }

    public static function createInputs()
    {
        $arr = func_get_arg(0);
        foreach ($arr as $name => $args) {
            $obj = new Aspect_Input($name);
            $obj->args = array_merge($obj->args, $args);
            for ($i = 1; $i < func_num_args(); $i++) {
                $obj->attachTo(func_get_arg($i));
            }
        }
    }

    public function getValue($parent, $esc = null, $post_id = null)
    {
        if ($post_id === null)
            $post_id = get_the_ID();
        $value = get_post_meta($post_id, self::getName($this, $parent), true);
        if (isset($this->args['default'])) {
            $default = $this->args['default'];
        } else {
            $default = null;
        }
        $offset = Aspect_Box::getName($parent);
        if (is_array($default) and isset($default[$offset])) {
            $default = $default[$offset];
        } elseif (is_array($default) and isset($default['scalar'])) {
            $default = $default['scalar'];
        } elseif (is_array($default) and !isset($default['scalar'])) {
            $default = null;
        }
        if (!$value and isset($default)) $value = $default;
        switch ($esc) {
            case "attr" : {
                return esc_attr($value);
                break;
            }
            case "html" : {
                return esc_html($value);
                break;
            }
            case "sql" : {
                return esc_sql($value);
                break;
            }
            case "js" : {
                return esc_js($value);
                break;
            }
            case "textarea" : {
                return esc_textarea($value);
                break;
            }
            case "url" : {
                return esc_url($value);
                break;
            }
            default:
                return $value;
        }
    }

    public function render($post, $parent)
    {
        switch ($this->args['type']) {
            case "select": {
                $this->htmlSelect($post, $parent);
                break;
            }
            default: {
                $this->htmlText($post, $parent);
            }
        }
    }

    protected function htmlSelect($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post->ID);
        ?>
        <div>
            <label
                for="<?= esc_attr(self::getName($this, $parent)) ?>"><?= $this->labels['singular_name'] ?></label><br>
            <select name="<?= esc_attr(self::getName($this, $parent)) ?>"
                    id="<?= esc_attr(self::getName($this, $parent)) ?>">
                <?php
                foreach ($this->attaches as $option) {
                    if (is_array($option)) { ?>
                        <option <?php selected($value, esc_attr($option[0])); ?>
                            value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                    <?php } else { ?>
                        <option <?php selected($value, esc_attr($option)); ?> value="<?=esc_attr($option)?>"><?= ucfirst(esc_html($option)) ?></option>
                    <?php
                    }
                }
                ?>
            </select>
        </div>
    <?php
    }

    protected function htmlText($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post->ID);
        ?>
        <div>
            <label
                for="<?= esc_attr(self::getName($this, $parent)) ?>"><?= $this->labels['singular_name'] ?></label><br>
            <input type="text" name="<?= esc_attr(self::getName($this, $parent)) ?>"
                   id="<?= esc_attr(self::getName($this, $parent)) ?>"
                   value="<?= esc_attr($value) ?>"/>
        </div>
    <?php
    }
}
