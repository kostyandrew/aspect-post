<?php
namespace Component\Input;
trait Listing {
    protected function htmlListing($post, $parent)
    {
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
            <?php if (!is_array($values) or empty($values)) {
                if (isset($this->labels['before'])) $values[0]['before'] = '';
                $values[0][0] = '';
                if (isset($this->labels['after'])) $values[0]['after'] = array('');
            }
            foreach ($values as $rid => $value) {
                $class = array(); ?>
                <tr>
                    <?php
                    if (isset($this->args['sub']) && $this->args['sub'] && isset($value['sub']) && $value['sub']) $class[] = 'sub';
                    if (isset($this->args['bold']) && $this->args['bold'] && isset($value['bold']) && $value['bold']) $class[] = 'bold';
                    ?>
                    <td class="<?= implode(' ', $class) ?>">
                        <?php
                        if (isset($this->labels['before']) && !isset($value['before'])) $value['before'] = '';
                        if (!isset($value[0])) $value[0] = '';
                        if (isset($this->labels['after']) && !isset($value['after'])) $value['after'] = array('');
                        if (isset($this->labels['before'])) { ?>
                            <input type="text" name="<?= $this->nameInput($post, $parent) ?>[<?= $rid ?>][before]"
                                   placeholder="<?= $this->labels['before'] ?>" class="large-text code"
                                   value="<?= esc_attr($value['before']) ?>"/>
                            <hr>
                        <?php }
                        if (isset($this->args['sub']) && $this->args['sub']) {
                            if (!isset($value['sub']) or empty($value['sub'])) $value['sub'] = 0; ?>
                            <label>Sub item: <input class="subitem" type="checkbox"
                                                    name="<?= $this->nameInput($post, $parent) ?>[<?= $rid ?>][sub]"
                                                    value="1" <?php if ($value['sub']) echo 'checked' ?>/></label>
                        <?php }
                        if (isset($this->args['bold']) && $this->args['bold']) {
                            if (!isset($value['bold']) or empty($value['bold'])) $value['bold'] = 0; ?>
                            <label>Bold item: <input class="bolditem" type="checkbox"
                                                     name="<?= $this->nameInput($post, $parent) ?>[<?= $rid ?>][bold]"
                                                     value="1" <?php if ($value['bold']) echo 'checked' ?>/></label>
                        <?php }
                        $last_id = 0;
                        foreach ($value as $id => $input) {
                            if (!is_numeric($id))
                                continue;
                            $last_id = $id;
                            ?>
                            <input type="text" name="<?= $this->nameInput($post, $parent) ?>[<?= $rid ?>][<?= $id ?>]"
                                   class="large-text code listing" value="<?= esc_attr($input) ?>"/>
                        <?php }
                        if (isset($this->labels['after'])) { ?>
                            <hr/>
                            <?php
                            $afters = $value['after'];
                            foreach ($afters as $aid => $after) { ?>
                                <input type="text"
                                       name="<?= $this->nameInput($post, $parent) ?>[<?= $rid ?>][after][<?= $aid ?>]"
                                       placeholder="<?= $this->labels['after'] ?>" class="large-text code"
                                       value="<?= esc_attr($after) ?>"/>
                            <?php } ?>
                            <?php if (isset($this->args['afterMultiply'])): ?><input type="button"
                                                                                     class="button <?= $this->nameInput($post, $parent) ?>_after"
                                                                                     data-row-id="<?= $rid ?>"
                                                                                     data-last-id="<?= $aid ?>"
                                                                                     value="+"><?php endif; ?>
                        <?php } ?>
                    </td>
                    <?php if (isset($this->args['multiply']) && $this->args['multiply']) { ?>
                        <td width="40" style="vertical-align: bottom"><input type="button"
                                                                             class="button <?= $this->nameInput($post, $parent) ?>"
                                                                             data-row-id="<?= $rid ?>"
                                                                             data-last-id="<?= $last_id ?>" value="+">
                        </td>
                    <?php } ?>
                </tr>
            <?php }
            if (!isset($this->args['single']) or !$this->args['single']) {
                ?>
                <tr><?php if (isset($this->args['multiply']) && $this->args['multiply']) { ?>
                    <td colspan="2">
                        <?php } else { ?>
                    <td>
                        <?php } ?>
                        <input type="button" id="<?= $this->nameInput($post, $parent) ?>" data-last-id="<?= $rid ?>"
                               class="button large-text" value="+">
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <script>
            jQuery(function ($) {
                $('#<?= $this->nameInput($post, $parent) ?>').on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('last-id') * 1 + 1;
                    $(this).data('last-id', id);
                    $('#table_<?= $this->nameInput($post, $parent) ?> tr:last').before('<tr>' +
                    '<td>' +
                    <?php if(isset($this->labels['before'])): ?>'<input type="text" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][before]" placeholder="<?=$this->labels['before']?>" class="large-text code" value=""/><hr>' + <?php endif; ?>
                    <?php if(isset($this->args['sub']) && $this->args['sub']) { ?>'<label>Sub item: <input class="subitem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][sub]" value="1"/></label>' + <?php } ?>
                    <?php if(isset($this->args['bold']) && $this->args['bold']) { ?>'<label>Bold item: <input class="bolditem" type="checkbox" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][bold]" value="1"/></label>' + <?php } ?>
                    '<input type="text" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][0]" class="large-text code listing" value="" />' +
                    <?php if(isset($this->labels['after'])): ?>'<hr><input type="text" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][after][0]" placeholder="<?=$this->labels['after']?>" class="large-text code" value=""/>' + <?php endif; ?>
                    <?php if(isset($this->args['afterMultiply'])): ?>'<input type="button" class="button_after <?= $this->nameInput($post, $parent) ?>" data-row-id="' + id + '" data-last-id="0" value="+">' + <?php endif; ?>
                    '</td>' +
                    <?php if(isset($this->args['multiply']) && $this->args['multiply']): ?>'<td width="40" style="vertical-align: bottom"><input type="button" class="button <?= $this->nameInput($post, $parent) ?>" data-row-id="' + id + '" data-last-id="0" value="+"></td>' + <?php endif; ?>
                    '</tr>');
                });
                <?php if(isset($this->args['multiply']) && $this->args['multiply']){ ?>
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.<?= $this->nameInput($post, $parent) ?>', function (e) {
                    e.preventDefault();
                    var id = $(this).data('last-id') * 1 + 1;
                    var row = $(this).data('row-id');
                    $(this).data('last-id', id);
                    $('#table_<?= $this->nameInput($post, $parent) ?> tr:eq(' + row + ') td:first input.listing:last').after('<input type="text" name="<?= $this->nameInput($post, $parent) ?>[' + row + '][' + id + ']" class="large-text code listing" value="">');
                });
                <?php } if(isset($this->args['sub']) && $this->args['sub']) { ?>
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.subitem', function (e) {
                    if ($(this).is(':checked')) {
                        $(this).parents('td').addClass('sub');
                    } else {
                        $(this).parents('td').removeClass('sub');
                    }
                });
                <?php } if(isset($this->args['bold']) && $this->args['bold']) { ?>
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.bolditem', function (e) {
                    if ($(this).is(':checked')) {
                        $(this).parents('td').addClass('bold');
                    } else {
                        $(this).parents('td').removeClass('bold');
                    }
                });
                <?php } if(isset($this->args['afterMultiply'])) { ?>
                $('#table_<?= $this->nameInput($post, $parent) ?>').on('click', '.<?= $this->nameInput($post, $parent) ?>_after', function (e) {
                    e.preventDefault();
                    var id = $(this).data('last-id') * 1 + 1;
                    var row = $(this).data('row-id');
                    $(this).data('last-id', id);
                    $(this).before('<input type="text" name="<?= $this->nameInput($post, $parent) ?>[' + row + '][after][' + id + ']" placeholder="<?=$this->labels['after']?>" class="large-text code" value=""/>');
                });
                <?php } ?>
            });
        </script>
    <?php
    }
}