<?php
namespace Aspect;
class Type extends Base
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
    public $args = array(
        'supports' => array('title', 'editor')
    );
    protected static $objects = array();

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
        $name = self::getName($this);
        if (!in_array($name, static::$reserved) && !$this->registered)
            register_post_type($name, $this->args);

        foreach ($this->attaches as $attach) {
            if (is_a($attach, '\Aspect\Box') and is_admin()) { /* @var $attach \Aspect\Box */
                add_action("save_post", array($attach, 'savePostBox'));
                add_action("add_meta_boxes", function () use ($attach) {
                    add_meta_box(self::getName($attach), $attach->labels['singular_name'], array($attach, 'renderBox'), (string)$this, $attach->args['context'], $attach->args['priority']);
                });
            }
            // create meta box in admin panel only

            if (is_a($attach, '\Aspect\Taxonomy')) { /* @var $attach \Aspect\Taxonomy */
                $attach->registerTaxonomy(strval($this));
            }
        }
    }

    public function getOrigin($args = array()) {
        $origin = parent::getOrigin($args);
        $origin
            ->setType('post')
            ->setPostType($this);
        return $origin;
    }
}
