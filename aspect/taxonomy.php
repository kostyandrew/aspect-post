<?php
namespace Aspect;
class Taxonomy extends Base
{
    private $reserved = array(
        'attachment',
        'attachment_id',
        'author',
        'author_name',
        'calendar',
        'cat',
        'category_name',
        'category__and',
        'category__in',
        'category__not_in',
        'comments_per_page',
        'comments_popup',
        'cpage',
        'day',
        'debug',
        'error',
        'exact',
        'feed',
        'hour',
        'link',
        'minute',
        'monthnum',
        'more',
        'name',
        'nav_menu',
        'nopaging',
        'offset',
        'order',
        'orderby',
        'p',
        'page',
        'paged',
        'pagename',
        'page_id',
        'pb',
        'perm',
        'post',
        'posts',
        'posts_per_archive_page',
        'posts_per_page',
        'post_format',
        'post_mime_type',
        'post_status',
        'post_type',
        'preview',
        'robots',
        's',
        'search',
        'second',
        'sentence',
        'showposts',
        'static',
        'subpost',
        'subpost_id',
        'tag',
        'tag_id',
        'tag_slug__and',
        'tag_slug__in',
        'tag__and',
        'tag__in',
        'tag__not_in',
        'taxonomy',
        'tb',
        'term',
        'type',
        'w',
        'withcomments',
        'withoutcomments',
        'year'
    );
    private $registered = false;
    protected static $objects = array();

    public function registerTaxonomy($post_type)
    {
        $name = self::getName($this);

        if (!in_array($name, $this->reserved) && !$this->registered) {
            register_taxonomy($name, (string)$post_type, $this->args);
        } else {
            register_taxonomy_for_object_type($name, $post_type);
        }

        if(!$this->registered) {
            foreach ($this->attaches as $attach) {
                if ((is_subclass_of($attach,'\Aspect\Box') or $attach instanceof \Aspect\Box) and is_admin()) {
                    add_action(self::getName($this)."_edit_form", function ($term) use ($attach) {
                        $attach->renderCategoryBox($term, 'edit');
                    });
                    add_action(self::getName($this)."_add_form_fields", function ($tax) use ($attach) {
                        $term = new \stdClass();
                        $term->taxonomy = $tax;
                        $attach->renderCategoryBox($term, 'create');
                    });
                    add_action('edit_' . $this, array($attach, 'saveTaxonomyBox'));
                    add_action('create_' . $this, array($attach, 'saveTaxonomyBox'));
                }
            }
        }
        $this->registered = true;
    }

    public static function termMetaDbName()
    {
        global $table_prefix;
        $additional_name = ASPECT_PREFIX;
        if ($additional_name) $additional_name .= '_';
        return $table_prefix . $additional_name . 'termmeta';
    }

    public static function createTermMetaDb()
    {
        global $wpdb;
        $table_name = static::termMetaDbName();
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table_name}` (
	`meta_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`term_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
	`meta_key` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`meta_value` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
	PRIMARY KEY (`meta_id`),
	INDEX `term_id` (`term_id`),
	INDEX `meta_key` (`meta_key`(191))
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1;
SQL;
        $wpdb->query($sql);
    }

    public static function get_term_meta($term_id, $key, $single = false)
    {
        global $wpdb;
        $table_name = static::termMetaDbName();
        $query = <<<SQL
SELECT * FROM `{$table_name}` WHERE `meta_key` = '{$key}' AND `term_id` = {$term_id}
SQL;
        if ($single) {
            return $wpdb->get_row($query, ARRAY_A)['meta_value'];
        } else {
            return $wpdb->get_col($query, 3);
        }
    }

    public static function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '')
    {
        global $wpdb;
        if ($prev_value != '') {
            return $wpdb->update(
                static::termMetaDbName(),
                array(
                    'meta_value' => $meta_value
                ),
                array(
                    'term_id' => $term_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $prev_value
                )
            );
        } else {
            if (static::get_term_meta($term_id, $meta_key)) {

                return $wpdb->update(
                    static::termMetaDbName(),
                    array(
                        'meta_value' => $meta_value
                    ),
                    array(
                        'term_id' => $term_id,
                        'meta_key' => $meta_key,
                    )
                );
            } else {
                return $wpdb->insert(
                    static::termMetaDbName(),
                    array(
                        'term_id' => $term_id,
                        'meta_key' => $meta_key,
                        'meta_value' => $meta_value
                    )
                );
            }
        }

    }

    public static function add_term_meta($term_id, $meta_key, $meta_value, $unique = false)
    {
        global $wpdb;
        $table_name = static::termMetaDbName();
        if ($unique) {
            $sql = <<<SQL
SELECT count(`meta_id`) FROM `{$table_name}` WHERE `meta_key` = '{$meta_key}' AND `meta_value` = '{$meta_value}'
SQL;

            $count = $wpdb->get_var($sql);
            if ($count > 0) return false;
        }
        return $wpdb->insert(
            $table_name,
            array(
                'term_id' => $term_id,
                'meta_key' => $meta_key,
                'meta_value' => $meta_value
            )
        );
    }

    public function get_terms($args) {
        return get_terms(strval($this), $args);
    }
}