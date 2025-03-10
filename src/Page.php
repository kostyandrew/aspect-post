<?php
namespace Aspect;
class Page extends Base
{
    protected static $objects = array();

    public function __construct($name)
    {
        parent::__construct($name);
        $object = $this;
        add_action('admin_menu', function () use($object) {
            if (isset($object->args['parent_slug'])) {
                call_user_func(array($object, 'addSubMenuPage'));
            } else {
                call_user_func(array($object, 'addMenuPage'));
            }
        });
        add_action('init', function () use($object) {
            foreach ($object->attaches as $attach) {
                if (is_a($attach,'\Aspect\Page')) { /* @var $attach \Aspect\Page */
                    $attach->setArgument('parent_slug', $object::getName($object));
                    remove_action('admin_menu', array($attach, 'addMenuPage'));
                    add_action('admin_menu', array($attach, 'addSubMenuPage'));
                    continue;
                } elseif (is_a($attach, '\Aspect\Box')) { /* @var $attach \Aspect\Box */
                    $section = $attach;
                } else {
                    throw new \Exception('Incorrect input parameters');
                }
                add_action('admin_init', function () use ($section, $object) {
                    add_settings_section($object::getName($section, $object), $section->labels['singular_name'], array($section, 'descriptionBox'), $object::getName($object));
                });
                foreach ($section->attaches as $field) { /* @var $field \Aspect\Input */
                    add_action('admin_init', function () use ($field, $section, $object) {
                        register_setting($object::getName($object), $object::getName($field, $section, $object));
                        add_settings_field($object::getName($field, $section, $object), $field->label($object, $section), array($field, 'render'), $object::getName($object), $object::getName($section, $object), array($object, $section));
                    });
                }
            }
        });
    }

    public function addMenuPage()
    {
        add_menu_page($this->labels['singular_name'], $this->labels['singular_name'], 'manage_options', self::getName($this), array($this, 'renderPage'));
    }

    public function addSubMenuPage()
    {
        add_submenu_page($this->args['parent_slug'], $this->labels['singular_name'], $this->labels['singular_name'], 'manage_options', self::getName($this), array($this, 'renderPage'));
    }

    function renderPage()
    { ?>
        <div class="wrap">
            <h2><?php echo get_admin_page_title() ?></h2>

            <form action="options.php" method="POST">
                <?php
                settings_fields(self::getName($this));
                do_settings_sections(self::getName($this));
                submit_button();
                ?>
            </form>
        </div>
    <?php }
}