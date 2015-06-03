<?php
namespace Component\Input\Select;
trait Columns {
    protected function htmlSelectColumns($post, $parent)
    {
        $value = $this->getValue($parent, null, $post);
        $posts = $this->attaches;
        ?>
        <script>
            jQuery(function ($) {
                $('.<?= $this->nameInput($post, $parent) ?>.forward').on('click', function (event) {
                    event.preventDefault();
                    $('.<?= $this->nameInput($post, $parent) ?>.from').find(':selected').each(function () {
                        $(this).removeAttr('selected');
                        $(this).appendTo('.<?= $this->nameInput($post, $parent) ?>.to')
                    });
                });
                $('.<?= $this->nameInput($post, $parent) ?>.back').on('click', function (event) {
                    event.preventDefault();
                    $('.<?= $this->nameInput($post, $parent) ?>.to').find(':selected').each(function () {
                        $(this).removeAttr('selected');
                        $(this).appendTo('.<?= $this->nameInput($post, $parent) ?>.from')
                    });
                });
                $('.<?= $this->nameInput($post, $parent) ?>').parents('form').on('submit', function () {
                    $('.<?= $this->nameInput($post, $parent) ?>.to option').attr({'selected': 'selected'});
                });
            });
        </script>
        <label
            style="display: inline-block"> <?= isset($this->labels['unselected_name']) ? $this->labels['unselected_name'] : 'Unselected'; ?>
            <br>
            <select class="<?= $this->nameInput($post, $parent) ?> from" multiple>
                <?php foreach ($posts as $option) { ?>
                    <?php if (in_array(esc_attr($option[0]), $value)) continue; ?>
                    <option value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                <?php } ?>
            </select>
        </label>
        <div style="display: inline-block">
            <a href="#" class="back <?= $this->nameInput($post, $parent) ?>">&lt;&lt;</a><br/>
            <a href="#" class="forward <?= $this->nameInput($post, $parent) ?>">&gt;&gt;</a>
        </div>
        <label
            style="display: inline-block"> <?= isset($this->labels['selected_name']) ? $this->labels['selected_name'] : 'Selected'; ?>
            <br>
            <select class="<?= $this->nameInput($post, $parent) ?> to" name="<?= $this->nameInput($post, $parent) ?>[]"
                    id="<?= $this->nameInput($post, $parent) ?>" multiple>
                <?php foreach ($posts as $option) { ?>
                    <?php if (!in_array(esc_attr($option[0]), $value)) continue; ?>
                    <option value="<?= esc_attr($option[0]) ?>"><?= esc_html($option[1]) ?></option>
                <?php } ?>
            </select>
        </label>
    <?php
    }
}