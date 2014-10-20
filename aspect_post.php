<?php
/* @todo: make base class */
class Aspect_Type {
	private $post_type_name;
	private $post_type_args = array();
	private $post_type_labels = array();
	public function __construct($post_type_name) {
		$this->post_type_name = $post_type_name;

		$this->post_type_args['labels'] = &$this->post_type_labels;
		$this->post_type_args['supports'] = array();

		/* Creating Label Using Translating */
		$singular_name = ucfirst($this->post_type_name);
		$name = $singular_name.'s';
		$this->post_type_labels['singular_name'] = __($singular_name);
		$this->post_type_labels['name'] = __($name);

		/* Default Supports */
		$this->addSupport('title');
		$this->addSupport('editor');
	}
	public function __toString() {
		return $this->get_name();
	}

	public function addSupport() {
		$args = func_get_args();
		$this->post_type_args['supports'] = array_merge($this->post_type_args['supports'], $args);
	}
	public function removeSupport() {
		$args = func_get_args();
		$this->post_type_args['supports'] = array_diff($this->post_type_args['supports'], $args);
	}

	public function setArgument($args, $data = null) {
		if(is_array($args)) {
			$this->post_type_args = array_merge($this->post_type_args, $args);
		}elseif(is_string($args)) {
			$this->post_type_args[$args] = $data;
		}
	}
	public function unsetArgument($name) {
		if(isset($this->post_type_args[$name])) {
			unset($this->post_type_args[$name]);
		}
	}

	public function setLabel($args, $data) {
		if(is_array($args)) {
			$this->post_type_labels = array_merge($this->post_type_labels, $args);
		}elseif(is_string($args)) {
			$this->post_type_labels[$args] = $data;
		}
	}
	public function unsetLabel($name) {
		if(isset($this->post_type_labels[$name])) {
			unset($this->post_type_labels[$name]);
		}
	}

	public function get_name() {
		return $this->post_type_name;
	}
	/* @todo: make method for public argument */

	public function save() {
		add_action("init", array(&$this, 'register_post_type'));
	}
	public function register_post_type() {
		register_post_type($this->post_type_name, $this->post_type_args);
	}
}

class Aspect_Taxonomy {
	private $taxonomy_name;
	private $taxonomy_args = array();
	private $taxonomy_labels = array();
	private $post_types = array();

	public function __construct($taxonomy_name) {
		$this->taxonomy_name = $taxonomy_name;
		$this->taxonomy_args['labels'] = &$this->taxonomy_labels;
	}
	public function __toString() {
		return $this->get_name();
	}

	public function setArgument($args, $data) {
		if(is_array($args)) {
			$this->taxonomy_args = array_merge($this->taxonomy_args, $args);
		}elseif(is_string($args)) {
			$this->taxonomy_args[$args] = $data;
		}
	}
	public function unsetArgument($name) {
		if(isset($this->taxonomy_args[$name])) {
			unset($this->taxonomy_args[$name]);
		}
	}

	public function setLabel($args, $data = null) {
		if(is_array($args)) {
			$this->taxonomy_labels = array_merge($this->taxonomy_labels, $args);
		}elseif(is_string($args)) {
			$this->taxonomy_labels[$args] = $data;
		}
	}
	public function unsetLabel($name) {
		if(isset($this->taxonomy_labels[$name])) {
			unset($this->taxonomy_labels[$name]);
		}
	}

	public function get_name() {
		return $this->taxonomy_name;
	}

	/* @todo: make method for public argument */

	public function attachType() {
		$obj_type = func_get_args();
		$type = array_map('strval', $obj_type);
		$this->post_types = array_merge($this->post_types, $type);
	}

	public function save() {
		add_action("init", array(&$this, 'register_taxonomy'), 0);
	}
	public function register_taxonomy() {
		register_taxonomy($this->taxonomy_name, $this->post_types, $this->taxonomy_args);
	}
}
