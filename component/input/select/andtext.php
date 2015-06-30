<?php
namespace Component\Input\Select;
trait AndText
{
    protected function htmlSelectAndText($post, $parent)
    {
        /* @var $this \Aspect\Input */
        $value = $this->getValue($parent, null, $post);
        $select = $this->attaches;
        $columns = (isset($this->args['columns'])) ? $this->args['columns'] : 1;
        if (!is_array($value) or empty($value)) {
            $start_array = array_fill(0, $columns, null);
            $start_array = array_merge($start_array, array('select' => null));
            $value = array(
                $start_array
            );
        }
        $value = (array)$value;
        ?>
        <style>
            #table_<?= $this->nameInput($post, $parent) ?> .large-text {
                width: 100%;
            }

            #table_<?= $this->nameInput($post, $parent) ?> tbody tr td {
                border: 1px solid #eee;
                padding: 5px;
            }

            #table_<?= $this->nameInput($post, $parent) ?> thead tr td {
                border: 1px solid #999;
                padding: 5px;
                background-color: #eee;
            }

            #table_<?= $this->nameInput($post, $parent) ?> tbody tr:hover td {
                border-color: #aaa;
            }

            #table_<?= $this->nameInput($post, $parent) ?> tbody tr td:hover {
                border-color: #444;
            }
        </style>
        <table id="table_<?= $this->nameInput($post, $parent) ?>" width="100%">
            <thead>
            <tr>
                <td><?= $this->labels['select'] ?></td>
                <?php for ($i = 0; $i < $columns; $i++) { ?>
                    <td><?= $this->labels['column' . ($i + 1)] ?></td>
                <?php } ?>
                <td>Del</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($value as $row_id => $row) { ?>
                <tr>
                    <?php
                    if (!isset($row['select']) and isset($this->args['default']['select'])) {
                        $row['select'] = $this->args['default']['select'];
                    } elseif(!isset($row['select'])) {
                        $row['select'] = '';
                    }
                    ?>
                    <td><select class="large-text <?= $this->nameInput($post, $parent) ?>_select"
                                name="<?= $this->nameInput($post, $parent) ?>[<?= $row_id ?>][select]">
                            <?php foreach ($select as $option) {
                                if (is_array($option)) { ?>
                                    <option <?php $this->selected($row['select'], esc_attr($option[0])); ?>
                                        value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                                <?php } else { ?>
                                    <option <?php $this->selected($row['select'], esc_attr($option)); ?>
                                        value="<?= esc_attr($option) ?>"><?= ucfirst(esc_html($option)) ?></option>
                                <?php
                                }
                            } ?>
                        </select></td>
                    <?php for ($i = 0; $i < $columns; $i++) {
                        if (!isset($row[$i]) and isset($this->args['default']['column' . ($i + 1)])) {
                            $row[$i] = $this->args['default']['column' . ($i + 1)];
                        } elseif(!isset($row[$i])) {
                            $row[$i] = '';
                        }
                        ?>
                        <td>
                            <input class="large-text code" type="text"
                                   name="<?= $this->nameInput($post, $parent) ?>[<?= $row_id ?>][<?= $i ?>]"
                                   value="<?= $row[$i] ?>"/>
                        </td>
                    <?php } ?>
                    <td width="30">
                        <input type="button" class="<?= $this->nameInput($post, $parent) ?>_delete button"
                               value="&times;">
                    </td>
                </tr>
            <?php } ?>
            </tbody>
            <tfoot>
            <?php if (!isset($this->args['single']) or !$this->args['single']) { ?>
                <tr>
                    <td colspan="<?= $columns + 2 ?>" style="vertical-align: bottom">
                        <input type="button" id="<?= $this->nameInput($post, $parent) ?>" data-last-id="<?= $row_id ?>"
                               class="button large-text" value="+">
                    </td>
                </tr>
            <?php } ?>
            </tfoot>
        </table>
        <script>
            jQuery(function ($) {
                $('#<?= $this->nameInput($post, $parent) ?>').on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('last-id') * 1 + 1;
                    var columns = <?=$columns?>;
                    var defaults = <?=json_encode($this->args['default']);?>;
                    $(this).data('last-id', id);
                    var message = '<tr>';
                    message += '<td><select class="large-text <?= $this->nameInput($post, $parent) ?>_select" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][select]">' + $('.<?= $this->nameInput($post, $parent) ?>_select').first().html() + '</select></td>';
                    for (var i = 0; i < columns; i++) {
                        var default_val;
                        if(defaults['column'+(i+1)]) {
                            default_val = defaults['column'+(i+1)];
                        }else{
                            default_val = '';
                        }
                        message += '<td><input class="large-text code" type="text" name="<?= $this->nameInput($post, $parent) ?>[' + id + '][' + i + ']" value="'+default_val+'"/></td>';
                    }
                    message += '<td width="30"><input type="button" class="<?= $this->nameInput($post, $parent) ?>_delete button" value="&times;"></td>';
                    message += '</tr>';
                    $('#table_<?= $this->nameInput($post, $parent) ?> tbody tr:last').after(message);
                    $(this).data('last-id', id);
                });
                $(document).on('click', '.<?= $this->nameInput($post, $parent) ?>_delete', function () {
                    $(this).closest('tr').hide();
                    $(this).closest('tr').find('input, select').each(function () {
                        $(this).attr('disabled', 'disabled');
                    });
                });
            });
        </script>
    <?php }
}