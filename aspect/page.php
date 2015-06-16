<?php
namespace Aspect;
class Page extends Base
{
    protected static $objects = array();

    public function __construct($name)
    {
        parent::__construct($name);
        add_action('admin_menu', function () {
            if (isset($this->args['parent_slug'])) {
                call_user_func(array($this, 'addSubMenuPage'));
            } else {
                call_user_func(array($this, 'addMenuPage'));
            }
        });
        add_action('init', function () {
            foreach ($this->attaches as $attach) {
                if (is_a($attach,'\Aspect\Page')) { /* @var $attach \Aspect\Page */
                    $attach->setArgument('parent_slug', self::getName($this));
                    remove_action('admin_menu', array($attach, 'addMenuPage'));
                    add_action('admin_menu', array($attach, 'addSubMenuPage'));
                    continue;
                } elseif (is_a($attach, '\Aspect\Box')) { /* @var $attach \Aspect\Box */
                    $section = $attach;
                } else {
                    throw new \Exception('Incorrect input parameters');
                }
                add_action('admin_init', function () use ($section) {
                    add_settings_section(self::getName($section, $this), $section->labels['singular_name'], array($section, 'descriptionBox'), self::getName($this));
                });
                foreach ($section->attaches as $field) { /* @var $field \Aspect\Input */
                    add_action('admin_init', function () use ($field, $section) {
                        register_setting(self::getName($this), self::getName($field, $section, $this));
                        add_settings_field(self::getName($field, $section, $this), $field->label($this, $section), array($field, 'render'), self::getName($this), self::getName($section, $this), array($this, $section));
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