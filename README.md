ASPECT-Post
===========
PHP Class for WordPress which help create type of post, taxonomies and metabox

You can use standard arguments and labels setting WordPress using setArgument and setLabel methods

### Creating post type: ###
```php
use \Aspect\Type;
$slides = new Type('slide');
$slides
  ->addSupport('thumbnail') // add support thumbnail
  ->setArgument('public', true) // public argument
  ->setArgument('show_in_nav_menus', false); // hide in creating menu
```
### Creating taxonomy: ###
```php
use \Aspect\Taxonomy;
$slides_type = new Taxonomy('type');
$slides_type
  ->attachTo($slides); // post type where will be created taxonomy
```

### Creating metabox: ###
```php
use \Aspect\Box;
$slides_settings = new Box('slide setting');
$slides_settings
  ->attachTo($slides); // post type
```

### Creating metabox input: ###
```php
use \Aspect\Input;
$text_color = new Input('text color');
$text_color
  ->attachTo($slides_settings) // metabox
  ->attach('white', 'black') // values for select
  ->setType('select') // type of input
  ->setArgument('default', 'black'); // default
```


### Get value of metabox field: ###
```php
$text_color = Input::get('text color')->getValue($slide_id, 'attr', Box::get('slide setting'));
```