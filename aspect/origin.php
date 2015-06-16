<?php
namespace Aspect;
class Origin extends Base
{
    protected static $objects = array();
    public $args = array(
        'type' => 'post'
    );
    protected $defaultArgs = array(
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

    public function returnOrigin()
    {
        $type = $this->args['type'];
        $method = $type . 'Flush';
        if (!method_exists($this, $method))
            throw new \Exception('Origin type ' . $type . ' not found');
        return call_user_func(array($this, $method));
    }

    protected function postFlush()
    {
        $default_args = (array) $this->defaultArgs['post'];
        $custom_args = (array) $this->args;
        $args = array_merge($custom_args, $default_args);
        $posts = get_posts($args);
        $result = array();
        foreach($posts as $post) {
            $result[] = array($post->ID, $post->post_title);
        }
        return $result;
    }

    protected function taxonomyFlush()
    {
        $default_args = (array) $this->defaultArgs['taxonomy'];
        $custom_args = (array) $this->args;
        $args = array_merge($custom_args, $default_args);
        $fields = $args['fields'];
        $taxonomy = (isset($this->args['taxonomy_name'])) ? $this->args['taxonomy_name'] : $this->defaultArgs['taxonomy_name'];
        $terms = get_terms($taxonomy, $args);
        $result = array();
        foreach($terms as $id => $term) {
            if($fields === 'id=>name') {
                $result[] = array($id, $term);
            } elseif($fields === 'all') {
                $result[] = array($term->term_id, $term->name);
            }else {
                throw new \Exception('Unsupported fields arg = '.$fields);
            }
        }
        return $result;
    }

    public function setPostType($type) {
        if(is_a($type, '\Aspect\Type')) $type = strval($type);
        $this->args['args']['post_type'] = $type;
        return $this;
    }
    public function setTaxonomy($type) {
        if(is_a($type, '\Aspect\Taxonomy')) $type = strval($type);
        $this->args['taxonomy_name'] = $type;
        return $this;
    }
}