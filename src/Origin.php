<?php
namespace Aspect;
class Origin extends Base
{
    protected static $objects = array();
    public $args = array();
    public $type = 'post';
    protected static $defaultArgs = array(
        'post' => array(
            'post_type' => 'post',
            'posts_per_page' => -1
        ),
        'taxonomy_name' => 'category',
        'taxonomy' => array(
            'hide_empty' => false, // for options and meta fields
            'fields' => 'id=>name'
        )
    );
    protected $resultArgs = array();

    public function returnOrigin($output = 'standard')
    {
        $type = $this->type;
        $method = $type . 'Flush';
        $default_args = (array)static::$defaultArgs[$type];
        $args = (array)$this->args;
        $args = wp_parse_args($args, $default_args);
        $this->resultArgs = $args;
        if (!method_exists($this, $method))
            throw new \Exception('Origin type ' . $type . ' not found');
        return call_user_func_array(array($this, $method), func_get_args());
    }

    protected function postFlush($output = 'standard')
    {
        $args = $this->resultArgs;
        $posts = get_posts($args);
        $result = array();
        foreach ($posts as $post) {
            switch ($output) {
                case 'id': {
                    $result[] = $post->ID;
                    break;
                }
                case 'name': {
                    $result[] = $post->post_title;
                    break;
                }
                default: {
                    $result[] = array($post->ID, $post->post_title);
                }
            }
        }
        return $result;
    }

    protected function taxonomyFlush($output = 'standard')
    {
        $args = $this->resultArgs;
        $fields = $args['fields'];
        $taxonomy = (isset($this->args['taxonomy_name'])) ? $this->args['taxonomy_name'] : $this->defaultArgs['taxonomy_name'];
        $terms = get_terms($taxonomy, $args);
        $result = array();
        foreach ($terms as $id => $term) {
            if ($fields === 'id=>name') {
                switch ($output) {
                    case 'id': {
                        $result[] = $id;
                        break;
                    }
                    case 'name': {
                        $result[] = $term;
                        break;
                    }
                    default: {
                        $result[] = array($id, $term);
                    }
                }
            } else {
                throw new \Exception('Too many data requested in origin ' . strval($this));
            }
        }
        return $result;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setPostType($type)
    {
        if (is_a($type, '\Aspect\Type')) $type = strval($type);
        $this->args['post_type'] = $type;
        return $this;
    }

    public function setTaxonomy($type)
    {
        if (is_a($type, '\Aspect\Taxonomy')) $type = strval($type);
        $this->args['taxonomy_name'] = $type;
        return $this;
    }
}