<?php
define('ASPECT_PREFIX', 'aspect');

abstract class Aspect_Base
{
    protected $name;
    protected $args = array();
    protected $labels = array();
    protected $attaches = array();
    protected static $objects = array();

    public function __construct($name)
    {
        $this->name = esc_attr(str_replace(' ', '_', $name));
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

    public static function getName()
    {
        $args = func_get_args();
        $name = ASPECT_PREFIX;
        foreach($args as $arg) {
            if(is_string($arg)) $obj = self::getObject($arg);
            if(is_object($arg)) $obj = $arg;
            $name .= '_'.$obj->name;
        }
        return $name;
    }
}

class Aspect_Type extends Aspect_Base
{
    public $args = array(
        'supports' => array('title', 'editor')
    );
    
    public function __construct($name)
    {
        parent::__construct($name);
        add_action("init", array($this, 'registerType'));
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
        
        foreach ($this->attaches as $attach) {
            if ($attach instanceof Aspect_Box and is_admin()) {
                add_action("save_post", array($attach, 'savePostBox'));
                add_action("add_meta_boxes", function () use ($attach) {
                    add_meta_box(self::getName($attach), $attach->labels['singular_name'], array($attach, 'renderBox'), (string)$this, $attach->args['context'], $attach->args['priority']);
                });
            }
            // create meta box in admin panel only
            if ($attach instanceof Aspect_Taxonomy)
                register_taxonomy(self::getName($attach), (string)$this, $attach->args);
        }
    }
}

class Aspect_Taxonomy extends Aspect_Base
{
    
}

class Aspect_Box extends Aspect_Base
{
    public $args = array(
        'context' => 'advanced',
        'priority' => 'default'
    );
    
    public function renderBox($post)
    {
        wp_nonce_field(self::getName($this), self::getName($this));
        foreach ($this->attaches as $input) {
            $input->render($post, $this);
        }
    }

    public function descriptionBox()
    {
        if (isset($this->args['description'])) echo '<p>'.$this->args['description'].'</p>';
    }

    public function savePostBox($post_id)
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
            if (!isset($_POST[$input->nameInput(null, $this)])) continue;
            $data = sanitize_text_field($_POST[$input->nameInput(null, $this)]);
            update_post_meta($post_id, $input->nameInput(null, $this), $data);
        }
        return $post_id;
    }
}

class Aspect_Input extends Aspect_Base
{
    public $args = array(
        'type' => 'text'    
    );

    public static function createInputs()
    {
        $arr = func_get_arg(0);
        foreach ($arr as $name => $args) {
            if(is_array($args)) {
                $obj = new Aspect_Input($name);
                $obj->args = array_merge($obj->args, $args);   
            } else {
                $obj = new Aspect_Input($args);
            }
            for ($i = 1; $i < func_num_args(); $i++) {
                $obj->attachTo(func_get_arg($i));
            }
        }
    }

    public function getValue($parent, $esc = null, $post = null)
    {
        if ($post === null)
            $post = get_the_ID();
        if ($post instanceof WP_Post) $post = $post->ID;
        if ($post instanceof Aspect_Page) {
            $value = get_option($this->nameInput($post, $parent));
        } else {
            $value = get_post_meta($post, $this->nameInput($post, $parent), true);
        }
        if (isset($this->args['default'])) {
            $default = $this->args['default'];
        } else {
            $default = null;
        }
        $offset = self::getName($parent);
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

    public function label($post, $parent)
    {
        return '<label for="' . $this->nameInput($post, $parent) . '">' . $this->labels['singular_name'] . '</label>';
    }

    public function description()
    {
        if (isset($this->args['description'])) echo '<p class="description">'.$this->args['description'].'</p>';
    }

    public function render()
    {
        if (func_num_args() === 1) {
            list($post, $parent) = func_get_arg(0);
        } else {
            list($post, $parent) = func_get_args();
        }
        if ($post instanceof WP_Post) :
            ?>
            <div>
                <?= $this->label($post, $parent); ?>
                <br>
                <?php $this->renderInput($post, $parent);?>
            </div>
        <?php endif;
        if ($post instanceof Aspect_Page) :
            $this->renderInput($post, $parent);
        endif;
    }
    
    protected function renderInput($post, $parent)
    {
        switch ($this->args['type']) {
            case "select": {
                $this->htmlSelect($post, $parent);
                break;
            }
            case "color": {
                $this->htmlColor($post, $parent);
                break;
            }
            case "media": {
                $this->htmlMedia($post, $parent);
                break;
            }
            default: {
                $this->htmlText($post, $parent);
            }
        }
        $this->description();
    }

    public function nameInput($post, $parent)
    {
        if ($post instanceof Aspect_Page) return self::getName($this, $parent, $post);
        return self::getName($this, $parent);
    }

    protected function htmlSelect($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        ?>
         <select name="<?= $this->nameInput($post, $parent) ?>"
                id="<?= $this->nameInput($post, $parent) ?>">
            <?php
            foreach ($this->attaches as $option) {
                if (is_array($option)) { ?>
                    <option <?php selected($value, esc_attr($option[0])); ?>
                        value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                <?php } else { ?>
                    <option <?php selected($value, esc_attr($option)); ?>
                        value="<?= esc_attr($option) ?>"><?= ucfirst(esc_html($option)) ?></option>
                <?php
                }
            }
            ?>
        </select>
    <?php
    }

    protected function htmlText($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        ?>
        <input class="<?php if($post instanceof Aspect_Page) echo 'regular-text';?> code" type="text" name="<?= $this->nameInput($post, $parent) ?>"
               id="<?= $this->nameInput($post, $parent) ?>"
               value="<?= $value ?>"/>
    <?php
    }
    protected function htmlColor($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        static $calling = false;
        if (!$calling) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            ?>
            <script>
                (function ($) {
                    $(function () {
                        $('.<?=ASPECT_PREFIX?>-color-picker').wpColorPicker();
                    });
                })(jQuery);
            </script>
            <?php
            $calling = true;
        }
        ?>
        <input type="text" name="<?= $this->nameInput($post, $parent) ?>"
               id="<?= $this->nameInput($post, $parent) ?>" class="<?= ASPECT_PREFIX ?>-color-picker"
               value="<?= $value ?>"/>

    <?php
    }

    protected function htmlMedia($post, $parent)

    {
        $value = $this->getValue($parent, 'url', $post);
        static $calling = false;
        if (!$calling) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            $calling = true;
        } ?>
        <script>
            jQuery(document).ready(function($) {
                $('#<?= $this->nameInput($post, $parent) ?>_upload').click(function() {
                    tb_show('Upload', 'media-upload.php?referer=<?= $this->nameInput($post, $parent) ?>&type=image&TB_iframe=true&post_id=0', false);
                    return false;
                });
                window.send_to_editor = function(html) {
                    var image_url = $('img',html).attr('src');
                    var name = 'referer';
                    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
                    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                    results = regex.exec(jQuery('#TB_iframeContent').attr('src'));
                    var id = results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
                    $('#'+id).val(image_url);
                    tb_remove();
                }
            });
        </script>
        <input class="<?php if($post instanceof Aspect_Page) echo 'regular-text';?> code" type="text" id="<?= $this->nameInput($post, $parent) ?>" name="<?= $this->nameInput($post, $parent) ?>"
               value="<?= $value ?>"/>
        <input id="<?= $this->nameInput($post, $parent) ?>_upload" class="button" type="button" value="<?php _e('Upload'); ?>"/>
    <?php }
}

class Aspect_Page extends Aspect_Base
{
    public function __construct($name)
    {
        parent::__construct($name);
        if (isset($this->args['parent_slug'])) {
            add_action('admin_menu', array($this, 'addSubMenuPage'));
        } else {
            add_action('admin_menu', array($this, 'addMenuPage'));
        }
        add_action('init', function () {
            foreach ($this->attaches as $attach) {
                if ($attach instanceof Aspect_Page) {
                    $attach->setArgument('parent_slug', self::getName($this));
                    remove_action('admin_menu', array($attach, 'addMenuPage'));
                    add_action('admin_menu', array($attach, 'addSubMenuPage'));
                    continue;
                } elseif ($attach instanceof Aspect_Box) {
                    $section = $attach;
                } else {
                    throw new Exception('Incorrect input parameters');
                }
                add_action('admin_init', function () use ($section) {
                    add_settings_section(self::getName($section, $this), $section->labels['singular_name'], array($section, 'descriptionBox'), self::getName($this));
                });
                foreach ($section->attaches as $field) {
                    add_action('admin_init', function () use ($field, $section) {
                        register_setting(self::getName($section, $this), self::getName($field, $section, $this));
                        add_settings_field(self::getName($field, $section, $this), $field->label($this, $section), array($field, 'render'), self::getName($this), self::getName($section, $this), array($this, $section));
                    });
                }
            }
        });
    }
    
    public function addMenuPage()
    {
        add_menu_page($this->labels['singular_name'], $this->labels['singular_name'], 'manage_options', self::getName($this), array($this, 'renderPage'));
    }

    public function addSubMenuPage()
    {
        add_submenu_page($this->args['parent_slug'], $this->labels['singular_name'], $this->labels['singular_name'], 'manage_options', self::getName($this), array($this, 'renderPage'));
    }

    function renderPage()
    { ?>
        <div class="wrap">
            <h2><?php echo get_admin_page_title() ?></h2>

            <form action="options.php" method="POST">
                <?php
                foreach ($this->attaches as $attach) {
                    if ($attach instanceof Aspect_Box) {
                        settings_fields(self::getName($attach, $this));
                    } else {
                        continue;
                    }
                }
                do_settings_sections(self::getName($this));
                submit_button();
                ?>
            </form>
        </div>
    <?php }
}
