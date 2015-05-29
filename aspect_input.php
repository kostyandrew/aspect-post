<?php

class Aspect_Input extends Aspect_Base
{
    public $args = array(
        'type' => 'text'
    );
    protected static $objects = array();

    public static function createInputs()
    {
        return call_user_func_array(array(get_class(),'createFew'), func_get_args());
    }

    public static function getInputs()
    {
        return call_user_func_array(array(get_class(),'getFew'), func_get_args());
    }

    public function getValue($parent, $esc = null, $post = null)
    {
        if ($post === null)
            $post = get_the_ID();
        if (is_string($post) and !is_numeric($post)) {
            $post = Aspect_Page::getObject($post);
        }
        if(is_numeric($post)) {
            $value = get_post_meta($post, $this->nameInput($post, $parent), true);
        }
        if ($post instanceof WP_Post) {
            $post = $post->ID;
            $value = get_post_meta($post, $this->nameInput($post, $parent), true);
        }
        if ($post instanceof Aspect_Page) {
            $value = get_option($this->nameInput($post, $parent));
        }
        if ($post instanceof stdClass && isset($post->taxonomy)) {
            if(isset($post->term_id)) {
                $value = Aspect_Taxonomy::get_term_meta($post->term_id, $this->nameInput($post, $parent), true);
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
        if (isset($this->args['description'])) echo '<p class="description">' . $this->args['description'] . '</p>';
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
        if ($post instanceof stdClass && isset($post->taxonomy) && isset($post->term_id)) { ?>
            <tr class="form-field">
                <th scope="row"><?= $this->label($post, $parent); ?></th>
                <td>
                    <?php $this->renderInput($post, $parent);?>
                    <?php $this->description(); ?>
                </td>
            </tr>
        <?php }
        if ($post instanceof stdClass && isset($post->taxonomy) && !isset($post->term_id)) {
        ?>
        <div class="form-field">
            <?= $this->label($post, $parent); ?>
            <?php $this->renderInput($post, $parent);?>
            <?php $this->description(); ?>
        </div>
        <?php }
    }

    protected function renderInput($post, $parent)
    {
        switch ($this->args['type']) {
            case "select": {
                $this->htmlSelect($post, $parent);
                break;
            }
            case "radio": {
                $this->htmlRadio($post, $parent);
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
            case "listing": {
                $this->htmlListing($post, $parent);
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
        $value = $this->getValue($parent, null, $post);
        ?>
        <select name="<?= $this->nameInput($post, $parent) ?><?php if(isset($this->args['multiply']) && $this->args['multiply']) echo '[]'; ?>" <?php if(isset($this->args['multiply']) && $this->args['multiply']) echo 'multiple'; ?>
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
    private function selected($selected, $current) {
        if(isset($this->args['multiply']) && $this->args['multiply']) {
            if(!is_array($selected)) $selected = array();
            if(array_key_exists($current, $selected) or in_array($current, $selected)) echo ' selected ';
        }else{
            selected($selected, $current);
        }
    }

    protected function htmlText($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        ?>
        <input class="large-text code" type="text"
               name="<?= $this->nameInput($post, $parent) ?>"
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
        $value = $this->getValue($parent, 'html', $post);
        $src = wp_get_attachment_image_src($value, 'full')[0];
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
                $('#<?= $this->nameInput($post, $parent) ?>_upload').click(function () {
                    tb_show('Upload', 'media-upload.php?referer=<?= $this->nameInput($post, $parent) ?>&type=image&TB_iframe=true&post_id=0', false);
                    return false;
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
                    $('#'+id).val(id_attach);
                    $('#' + id + '_src').val(image_url);
                    $('#' + id + '_preview img').attr({'src': image_url});
                    tb_remove();
                }
            });
        </script>
        <input type="hidden" id="<?= $this->nameInput($post, $parent) ?>" name="<?= $this->nameInput($post, $parent) ?>" value="<?= $value ?>"/>
        <input class="large-text code" type="text"
               id="<?= $this->nameInput($post, $parent) ?>_src"
               value="<?= $src ?>"/>
        <input id="<?= $this->nameInput($post, $parent) ?>_upload" class="button" type="button"
               value="<?php _e('Upload'); ?>"/>
        <div id="<?= $this->nameInput($post, $parent) ?>_preview" style="margin-top: 10px">
            <img style="max-width:50%;" src="<?= $src; ?>"/>
        </div>
    <?php }

    public function htmlListing($post, $parent) {
        $values = $this->getValue($parent, null, $post);
        ?>
        <style>
            #table_<?= $this->nameInput($post, $parent) ?> tr td:first-child {
                border: 1px solid #eee;
                padding: 5px;
            }
            #table_<?= $this->nameInput($post, $parent) ?> tr td.sub {
                padding-left: 50px;
            }
            #table_<?= $this->nameInput($post, $parent) ?> tr td.bold input {
                font-weight: bold;
            }
            #table_<?= $this->nameInput($post, $parent) ?> tr td:first-child:hover {
                border-color: #aaa;
            }
        </style>
        <table width="100%" id="table_<?= $this->nameInput($post, $parent) ?>">
            <tbody>
            <?php if(!is_array($values)) {
                if(isset($this->labels['before'])) $values[0]['before'] = '';
                $values[0][0] = '';
                if(isset($this->labels['after'])) $values[0]['after'] = '';
            }
            foreach($values as $rid => $value) { $class = array();?>
                <tr>
                    <?php
                    if(isset($this->args['sub']) && $this->args['sub'] && isset($value['sub']) && $value['sub']) $class[] = 'sub';
                    if(isset($this->args['bold']) && $this->args['bold'] && isset($value['bold']) && $value['bold']) $class[] = 'bold';
                    ?>
                    <td class="<?=implode(' ', $class)?>">
                        <?php
                        if(isset($this->labels['before']) && !isset($value['before'])) $value['before'] = '';
                        if(!isset($value[0])) $value[0] = '';
                        if(isset($this->labels['after']) && !isset($value['after'])) $value['after'] = '';
                        if(isset($this->labels['before'])) { ?>
                            <input type="text" name="<?= $this->nameInput($post, $parent) ?>[<?=$rid?>][before]" placeholder="<?=$this->labels['before']?>" class="large-text code" value="<?=esc_attr($value['before'])?>"/><hr>
                        <?php }
                        if(isset($this->args['sub']) && $this->args['sub']) { if(!isset($value['sub']) or empty($value['sub'])) $value['sub'] = 0; ?>
                            <label>Sub item: <input class="subitem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>[<?=$rid?>][sub]" value="1" <?php if($value['sub']) echo 'checked'?>/></label>
                        <?php }
                        if(isset($this->args['bold']) && $this->args['bold']) { if(!isset($value['bold']) or empty($value['bold'])) $value['bold'] = 0; ?>
                            <label>Bold item: <input class="bolditem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>[<?=$rid?>][bold]" value="1" <?php if($value['bold']) echo 'checked'?>/></label>
                        <?php }
                        $last_id = 0;
                        foreach($value as $id => $input) {
                            if(!is_numeric($id))
                                continue;
                            $last_id = $id;
                            ?>
                            <input type="text" name="<?= $this->nameInput($post, $parent) ?>[<?=$rid?>][<?=$id?>]" class="large-text code listing" value="<?=esc_attr($input)?>"/>
                        <?php }
                        if(isset($this->labels['after'])) { ?>
                            <hr/><input type="text" name="<?= $this->nameInput($post, $parent) ?>[<?=$rid?>][after]" placeholder="<?=$this->labels['after']?>" class="large-text code" value="<?=esc_attr($value['after'])?>"/>
                        <?php } ?>
                    </td>
                    <?php if(isset($this->args['multiply']) && $this->args['multiply']) { ?>
                    <td width="40" style="vertical-align: bottom"><input type="button" class="button <?= $this->nameInput($post, $parent) ?>" data-row-id="<?=$rid?>" data-last-id="<?=$last_id?>" value="+"></td>
                    <?php } ?>
                </tr>
            <?php }
            if(!isset($this->args['single']) or !$this->args['single']) {
            ?>
            <tr><?php if(isset($this->args['multiply']) && $this->args['multiply']) { ?>
                <td colspan="2">
                    <?php } else {?>
                <td>
                    <?php } ?>
                    <input type="button" id="<?= $this->nameInput($post, $parent) ?>" data-last-id="<?=$rid?>" class="button large-text" value="+">
                </td></tr>
            <?php } ?>
            </tbody>
        </table>
        <script>
            jQuery(function($){
                $('#<?= $this->nameInput($post, $parent) ?>').on('click', function(e) {
                    e.preventDefault();
                    var id = $(this).data('last-id')*1 + 1;
                    $(this).data('last-id', id);
                    $('#table_<?= $this->nameInput($post, $parent) ?> tr:last').before('<tr>'+
                    '<td>'+
                    <?php if(isset($this->labels['before'])): ?>'<input type="text" name="<?= $this->nameInput($post, $parent) ?>['+id+'][before]" placeholder="<?=$this->labels['before']?>" class="large-text code" value=""/><hr>'+ <?php endif; ?>
                    <?php if(isset($this->args['sub']) && $this->args['sub']) { ?>'<label>Sub item: <input class="subitem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>['+id+'][sub]" value="1"/></label>' + <?php } ?>
                    <?php if(isset($this->args['bold']) && $this->args['bold']) { ?>'<label>Bold item: <input class="bolditem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>['+id+'][bold]" value="1"/></label>' + <?php } ?>
                    '<input type="text" name="<?= $this->nameInput($post, $parent) ?>['+id+'][0]" class="large-text code listing" value="" />'+
                    <?php if(isset($this->labels['after'])): ?>'<hr><input type="text" name="<?= $this->nameInput($post, $parent) ?>['+id+'][after]" placeholder="<?=$this->labels['after']?>" class="large-text code" value=""/>'+ <?php endif; ?>
                    '</td>'+
                    <?php if(isset($this->args['multiply']) && $this->args['multiply']): ?>'<td width="40" style="vertical-align: bottom"><input type="button" class="button <?= $this->nameInput($post, $parent) ?>" data-row-id="'+id+'" data-last-id="0" value="+"></td>' +<?php endif; ?>
                    '</tr>');
                });
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.<?= $this->nameInput($post, $parent) ?>', function(e) {
                    e.preventDefault();
                    var id = $(this).data('last-id')*1 + 1;
                    var row = $(this).data('row-id');
                    $(this).data('last-id', id);
                    $('#table_<?= $this->nameInput($post, $parent) ?> tr:eq('+row+') td:first input.listing:last').after('<input type="text" name="<?= $this->nameInput($post, $parent) ?>['+row+']['+id+']" class="large-text code listing" value="">');
                })
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.subitem', function(e) {
                    if($(this).is(':checked')) {
                        $(this).parents('td').addClass('sub');
                    } else {
                        $(this).parents('td').removeClass('sub');
                    }
                });
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.bolditem', function(e) {
                    if($(this).is(':checked')) {
                        $(this).parents('td').addClass('bold');
                    } else {
                        $(this).parents('td').removeClass('bold');
                    }
                });
            });
        </script>
    <?php
    }

    protected function htmlRadio($post, $parent)
    {
        $value = $this->getValue($parent, 'attr', $post);
        foreach ($this->attaches as $option) {
            if (is_array($option)) { ?>
                <label><input type="radio" <?php checked($value, esc_attr($option[0])); ?> name="<?= $this->nameInput($post, $parent) ?>"
                    value="<?= esc_attr($option[0]) ?>">&nbsp;<?= esc_html($option[1]) ?></label>
            <?php } else { ?>
                <label><input type="radio" <?php checked($value, esc_attr($option)); ?> name="<?= $this->nameInput($post, $parent) ?>"
                    value="<?= esc_attr($option) ?>">&nbsp;<?= ucfirst(esc_html($option)) ?></label>
            <?php
            }
        }
    }
}