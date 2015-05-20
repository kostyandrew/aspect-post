<?php

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
    public function renderCategoryBox($post, $type) {
        if($type === 'create') {
            echo '<h3>'.$this->labels['singular_name'].'</h3>';
            $this->descriptionBox();
        }
        if($type === 'edit') { ?>
            <h3><?= $this->labels['singular_name']; ?></h3>
            <?php $this->descriptionBox(); ?>
            <table class="form-table"><tbody><?php
        }
        wp_nonce_field(self::getName($this), self::getName($this));
        foreach ($this->attaches as $input) {
            $input->render($post, $this);
        }
        if($type === 'edit') {
            echo '</tbody></table>';
        }
    }

    public function descriptionBox()
    {
        if (isset($this->args['description'])) echo '<p>' . $this->args['description'] . '</p>';
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
            $data = null;
            if (!isset($_POST[$input->nameInput(null, $this)])) continue;
            if(is_string($_POST[$input->nameInput(null, $this)]))
                $data = sanitize_text_field($_POST[$input->nameInput(null, $this)]);
            if(is_array($_POST[$input->nameInput(null, $this)])) {
                $values = $_POST[$input->nameInput(null, $this)];
                if($input->args['type'] === 'listing') {
                    foreach($values as $val) {
                        $idata = array();
                        foreach($val as $id => $ival) {
                            if(!empty($ival))
                                $idata[$id] = sanitize_text_field($ival);
                        }
                        if(!empty($idata))
                            $data[] = $idata;
                    }
                }else{
                    $data = array_map('sanitize_text_field',$values);
                }
            }
            update_post_meta($post_id, $input->nameInput(null, $this), $data);
        }
        return $post_id;
    }
    public function saveTaxonomyBox($term_id)
    {
        if (!isset($_POST[self::getName($this)]) or !wp_verify_nonce($_POST[self::getName($this)], self::getName($this)))
            return $term_id;
        if (!current_user_can('manage_categories'))
            return $term_id;
        foreach ($this->attaches as $input) {
            if (!isset($_POST[$input->nameInput(null, $this)])) continue;
            $data = sanitize_text_field($_POST[$input->nameInput(null, $this)]);
            if($data)
                Aspect_Taxonomy::update_term_meta($term_id, $input->nameInput(null, $this), $data);
        }
        return $term_id;
    }
}