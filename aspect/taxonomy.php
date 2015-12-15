<?php
namespace Aspect;
class Taxonomy extends Base
{
    static private $reserved = array(
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
        'post', 'post_tag',
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

        if (!in_array($name, static::$reserved) && !$this->registered) {
            register_taxonomy($name, (string)$post_type, $this->args);
        } else {
            register_taxonomy_for_object_type($name, $post_type);
        }

        if (!$this->registered) {
            foreach ($this->attaches as $attach) {
                if (is_a($attach, '\Aspect\Box') and is_admin()) {
                    /* @var $attach \Aspect\Box */
                    add_action(self::getName($this) . "_edit_form", function ($term) use ($attach) {
                        $attach->renderCategoryBox($term, 'edit');
                    });
                    add_action(self::getName($this) . "_add_form_fields", function ($tax) use ($attach) {
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
        if (get_bloginfo('version') >= 4.4) return false;
        global $table_prefix;
        $additional_name = ASPECT_PREFIX;
        if ($additional_name) $additional_name .= '_';
        return $table_prefix . $additional_name . 'termmeta';
    }

    public static function createTermMetaDb()
    {
        if (get_bloginfo('version') >= 4.4) return false;
        global $wpdb;
        $table_name = $wpdb->termmeta;
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$table_name}` (
	`meta_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`term_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
	`meta_key` VARCHAR(255) NULL DEFAULT NULL,
	`meta_value` LONGTEXT NULL,
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
        if (get_bloginfo('version') >= 4.4) return get_term_meta($term_id, $key, $single);
        return get_metadata('term', $term_id, $key, $single);
    }

    public static function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '')
    {
        if (get_bloginfo('version') >= 4.4) return update_term_meta($term_id, $meta_key, $meta_value, $prev_value);
        return update_metadata('term', $term_id, $meta_key, $meta_value, $prev_value);
    }

    public static function add_term_meta($term_id, $meta_key, $meta_value, $unique = false)
    {
        if (get_bloginfo('version') >= 4.4) return add_term_meta($term_id, $meta_key, $meta_value, $unique);
        return add_metadata('term', $term_id, $meta_key, $meta_value, $unique);
    }

    public function get_terms($args = array())
    {
        return get_terms(strval($this), $args);
    }

    public static function initTermMeta()
    {
        if (get_bloginfo('version') >= 4.4) return false;
        global $wpdb;
        $wpdb->termmeta = static::termMetaDbName();
    }

    protected static function init()
    {
        static $initialized = false;
        if (!$initialized) {
            parent::init();
            if (get_bloginfo('version') < 4.4 or 1) {
                add_action('after_switch_theme', array(get_called_class(), 'createTermMetaDb'));
                add_action('init', array(get_called_class(), 'initTermMeta'));
            }
            $initialized = true;
        }
    }

    public function getOrigin($args = array())
    {
        $origin = parent::getOrigin($args);
        $origin
            ->setType('taxonomy')
            ->setTaxonomy($this);
        return $origin;
    }
}