<?php
namespace Aspect;
class Input extends Base
{
    protected static $objects = array();
    public $type = 'text';

    /**
     * @return static[]
     */
    public static function createInputs()
    {
        return call_user_func_array(array('static', 'createFew'), func_get_args());
    }

    /**
     * @return static[]
     */
    public static function getInputs()
    {
        return call_user_func_array(array('static', 'getFew'), func_get_args());
    }

    public function render()
    {
        if (func_num_args() === 1) {
            list($post, $parent) = func_get_arg(0);
        } else {
            list($post, $parent) = func_get_args();
        }
        if ($post instanceof \WP_Post) :
            ?>
            <div>
                <?php if (!isset($this->args['hide_label']) or !$this->args['hide_label'])
                    echo $this->label($post, $parent) . '<br>'; ?>
                <?php $this->renderInput($post, $parent); ?>
            </div>
        <?php endif;
        if (is_a($post, '\Aspect\Page')) :
            $this->renderInput($post, $parent);
        endif;
        if (($post instanceof \stdClass || $post instanceof \WP_Term) && isset($post->taxonomy) && isset($post->term_id)) { ?>
            <tr class="form-field">
                <?php if (!isset($this->args['hide_label']) or !$this->args['hide_label'])
                    echo '<th scope="row">' . $this->label($post, $parent) . '</th>'; ?>
                <td colspan="<?= (!isset($this->args['hide_label']) or !$this->args['hide_label']) ? 1 : 2; ?>">
                    <?php $this->renderInput($post, $parent); ?>
                    <?php $this->description(); ?>
                </td>
            </tr>
        <?php }
        if (($post instanceof \stdClass || $post instanceof \WP_Term) && isset($post->taxonomy) && !isset($post->term_id)) {
            ?>
            <div class="form-field">
                <?php if (!isset($this->args['hide_label']) or !$this->args['hide_label'])
                    echo $this->label($post, $parent); ?>
                <?php $this->renderInput($post, $parent); ?>
                <?php $this->description(); ?>
            </div>
        <?php }
    }

    public function label($post, $parent)
    {
        return '<label for="' . $this->nameInput($post, $parent) . '">' . $this->labels['singular_name'] . '</label>';
    }

    public function nameInput($post, $parent)
    {
        if (is_a($post, '\Aspect\Page')) return self::getName($this, $parent, $post);
        return self::getName($this, $parent);
    }

    public function renderInput($post, $parent)
    {
        $name = $this->getType();
        $method = 'html' . $name;
        if (!method_exists($this, $method))
            throw new \Exception('Input type with ' . $name . ' not found');

        call_user_func_array(array($this, $method), array($post, $parent));
    }

    public function getType()
    {
        $type = $this->type;
        $name = str_replace(' ', '', ucwords($type));
        if (empty($name)) $name = 'Text';
        return $name;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function description()
    {
        if (isset($this->args['description'])) echo '<p class="description">' . $this->args['description'] . '</p>';
    }

    public function htmlSelect($post, $parent)
    {
        $value = $this->getValue($parent, null, $post);
        ?>
        <select
            name="<?= $this->nameInput($post, $parent) ?><?php if (isset($this->args['multiply']) && $this->args['multiply']) echo '[]'; ?>" <?php if (isset($this->args['multiply']) && $this->args['multiply']) echo 'multiple'; ?>
            id="<?= $this->nameInput($post, $parent) ?>">
            <?php
            foreach ($this->attaches as $option) {
                if (is_array($option)) { ?>
                    <option <?php $this->selected($value, esc_attr($option[0])); ?>
                        value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                <?php } else { ?>
                    <option <?php $this->selected($value, esc_attr($option)); ?>
                        value="<?= esc_attr($option) ?>"><?= ucfirst(esc_html($option)) ?></option>
                    <?php
                }
            }
            ?>
        </select>
        <?php
    }

    public function getValue($parent, $esc = null, $post = null)
    {
        if ($post === null)
            $post = get_the_ID();
        if (is_numeric($post)) {
            $value = get_post_meta($post, $this->nameInput($post, $parent), true);
        }
        if ($post instanceof \WP_Post) {
            $post = $post->ID;
            $value = get_post_meta($post, $this->nameInput($post, $parent), true);
        }
        if (is_a($post, '\Aspect\Page')) {
            $value = get_option($this->nameInput($post, $parent));
        }
        if (($post instanceof \stdClass || $post instanceof \WP_Term) && isset($post->taxonomy)) {
            if (get_bloginfo('version') >= 4.4 && isset($post->term_id)) {
                $value = get_term_meta($post->term_id, $this->nameInput($post, $parent), true);
            } else if (isset($post->term_id)) {
                $value = Taxonomy::get_term_meta($post->term_id, $this->nameInput($post, $parent), true);
            } else {
                $value = null;
            }
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
        if (!isset($value) and isset($default)) $value = $default;
        if (empty($esc) or $esc == null) {
            return $value;
        } else {
            if (!function_exists('esc_' . $esc)) throw new Exception('Escape function with name ' . $esc . ' not exists!');
            return call_user_func_array('esc_' . $esc, array($value));
        }
    }

    public function selected($selected, $current)
    {
        if (isset($this->args['multiply']) && $this->args['multiply']) {
            if (!is_array($selected)) $selected = array();
            if (array_key_exists($current, $selected) or in_array($current, $selected)) echo ' selected ';
        } else {
            selected($selected, $current);
        }
    }

    public function htmlText($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        ?>
        <input class="large-text code" type="text"
               name="<?= $this->nameInput($post, $parent) ?>"
               id="<?= $this->nameInput($post, $parent) ?>"
               value="<?= $value ?>"/>
        <?php
    }

    public function htmlTextArea($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        ?>
        <textarea class="large-text code" type="text"
                  name="<?= $this->nameInput($post, $parent) ?>"
                  id="<?= $this->nameInput($post, $parent) ?>"><?= $value ?></textarea>
        <?php
    }

    public function htmlColor($post, $parent)
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

    public function htmlMedia($post, $parent)

    {
        $value = $this->getValue($parent, 'html', $post);
        $src_data = wp_get_attachment_image_src($value, 'full');
        $src = $src_data[0];
        static $calling = false;
        if (!$calling) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            $calling = true;
        } ?>
        <script>
            jQuery(document).ready(function ($) {
                $('#<?= $this->nameInput($post, $parent) ?>_upload').click(function (e) {
                    e.preventDefault();
                    tb_show('Upload', 'media-upload.php?referer=<?= $this->nameInput($post, $parent) ?>&type=image&TB_iframe=true&post_id=0', false);
                });
                $('#<?= $this->nameInput($post, $parent) ?>_remove').click(function (e) {
                    e.preventDefault();
                    $('#<?= $this->nameInput($post, $parent) ?>_src, #<?= $this->nameInput($post, $parent) ?>').val('');
                    $('#<?= $this->nameInput($post, $parent) ?>_preview img').attr({'src': ''}).hide();
                });
                window.send_to_editor = function (html) {
                    var image_url = $('img', html).attr('src');
                    var id_attach = $('img', html).attr('class').match(/\d+/g);
                    console.log(id_attach);
                    var name = 'referer';
                    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
                    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                        results = regex.exec(jQuery('#TB_iframeContent').attr('src'));
                    var id = results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
                    $('#' + id).val(id_attach);
                    $('#' + id + '_src').val(image_url);
                    $('#' + id + '_preview img').attr({'src': image_url}).show();
                    tb_remove();
                }
            });
        </script>
        <input type="hidden" id="<?= $this->nameInput($post, $parent) ?>" name="<?= $this->nameInput($post, $parent) ?>"
               value="<?= $value ?>"/>
        <input class="large-text code" type="text"
               id="<?= $this->nameInput($post, $parent) ?>_src"
               value="<?= $src ?>"/>
        <input id="<?= $this->nameInput($post, $parent) ?>_upload" class="button" type="button"
               value="<?php _e('Upload'); ?>"/>
        <input id="<?= $this->nameInput($post, $parent) ?>_remove" class="button" type="button"
               value="<?php _e('Remove'); ?>"/>
        <div id="<?= $this->nameInput($post, $parent) ?>_preview" style="margin-top: 10px">
            <img style="max-width:50%;" src="<?= $src; ?>"/>
        </div>
    <?php }

    public function htmlRadio($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        foreach ($this->attaches as $option) {
            if (is_array($option)) { ?>
                <label><input type="radio" <?php checked($value, esc_attr($option[0])); ?>
                              name="<?= $this->nameInput($post, $parent) ?>"
                              value="<?= esc_attr($option[0]) ?>">&nbsp;<?= esc_html($option[1]) ?></label>
            <?php } else { ?>
                <label><input type="radio" <?php checked($value, esc_attr($option)); ?>
                              name="<?= $this->nameInput($post, $parent) ?>"
                              value="<?= esc_attr($option) ?>">&nbsp;<?= ucfirst(esc_html($option)) ?></label>
                <?php
            }
        }
    }

    public function htmlCheckbox($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        foreach ($this->attaches as $option) {
            if (is_array($option)) { ?>
                <label><input type="checkbox" <?php checked($value, esc_attr($option[0])); ?>
                              name="<?= $this->nameInput($post, $parent) ?>"
                              value="<?= esc_attr($option[0]) ?>">&nbsp;<?= esc_html($option[1]) ?></label>
            <?php } else { ?>
                <label><input type="checkbox" <?php checked($value, esc_attr($option)); ?>
                              name="<?= $this->nameInput($post, $parent) ?>"
                              value="<?= esc_attr($option) ?>">&nbsp;<?= ucfirst(esc_html($option)) ?></label>
                <?php
            }
        }
    }

    public function processingData($elem_id, $parent)
    {
        $data = null;
        $key_name = $this->nameInput(null, $parent);
        $data = $_POST[$key_name];
        $data = call_user_func_array(array($this, 'saveBefore'), array($data, $key_name, $elem_id));
        if (is_string($data))
            $data = sanitize_text_field($data);
        if (is_array($data))
            call_user_func_array(array('static', 'filter_array'), array(&$data));
        $data = call_user_func_array(array($this, 'saveAfter'), array($data, $key_name, $elem_id));
        return array($data, $key_name);
    }

    public function saveBefore($data, $key_name, $elem_id)
    {
        $name = $this->getType();
        if (method_exists($this, 'saveBefore' . $name))
            call_user_func_array(array($this, 'saveBefore' . $name), array(&$data));
        if (isset($this->args['saveBefore']) && is_callable($this->args['saveBefore']))
            call_user_func_array($this->args['saveBefore'], array(&$data, $key_name, $elem_id));
        $data = apply_filters_ref_array('\Aspect\Input\saveBefore', array($data, $this, $key_name, $elem_id));
        return $data;
    }

    public function saveAfter($data, $key_name, $elem_id)
    {
        $name = $this->getType();
        if (method_exists($this, 'saveAfter' . $name))
            call_user_func_array(array($this, 'saveAfter' . $name), array(&$data));
        if (isset($this->args['saveAfter']) && is_callable($this->args['saveAfter']))
            call_user_func_array($this->args['saveAfter'], array(&$data, $key_name, $elem_id));
        $data = apply_filters_ref_array('\Aspect\Input\saveAfter', array($data, $this, $key_name, $elem_id));
        return $data;
    }

    public function setOrigin($origin)
    {
        if (!is_a($origin, '\Aspect\Origin'))
            throw new \Exception(strval($origin) . ' is not valid origin');
        /* @var $origin \Aspect\Origin */
        $object = $this;
        add_action('init', function () use ($origin, $object) {
            $attaches = $origin->returnOrigin();
            $object->attachFew($attaches);
        });
        return $this;
    }
}